<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Location;
use App\Models\Paiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    /**
     * Créer les commissions après un paiement réussi
     */
    public function creerCommissionsApresPaiement(Paiement $paiement)
    {
        if ($paiement->statut !== 'reussi' || $paiement->montant_restant > 0) {
            return null;
        }

        try {
            DB::beginTransaction();

            $commissions = [];

            // RÉSERVATION
            if ($paiement->reservation_id) {
                $commissions[] = $this->creerCommissionReservation($paiement);
            }

            // VENTE
            elseif ($paiement->vente_id && $paiement->type === 'vente') {
                $commissions[] = $this->creerCommissionVente($paiement);
            }

            // LOCATION - PAIEMENT INITIAL (Caution + 1er mois)
            elseif ($paiement->location_id && $paiement->type === 'location') {
                $commissions = $this->creerCommissionsLocationInitiale($paiement);
            }

            // LOCATION - LOYER MENSUEL (à partir du 2ème mois)
            elseif ($paiement->location_id && $paiement->type === 'loyer_mensuel') {
                $commissions[] = $this->creerCommissionLoyerMensuel($paiement);
            }

            DB::commit();

            Log::info('✅ Commissions créées avec succès', [
                'paiement_id' => $paiement->id,
                'type' => $paiement->type,
                'nombre_commissions' => count($commissions)
            ]);

            return $commissions;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur création commissions', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer commission pour réservation
     */
    private function creerCommissionReservation(Paiement $paiement)
    {
        $reservation = $paiement->reservation()->with('bien.mandat')->first();
        $bien = $reservation->bien;

        if ($bien->mandat->type_mandat === 'vente') {
            // VENTE : 10% d'acompte → 100% pour l'agence
            return Commission::create([
                'type' => 'reservation_vente',
                'commissionable_type' => 'App\Models\Reservation',
                'commissionable_id' => $reservation->id,
                'bien_id' => $bien->id,
                'mois_concerne' => now(),
                'montant_base' => $paiement->montant_total,
                'taux_commission' => 100.00,
                'montant_commission' => $paiement->montant_total,
                'montant_net_proprietaire' => 0,
                'statut' => 'payee',
                'date_paiement' => now(),
                'paiement_id' => $paiement->id,
                'notes' => 'Acompte de réservation (10% du prix) - 100% agence'
            ]);
        } else {
            // LOCATION : Dépôt de garantie → 100% AGENCE (pour maintenance)
            return Commission::create([
                'type' => 'reservation_location',
                'commissionable_type' => 'App\Models\Reservation',
                'commissionable_id' => $reservation->id,
                'bien_id' => $bien->id,
                'mois_concerne' => now(),
                'montant_base' => $paiement->montant_total,
                'taux_commission' => 100.00,
                'montant_commission' => $paiement->montant_total,
                'montant_net_proprietaire' => 0,
                'statut' => 'payee',
                'date_paiement' => now(),
                'paiement_id' => $paiement->id,
                'notes' => 'Dépôt de garantie (1 mois) - 100% agence (pour maintenance)'
            ]);
        }
    }

    /**
     * Créer commission pour vente (90% restants)
     */
    private function creerCommissionVente(Paiement $paiement)
    {
        $vente = $paiement->vente()->with('reservation.bien')->first();
        $bien = $vente->reservation->bien;

        $montantBase = $paiement->montant_total; // 90% du prix
        $repartition = Commission::calculerRepartition($montantBase);

        return Commission::create([
            'type' => 'vente',
            'commissionable_type' => 'App\Models\Vente',
            'commissionable_id' => $vente->id,
            'bien_id' => $bien->id,
            'mois_concerne' => now(),
            'montant_base' => $montantBase,
            'taux_commission' => 10.00,
            'montant_commission' => $repartition['montant_commission'],
            'montant_net_proprietaire' => $repartition['montant_net_proprietaire'],
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => 'Solde vente (90%) - 10% agence + 90% vendeur'
        ]);
    }

    /**
     * Créer commissions pour location initiale
     * ✅ CORRECTION : Caution 100% agence + 1er mois 50%-50%
     */
    private function creerCommissionsLocationInitiale(Paiement $paiement)
    {
        $location = $paiement->location()->with('reservation.bien')->first();
        $bien = $location->reservation->bien;
        $loyerMensuel = $location->loyer_mensuel;

        $commissions = [];

        // 1. CAUTION LOCATIVE (1 mois) → 100% AGENCE
        $commissions[] = Commission::create([
            'type' => 'location',
            'commissionable_type' => 'App\Models\Location',
            'commissionable_id' => $location->id,
            'bien_id' => $bien->id,
            'mois_concerne' => $location->date_debut,
            'mois_numero' => 0, // Mois 0 = caution
            'montant_base' => $loyerMensuel,
            'taux_commission' => 100.00,
            'montant_commission' => $loyerMensuel,
            'montant_net_proprietaire' => 0,
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => 'Caution locative (1 mois) - 100% agence (services)'
        ]);

        // 2. PREMIER MOIS DE LOYER → 50% agence + 50% bailleur
        $commissions[] = Commission::create([
            'type' => 'location',
            'commissionable_type' => 'App\Models\Location',
            'commissionable_id' => $location->id,
            'bien_id' => $bien->id,
            'mois_concerne' => $location->date_debut,
            'mois_numero' => 1, // Premier mois
            'montant_base' => $loyerMensuel,
            'taux_commission' => 50.00,
            'montant_commission' => $loyerMensuel / 2,
            'montant_net_proprietaire' => $loyerMensuel / 2,
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => 'Premier mois de loyer - 50% agence + 50% bailleur'
        ]);

        return $commissions;
    }

    /**
     * Créer commission pour loyer mensuel (à partir du 2ème mois)
     * ✅ CORRECTION : Répartition 10%-90% uniquement à partir du 2ème mois
     */
    private function creerCommissionLoyerMensuel(Paiement $paiement)
    {
        $location = $paiement->location()->with('reservation.bien')->first();
        $bien = $location->reservation->bien;

        $moisConcerne = Carbon::parse($paiement->created_at)->startOfMonth();
        $dateDebut = Carbon::parse($location->date_debut)->startOfMonth();
        $moisNumero = $dateDebut->diffInMonths($moisConcerne) + 1;

        $repartition = Commission::calculerRepartition($location->loyer_mensuel);

        return Commission::create([
            'type' => 'location',
            'commissionable_type' => 'App\Models\Location',
            'commissionable_id' => $location->id,
            'bien_id' => $bien->id,
            'mois_concerne' => $moisConcerne,
            'mois_numero' => $moisNumero,
            'montant_base' => $location->loyer_mensuel,
            'taux_commission' => 10.00,
            'montant_commission' => $repartition['montant_commission'],
            'montant_net_proprietaire' => $repartition['montant_net_proprietaire'],
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => "Loyer mois {$moisNumero} - 10% agence + 90% bailleur"
        ]);
    }

    /**
     * Obtenir le récapitulatif des commissions pour une location
     */
    public function getRecapitulatifLocation(Location $location)
    {
        $commissions = Commission::where('commissionable_type', 'App\Models\Location')
            ->where('commissionable_id', $location->id)
            ->orderBy('mois_numero')
            ->get();

        return [
            'location_id' => $location->id,
            'loyer_mensuel' => $location->loyer_mensuel,
            'date_debut' => $location->date_debut,
            'date_fin' => $location->date_fin,
            'total_commissions_agence' => $commissions->sum('montant_commission'),
            'total_net_bailleur' => $commissions->sum('montant_net_proprietaire'),
            'commissions' => $commissions,
        ];
    }

    /**
     * Obtenir les commissions à venir pour une location
     */
    public function getCommissionsAVenir(Location $location, int $nombreMois = 3)
    {
        $commissionsPrevisionnelles = [];
        $dateDebut = Carbon::parse($location->date_debut)->startOfMonth();
        $dateCourante = now()->startOfMonth();

        for ($i = 1; $i <= $nombreMois; $i++) {
            $moisFutur = $dateCourante->copy()->addMonths($i);

            if ($moisFutur->lte(Carbon::parse($location->date_fin))) {
                $moisNumero = $dateDebut->diffInMonths($moisFutur) + 1;
                $repartition = Commission::calculerRepartition($location->loyer_mensuel);

                $commissionsPrevisionnelles[] = [
                    'mois_numero' => $moisNumero,
                    'mois_concerne' => $moisFutur->format('Y-m'),
                    'montant_base' => $location->loyer_mensuel,
                    'montant_commission' => $repartition['montant_commission'],
                    'montant_net_proprietaire' => $repartition['montant_net_proprietaire'],
                ];
            }
        }

        return $commissionsPrevisionnelles;
    }

    /**
     * Obtenir la commission du mois courant pour une location
     */
    public function getCommissionMoisCourant(Location $location, Carbon $date = null)
    {
        $date = $date ?? now();

        return Commission::where('commissionable_type', 'App\Models\Location')
            ->where('commissionable_id', $location->id)
            ->whereYear('mois_concerne', $date->year)
            ->whereMonth('mois_concerne', $date->month)
            ->first();
    }

    /**
     * Générer les commissions de renouvellement pour une location
     */
    public function genererCommissionsRenouvellement(Location $location, int $nombreMois)
    {
        $commissionsGenerees = [];
        $dateDebut = Carbon::parse($location->date_debut)->startOfMonth();
        $dateFin = Carbon::parse($location->date_fin)->startOfMonth();

        for ($i = 1; $i <= $nombreMois; $i++) {
            $moisConcerne = $dateFin->copy()->addMonths($i);
            $moisNumero = $dateDebut->diffInMonths($moisConcerne) + 1;

            // Vérifier si la commission n'existe pas déjà
            $existe = Commission::where('commissionable_type', 'App\Models\Location')
                ->where('commissionable_id', $location->id)
                ->where('mois_numero', $moisNumero)
                ->exists();

            if (!$existe) {
                $repartition = Commission::calculerRepartition($location->loyer_mensuel);

                $commission = Commission::create([
                    'type' => 'location',
                    'commissionable_type' => 'App\Models\Location',
                    'commissionable_id' => $location->id,
                    'bien_id' => $location->reservation->bien_id,
                    'mois_concerne' => $moisConcerne,
                    'mois_numero' => $moisNumero,
                    'montant_base' => $location->loyer_mensuel,
                    'taux_commission' => 10.00,
                    'montant_commission' => $repartition['montant_commission'],
                    'montant_net_proprietaire' => $repartition['montant_net_proprietaire'],
                    'statut' => 'en_attente',
                    'notes' => "Renouvellement - Mois {$moisNumero}"
                ]);

                $commissionsGenerees[] = $commission;
            }
        }

        return $commissionsGenerees;
    }
}
