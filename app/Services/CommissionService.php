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
            Log::warning('⚠️ Paiement non éligible aux commissions', [
                'paiement_id' => $paiement->id,
                'statut' => $paiement->statut,
                'montant_restant' => $paiement->montant_restant
            ]);
            return null;
        }

        try {
            DB::beginTransaction();

            $commissions = [];

            // 1️⃣ RÉSERVATION (Acompte vente OU Dépôt garantie location)
            if ($paiement->reservation_id) {
                $commissions[] = $this->creerCommissionReservation($paiement);
            }

            // 2️⃣ VENTE (90% restants)
            elseif ($paiement->vente_id && $paiement->type === 'vente') {
                $commissions[] = $this->creerCommissionVente($paiement);
            }

            // 3️⃣ LOCATION - PAIEMENT INITIAL (Caution + 1er mois)
            elseif ($paiement->location_id && $paiement->type === 'location') {
                $commissions = $this->creerCommissionsLocationInitiale($paiement);
            }

            // 4️⃣ LOCATION - LOYER MENSUEL (2ème mois et +)
            elseif ($paiement->location_id && $paiement->type === 'loyer_mensuel') {
                $commissions[] = $this->creerCommissionLoyerMensuel($paiement);
            }

            DB::commit();

            Log::info('✅ Commissions créées avec succès', [
                'paiement_id' => $paiement->id,
                'type' => $paiement->type,
                'nombre_commissions' => count($commissions),
                'total_agence' => collect($commissions)->sum('montant_commission'),
                'total_proprio' => collect($commissions)->sum('montant_net_proprietaire')
            ]);

            return $commissions;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur création commissions', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 📌 RÉSERVATION : Acompte vente (10%) OU Dépôt garantie location (1 mois)
     * ✅ RÈGLE : 100% pour l'agence dans les deux cas
     */
    private function creerCommissionReservation(Paiement $paiement)
    {
        $reservation = $paiement->reservation()->with('bien.mandat')->first();
        $bien = $reservation->bien;

        if ($bien->mandat->type_mandat === 'vente') {
            // ✅ VENTE : Acompte 10% = Commission COMPLÈTE de l'agence
            $commission = Commission::create([
                'type' => 'reservation_vente',
                'commissionable_type' => 'App\Models\Reservation',
                'commissionable_id' => $reservation->id,
                'bien_id' => $bien->id,
                'mois_concerne' => now(),
                'montant_base' => $bien->price, // 6 000 000 (prix total)
                'taux_commission' => 10.00, // 10%
                'montant_commission' => $paiement->montant_total, // 600 000 → AGENCE
                'montant_net_proprietaire' => 0, // 0 → PROPRIO (il aura tout au solde)
                'statut' => 'payee',
                'date_paiement' => now(),
                'paiement_id' => $paiement->id,
                'notes' => 'Commission agence (10% du prix) prélevée sur acompte - Vendeur recevra 90% au solde'
            ]);

            Log::info('💰 Commission ACOMPTE VENTE créée', [
                'prix_bien' => $bien->price,
                'acompte_10%' => $paiement->montant_total,
                'agence_reçoit' => $paiement->montant_total,
                'proprio_reçoit' => 0,
                'notes' => 'Commission complète agence = acompte'
            ]);

            return $commission;
        } else {
            // LOCATION : Dépôt de garantie (1 mois) → 100% agence (maintenance)
            $commission = Commission::create([
                'type' => 'reservation_location',
                'commissionable_type' => 'App\Models\Reservation',
                'commissionable_id' => $reservation->id,
                'bien_id' => $bien->id,
                'mois_concerne' => now(),
                'montant_base' => $paiement->montant_total,
                'taux_commission' => 100.00,
                'montant_commission' => $paiement->montant_total, // 100%
                'montant_net_proprietaire' => 0,
                'statut' => 'payee',
                'date_paiement' => now(),
                'paiement_id' => $paiement->id,
                'notes' => 'Dépôt de garantie location (1 mois) - 100% agence (maintenance)'
            ]);

            Log::info('💰 Commission réservation LOCATION créée', [
                'reservation_id' => $reservation->id,
                'montant_base' => $paiement->montant_total,
                'agence_100%' => $paiement->montant_total,
                'proprio_0%' => 0
            ]);

            return $commission;
        }
    }

    private function creerCommissionVente(Paiement $paiement)
    {
        $vente = $paiement->vente()->with('reservation.bien')->first();
        $bien = $vente->reservation->bien;

        // ✅ Solde = 100% pour le vendeur (la commission a déjà été prise sur l'acompte)
        $commission = Commission::create([
            'type' => 'vente',
            'commissionable_type' => 'App\Models\Vente',
            'commissionable_id' => $vente->id,
            'bien_id' => $bien->id,
            'mois_concerne' => now(),
            'montant_base' => $paiement->montant_total, // 5 400 000
            'taux_commission' => 0.00, // 0% (commission déjà prélevée)
            'montant_commission' => 0, // 0 → AGENCE
            'montant_net_proprietaire' => $paiement->montant_total, // 5 400 000 → VENDEUR
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => 'Solde vente (90% du prix) - 100% reversé au vendeur, commission agence déjà encaissée'
        ]);

        Log::info('💰 Commission SOLDE VENTE créée', [
            'solde_90%' => $paiement->montant_total,
            'agence_reçoit' => 0,
            'vendeur_reçoit' => $paiement->montant_total,
            'notes' => 'Commission déjà encaissée lors de l\'acompte'
        ]);

        return $commission;
    }

    /**
     * 📌 LOCATION INITIALE : Caution + 1er mois
     * ✅ RÈGLE :
     *   - Caution locative (1 loyer) → 100% agence
     *   - 1er mois de loyer → 50% agence + 50% bailleur
     */
    private function creerCommissionsLocationInitiale(Paiement $paiement)
    {
        $location = $paiement->location()->with('reservation.bien')->first();
        $bien = $location->reservation->bien;
        $loyerMensuel = $location->loyer_mensuel;

        $commissions = [];

        // ✅ 1️⃣ CAUTION LOCATIVE (1 mois) → 100% AGENCE
        $commissions[] = Commission::create([
            'type' => 'location',
            'commissionable_type' => 'App\Models\Location',
            'commissionable_id' => $location->id,
            'bien_id' => $bien->id,
            'mois_concerne' => $location->date_debut,
            'mois_numero' => 0,
            'montant_base' => $loyerMensuel,
            'taux_commission' => 100.00,
            'montant_commission' => $loyerMensuel, // 100%
            'montant_net_proprietaire' => 0,
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => 'Caution locative - 100% agence'
        ]);

        // ✅ 2️⃣ PREMIER MOIS → 50% agence + 50% bailleur
        $commissions[] = Commission::create([
            'type' => 'location',
            'commissionable_type' => 'App\Models\Location',
            'commissionable_id' => $location->id,
            'bien_id' => $bien->id,
            'mois_concerne' => $location->date_debut,
            'mois_numero' => 1,
            'montant_base' => $loyerMensuel,
            'taux_commission' => 50.00,
            'montant_commission' => $loyerMensuel / 2, // 50%
            'montant_net_proprietaire' => $loyerMensuel / 2, // 50%
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => '1er mois de loyer - 50% agence + 50% bailleur'
        ]);

        Log::info('💰 Commissions LOCATION INITIALE créées', [
            'location_id' => $location->id,
            'caution_agence_100%' => $loyerMensuel,
            '1er_mois_agence_50%' => $loyerMensuel / 2,
            '1er_mois_bailleur_50%' => $loyerMensuel / 2,
            'total_agence' => $loyerMensuel + ($loyerMensuel / 2),
            'total_bailleur' => $loyerMensuel / 2
        ]);

        return $commissions;
    }

    /**
     * 📌 LOYER MENSUEL (à partir du 2ème mois)
     * ✅ RÈGLE : 10% agence + 90% bailleur
     */
    private function creerCommissionLoyerMensuel(Paiement $paiement)
    {
        $location = $paiement->location()->with('reservation.bien')->first();
        $bien = $location->reservation->bien;

        $moisConcerne = Carbon::parse($paiement->created_at)->startOfMonth();
        $dateDebut = Carbon::parse($location->date_debut)->startOfMonth();
        $moisNumero = $dateDebut->diffInMonths($moisConcerne) + 1;

        $repartition = Commission::calculerRepartition($location->loyer_mensuel);

        $commission = Commission::create([
            'type' => 'location',
            'commissionable_type' => 'App\Models\Location',
            'commissionable_id' => $location->id,
            'bien_id' => $bien->id,
            'mois_concerne' => $moisConcerne,
            'mois_numero' => $moisNumero,
            'montant_base' => $location->loyer_mensuel,
            'taux_commission' => 10.00,
            'montant_commission' => $repartition['montant_commission'], // 10%
            'montant_net_proprietaire' => $repartition['montant_net_proprietaire'], // 90%
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiement->id,
            'notes' => "Loyer mois {$moisNumero} - 10% agence + 90% bailleur"
        ]);

        Log::info('💰 Commission LOYER MENSUEL créée', [
            'location_id' => $location->id,
            'mois_numero' => $moisNumero,
            'montant_base' => $location->loyer_mensuel,
            'agence_10%' => $repartition['montant_commission'],
            'bailleur_90%' => $repartition['montant_net_proprietaire']
        ]);

        return $commission;
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
