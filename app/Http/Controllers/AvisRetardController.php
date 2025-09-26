<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\AvisRetard;
use App\Models\User;
use App\Notifications\RappelPaiementLoyer;
use App\Notifications\AvisRetardPaiement;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class AvisRetardController extends Controller
{
    /**
     * Afficher le tableau de bord des avis de retard
     */
    public function index()
    {
        $avisRetards = AvisRetard::with(['location.bien', 'location.client'])
            ->latest()
            ->paginate(20);

        return Inertia::render('AvisRetard/Index', [
            'avisRetards' => $avisRetards,
            'statistiques' => $this->getStatistiques()
        ]);
    }

    /**
     * Envoyer les rappels de paiement (J-5)
     */
    public function envoyerRappels()
    {
        $dateRappel = Carbon::today()->addDays(5);

        // Récupérer toutes les locations actives
        $locations = Location::with(['client', 'bien.proprietaire'])
            ->where('statut', 'active')
            ->get();

        $rappelsEnvoyes = 0;

        foreach ($locations as $location) {
            $prochaineDatePaiement = $this->calculerProchaineDatePaiement($location);

            if ($prochaineDatePaiement && $prochaineDatePaiement->isSameDay($dateRappel)) {
                // Vérifier si un rappel n'a pas déjà été envoyé pour cette période
                if (!$this->rappelDejaEnvoye($location, $prochaineDatePaiement)) {
                    $this->envoyerRappelPaiement($location, $prochaineDatePaiement);
                    $rappelsEnvoyes++;
                }
            }
        }

        Log::info("Rappels de paiement envoyés: {$rappelsEnvoyes}");

        return response()->json([
            'success' => true,
            'message' => "{$rappelsEnvoyes} rappel(s) de paiement envoyé(s)",
            'rappels_envoyes' => $rappelsEnvoyes
        ]);
    }

    /**
     * Envoyer les avis de retard
     */
    public function envoyerAvisRetards()
    {
        $dateAujourdhui = Carbon::today();

        // Récupérer toutes les locations actives
        $locations = Location::with(['client', 'bien.proprietaire'])
            ->where('statut', 'active')
            ->get();

        $avisEnvoyes = 0;

        foreach ($locations as $location) {
            $derniereDatePaiement = $this->calculerDerniereDatePaiement($location);

            if ($derniereDatePaiement && $dateAujourdhui->isAfter($derniereDatePaiement)) {
                $joursRetard = $dateAujourdhui->diffInDays($derniereDatePaiement);

                // Vérifier si un avis n'a pas déjà été envoyé pour cette période
                if (!$this->avisRetardDejaEnvoye($location, $derniereDatePaiement)) {
                    $this->creerEtEnvoyerAvisRetard($location, $derniereDatePaiement, $joursRetard);
                    $avisEnvoyes++;
                }
            }
        }

        Log::info("Avis de retard envoyés: {$avisEnvoyes}");

        return response()->json([
            'success' => true,
            'message' => "{$avisEnvoyes} avis de retard envoyé(s)",
            'avis_envoyes' => $avisEnvoyes
        ]);
    }

    /**
     * Traiter les notifications automatiquement
     */
    public function traiterNotificationsAutomatiques()
    {
        try {
            DB::beginTransaction();

            $rappelsResult = $this->envoyerRappels();
            $avisResult = $this->envoyerAvisRetards();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Notifications traitées avec succès',
                'rappels' => $rappelsResult->original['rappels_envoyes'],
                'avis' => $avisResult->original['avis_envoyes']
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Erreur lors du traitement des notifications: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement des notifications'
            ], 500);
        }
    }

    /**
     * Calculer la prochaine date de paiement pour une location
     */
    private function calculerProchaineDatePaiement(Location $location)
    {
        $dateDebut = Carbon::parse($location->date_debut);
        $maintenant = Carbon::now();

        // Calculer le nombre de mois écoulés depuis le début de la location
        $moisEcoules = $dateDebut->diffInMonths($maintenant);

        // La prochaine échéance est le même jour du mois suivant
        return $dateDebut->copy()->addMonths($moisEcoules + 1);
    }

    /**
     * Calculer la dernière date de paiement attendue
     */
    private function calculerDerniereDatePaiement(Location $location)
    {
        $dateDebut = Carbon::parse($location->date_debut);
        $maintenant = Carbon::now();

        // Calculer le nombre de mois écoulés depuis le début
        $moisEcoules = $dateDebut->diffInMonths($maintenant);

        // Si on est passé le jour de paiement du mois en cours
        $jourPaiement = $dateDebut->day;
        $paiementMoisCourant = $maintenant->copy()->day($jourPaiement);

        if ($maintenant->isAfter($paiementMoisCourant)) {
            return $paiementMoisCourant;
        } else {
            // Le paiement du mois précédent
            return $dateDebut->copy()->addMonths($moisEcoules);
        }
    }

    /**
     * Vérifier si un rappel a déjà été envoyé
     */
    private function rappelDejaEnvoye(Location $location, Carbon $datePaiement)
    {
        return AvisRetard::where('location_id', $location->id)
            ->where('type', 'rappel')
            ->where('date_echeance', $datePaiement->format('Y-m-d'))
            ->exists();
    }

    /**
     * Vérifier si un avis de retard a déjà été envoyé
     */
    private function avisRetardDejaEnvoye(Location $location, Carbon $datePaiement)
    {
        return AvisRetard::where('location_id', $location->id)
            ->where('type', 'retard')
            ->where('date_echeance', $datePaiement->format('Y-m-d'))
            ->exists();
    }

    /**
     * Envoyer un rappel de paiement
     */
    private function envoyerRappelPaiement(Location $location, Carbon $datePaiement)
    {
        // Créer l'enregistrement du rappel
        $avis = AvisRetard::create([
            'location_id' => $location->id,
            'type' => 'rappel',
            'date_echeance' => $datePaiement,
            'montant_du' => $location->loyer_mensuel,
            'statut' => 'envoye',
            'date_envoi' => now()
        ]);

        // Envoyer les notifications
        $location->client->notify(new RappelPaiementLoyer($location, $datePaiement));

        // Notifier aussi le propriétaire
        if ($location->bien->proprietaire) {
            $location->bien->proprietaire->notify(new RappelPaiementLoyer($location, $datePaiement, true));
        }

        Log::info("Rappel de paiement envoyé", [
            'location_id' => $location->id,
            'client_id' => $location->client_id,
            'date_echeance' => $datePaiement->format('Y-m-d')
        ]);
    }

    /**
     * Créer et envoyer un avis de retard
     */
    private function creerEtEnvoyerAvisRetard(Location $location, Carbon $datePaiement, int $joursRetard)
    {
        // Créer l'enregistrement de l'avis de retard
        $avis = AvisRetard::create([
            'location_id' => $location->id,
            'type' => 'retard',
            'date_echeance' => $datePaiement,
            'montant_du' => $location->loyer_mensuel,
            'jours_retard' => $joursRetard,
            'statut' => 'envoye',
            'date_envoi' => now()
        ]);

        // Envoyer les notifications
        $location->client->notify(new AvisRetardPaiement($location, $datePaiement, $joursRetard));

        // Notifier aussi le propriétaire
        if ($location->bien->proprietaire) {
            $location->bien->proprietaire->notify(new AvisRetardPaiement($location, $datePaiement, $joursRetard, true));
        }

        Log::info("Avis de retard envoyé", [
            'location_id' => $location->id,
            'client_id' => $location->client_id,
            'jours_retard' => $joursRetard,
            'montant' => $location->loyer_mensuel
        ]);
    }

    /**
     * Obtenir les statistiques des avis
     */
    private function getStatistiques()
    {
        return [
            'total_avis' => AvisRetard::count(),
            'rappels_envoyes' => AvisRetard::where('type', 'rappel')->count(),
            'avis_retard' => AvisRetard::where('type', 'retard')->count(),
            'en_attente_paiement' => AvisRetard::where('statut', 'envoye')->count(),
            'payes' => AvisRetard::where('statut', 'paye')->count(),
        ];
    }

    /**
     * Marquer un avis comme payé
     */
    public function marquerPaye(AvisRetard $avisRetard)
    {
        $avisRetard->update([
            'statut' => 'paye',
            'date_paiement' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avis marqué comme payé'
        ]);
    }

    /**
     * Afficher les détails d'un avis
     */
    public function show(AvisRetard $avisRetard)
    {
        $avisRetard->load(['location.bien', 'location.client']);

        return Inertia::render('AvisRetard/Show', [
            'avisRetard' => $avisRetard
        ]);
    }
}
