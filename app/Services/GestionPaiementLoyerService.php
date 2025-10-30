<?php

namespace App\Services;

use App\Models\Location;
use App\Models\AvisRetard;
use App\Models\PaiementLoyer;
use App\Notifications\RappelPaiementLoyer;
use App\Notifications\AvisRetardPaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GestionPaiementLoyerService
{
    // Constantes de l'agence
    const JOUR_ECHEANCE_MENSUELLE = 1; // Le 1er de chaque mois
    const JOURS_DELAI_PAIEMENT = 10; // 10 jours pour payer
    const JOUR_LIMITE_PAIEMENT = 10; // Date limite = 10 du mois
    const TAUX_PENALITE = 0.02; // 2% du loyer
    const JOURS_AVANT_RAPPEL = 5; // Rappel 5 jours avant la date limite

    /**
     * Vérifier et envoyer les rappels de paiement (J-5 avant la date limite)
     * Rappel envoyé le 5 du mois pour paiement dû le 10
     */
    public function envoyerRappelsMensuels()
    {
        $aujourdhui = Carbon::today();
        $jourDuMois = $aujourdhui->day;

        // Envoyer rappels uniquement le 5 du mois (5 jours avant la date limite du 10)
        if ($jourDuMois !== (self::JOUR_LIMITE_PAIEMENT - self::JOURS_AVANT_RAPPEL)) {
            return [
                'success' => true,
                'message' => 'Rappels envoyés uniquement le ' . (self::JOUR_LIMITE_PAIEMENT - self::JOURS_AVANT_RAPPEL) . ' du mois',
                'rappels_envoyes' => 0
            ];
        }

        $moisConcerne = $aujourdhui->format('Y-m');
        $dateEcheance = Carbon::parse($moisConcerne . '-01'); // Le 1er du mois
        $dateLimitePaiement = Carbon::parse($moisConcerne . '-' . self::JOUR_LIMITE_PAIEMENT);

        $locations = Location::with(['client', 'bien.proprietaire'])
            ->where('statut', 'active')
            ->get();

        $rappelsEnvoyes = 0;

        foreach ($locations as $location) {
            // Vérifier si le loyer du mois n'a pas déjà été payé
            if (!$this->loyerMoisPaye($location, $moisConcerne)) {
                // Vérifier si rappel pas déjà envoyé pour ce mois
                if (!$this->rappelDejaEnvoye($location, $moisConcerne)) {
                    $this->creerEtEnvoyerRappel($location, $dateEcheance, $dateLimitePaiement);
                    $rappelsEnvoyes++;
                }
            }
        }

        Log::info("Rappels de paiement envoyés", [
            'date' => $aujourdhui->format('Y-m-d'),
            'mois_concerne' => $moisConcerne,
            'rappels_envoyes' => $rappelsEnvoyes
        ]);

        return [
            'success' => true,
            'message' => "{$rappelsEnvoyes} rappel(s) envoyé(s) pour le mois de " . $dateEcheance->translatedFormat('F Y'),
            'rappels_envoyes' => $rappelsEnvoyes
        ];
    }

    /**
     * Vérifier et envoyer les avis de retard (à partir du 11 du mois)
     * Le client a jusqu'au 10 pour payer, donc retard commence le 11
     */
    public function envoyerAvisRetards()
    {
        $aujourdhui = Carbon::today();
        $jourDuMois = $aujourdhui->day;

        // Avis de retard uniquement après le 10 du mois
        if ($jourDuMois <= self::JOUR_LIMITE_PAIEMENT) {
            return [
                'success' => true,
                'message' => 'Pas de retard avant le ' . (self::JOUR_LIMITE_PAIEMENT + 1) . ' du mois',
                'avis_envoyes' => 0
            ];
        }

        $moisConcerne = $aujourdhui->format('Y-m');
        $dateEcheance = Carbon::parse($moisConcerne . '-01');
        $dateLimitePaiement = Carbon::parse($moisConcerne . '-' . self::JOUR_LIMITE_PAIEMENT);
        $joursRetard = $aujourdhui->diffInDays($dateLimitePaiement);

        $locations = Location::with(['client', 'bien.proprietaire'])
            ->where('statut', 'active')
            ->get();

        $avisEnvoyes = 0;

        foreach ($locations as $location) {
            // Vérifier si le loyer du mois n'a pas été payé complètement
            $montantRestant = $this->calculerMontantRestant($location, $moisConcerne);

            if ($montantRestant > 0) {
                // Vérifier si avis pas déjà envoyé pour ce mois
                if (!$this->avisRetardDejaEnvoye($location, $moisConcerne)) {
                    $this->creerEtEnvoyerAvisRetard($location, $dateEcheance, $joursRetard, $montantRestant);
                    $avisEnvoyes++;
                }
            }
        }

        Log::info("Avis de retard envoyés", [
            'date' => $aujourdhui->format('Y-m-d'),
            'mois_concerne' => $moisConcerne,
            'jours_retard' => $joursRetard,
            'avis_envoyes' => $avisEnvoyes
        ]);

        return [
            'success' => true,
            'message' => "{$avisEnvoyes} avis de retard envoyé(s)",
            'avis_envoyes' => $avisEnvoyes,
            'jours_retard' => $joursRetard
        ];
    }

    /**
     * Vérifier si le loyer du mois est payé (supporte les paiements multiples)
     */
    private function loyerMoisPaye(Location $location, string $moisConcerne): bool
    {
        $montantPaye = PaiementLoyer::where('location_id', $location->id)
            ->where('mois_concerne', $moisConcerne)
            ->where('statut', 'valide')
            ->sum('montant');

        return $montantPaye >= $location->loyer_mensuel;
    }

    /**
     * Calculer le montant restant à payer pour le mois (supporte paiements partiels)
     */
    private function calculerMontantRestant(Location $location, string $moisConcerne): float
    {
        $montantPaye = PaiementLoyer::where('location_id', $location->id)
            ->where('mois_concerne', $moisConcerne)
            ->where('statut', 'valide')
            ->sum('montant');

        $montantRestant = $location->loyer_mensuel - $montantPaye;

        return max(0, $montantRestant);
    }

    /**
     * Calculer les pénalités de retard (2% fixe du loyer)
     */
    public function calculerPenalites(float $loyerMensuel): float
    {
        return $loyerMensuel * self::TAUX_PENALITE;
    }

    /**
     * Vérifier si un rappel a déjà été envoyé pour le mois
     */
    private function rappelDejaEnvoye(Location $location, string $moisConcerne): bool
    {
        return AvisRetard::where('location_id', $location->id)
            ->where('type', 'rappel')
            ->where('mois_concerne', $moisConcerne)
            ->exists();
    }

    /**
     * Vérifier si un avis de retard a déjà été envoyé pour le mois
     */
    private function avisRetardDejaEnvoye(Location $location, string $moisConcerne): bool
    {
        return AvisRetard::where('location_id', $location->id)
            ->where('type', 'retard')
            ->where('mois_concerne', $moisConcerne)
            ->exists();
    }

    /**
     * Créer et envoyer un rappel de paiement
     */
    private function creerEtEnvoyerRappel(Location $location, Carbon $dateEcheance, Carbon $dateLimite)
    {
        $avis = AvisRetard::create([
            'location_id' => $location->id,
            'type' => 'rappel',
            'mois_concerne' => $dateEcheance->format('Y-m'),
            'date_echeance' => $dateEcheance,
            'date_limite_paiement' => $dateLimite,
            'montant_du' => $location->loyer_mensuel,
            'penalites' => 0, // Pas de pénalité pour un rappel
            'statut' => 'envoye',
            'date_envoi' => now()
        ]);

        // Envoyer notification au client
        $location->client->notify(new RappelPaiementLoyer($location, $dateLimite));

        // Notifier le propriétaire
        if ($location->bien->proprietaire) {
            $location->bien->proprietaire->notify(
                new RappelPaiementLoyer($location, $dateLimite, true)
            );
        }

        Log::info("Rappel de paiement créé", [
            'avis_id' => $avis->id,
            'location_id' => $location->id,
            'mois_concerne' => $dateEcheance->format('Y-m'),
            'montant' => $location->loyer_mensuel
        ]);
    }

    /**
     * Créer et envoyer un avis de retard avec pénalités
     */
    private function creerEtEnvoyerAvisRetard(Location $location, Carbon $dateEcheance, int $joursRetard, float $montantRestant)
    {
        $penalites = $this->calculerPenalites($location->loyer_mensuel);
        $montantTotal = $montantRestant + $penalites;

        $avis = AvisRetard::create([
            'location_id' => $location->id,
            'type' => 'retard',
            'mois_concerne' => $dateEcheance->format('Y-m'),
            'date_echeance' => $dateEcheance,
            'date_limite_paiement' => Carbon::parse($dateEcheance->format('Y-m') . '-' . self::JOUR_LIMITE_PAIEMENT),
            'montant_du' => $montantRestant,
            'penalites' => $penalites,
            'montant_total' => $montantTotal,
            'jours_retard' => $joursRetard,
            'statut' => 'envoye',
            'date_envoi' => now(),
            'commentaires' => "Retard de {$joursRetard} jour(s). Pénalité de 2% appliquée."
        ]);

        // Envoyer notification au client
        $location->client->notify(
            new AvisRetardPaiement($location, $dateEcheance, $joursRetard, $penalites, $montantTotal)
        );

        // Notifier le propriétaire
        if ($location->bien->proprietaire) {
            $location->bien->proprietaire->notify(
                new AvisRetardPaiement($location, $dateEcheance, $joursRetard, $penalites, $montantTotal, true)
            );
        }

        Log::info("Avis de retard créé", [
            'avis_id' => $avis->id,
            'location_id' => $location->id,
            'mois_concerne' => $dateEcheance->format('Y-m'),
            'montant_restant' => $montantRestant,
            'penalites' => $penalites,
            'montant_total' => $montantTotal,
            'jours_retard' => $joursRetard
        ]);
    }

    /**
     * Enregistrer un paiement (peut être partiel)
     */
    public function enregistrerPaiement(Location $location, float $montant, string $modePaiement, string $reference = null)
    {
        $moisConcerne = Carbon::now()->format('Y-m');
        $montantRestant = $this->calculerMontantRestant($location, $moisConcerne);

        // Vérifier si en retard pour ajouter les pénalités
        $aujourdhui = Carbon::today();
        $enRetard = $aujourdhui->day > self::JOUR_LIMITE_PAIEMENT;
        $penalites = $enRetard ? $this->calculerPenalites($location->loyer_mensuel) : 0;

        $paiement = PaiementLoyer::create([
            'location_id' => $location->id,
            'mois_concerne' => $moisConcerne,
            'montant' => $montant,
            'penalites' => $penalites,
            'mode_paiement' => $modePaiement,
            'reference' => $reference,
            'date_paiement' => now(),
            'statut' => 'valide',
            'en_retard' => $enRetard
        ]);

        // Vérifier si le loyer est maintenant totalement payé
        $nouveauMontantRestant = $this->calculerMontantRestant($location, $moisConcerne);

        if ($nouveauMontantRestant <= 0) {
            // Marquer les avis comme payés
            AvisRetard::where('location_id', $location->id)
                ->where('mois_concerne', $moisConcerne)
                ->where('statut', 'envoye')
                ->update([
                    'statut' => 'paye',
                    'date_paiement' => now()
                ]);
        }

        Log::info("Paiement enregistré", [
            'paiement_id' => $paiement->id,
            'location_id' => $location->id,
            'montant' => $montant,
            'penalites' => $penalites,
            'montant_restant' => $nouveauMontantRestant,
            'paiement_complet' => $nouveauMontantRestant <= 0
        ]);

        return [
            'success' => true,
            'paiement' => $paiement,
            'montant_restant' => $nouveauMontantRestant,
            'paiement_complet' => $nouveauMontantRestant <= 0,
            'penalites_appliquees' => $penalites
        ];
    }

    /**
     * Obtenir le récapitulatif des paiements d'une location pour un mois
     */
    public function getRecapitulatifMois(Location $location, string $moisConcerne = null)
    {
        $moisConcerne = $moisConcerne ?? Carbon::now()->format('Y-m');

        $paiements = PaiementLoyer::where('location_id', $location->id)
            ->where('mois_concerne', $moisConcerne)
            ->where('statut', 'valide')
            ->get();

        $montantPaye = $paiements->sum('montant');
        $penalitesPayees = $paiements->sum('penalites');
        $montantRestant = $this->calculerMontantRestant($location, $moisConcerne);

        $aujourdhui = Carbon::today();
        $dateLimite = Carbon::parse($moisConcerne . '-' . self::JOUR_LIMITE_PAIEMENT);
        $enRetard = $aujourdhui->isAfter($dateLimite) && $montantRestant > 0;
        $penalitesAVenir = $enRetard ? $this->calculerPenalites($location->loyer_mensuel) : 0;

        return [
            'mois_concerne' => $moisConcerne,
            'loyer_mensuel' => $location->loyer_mensuel,
            'montant_paye' => $montantPaye,
            'penalites_payees' => $penalitesPayees,
            'montant_restant' => $montantRestant,
            'en_retard' => $enRetard,
            'penalites_a_venir' => $penalitesAVenir,
            'montant_total_du' => $montantRestant + $penalitesAVenir,
            'paiements' => $paiements,
            'paiement_complet' => $montantRestant <= 0,
            'date_limite' => $dateLimite->format('Y-m-d')
        ];
    }
}
