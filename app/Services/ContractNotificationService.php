<?php

namespace App\Services;

use App\Models\Location;
use App\Notifications\RappelPaiementLoyer;
use App\Notifications\AvisRetardPaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class ContractNotificationService
{
    /**
     * Programmer les notifications de test après signature complète du contrat
     */
    public function programmerNotificationsApresSignature(Location $location)
    {
        // Vérifier que le contrat est entièrement signé
        if (!$this->isContratEntierementSigne($location)) {
            Log::info('Contrat pas encore entièrement signé', [
                'location_id' => $location->id,
                'signature_status' => $location->signature_status
            ]);
            return false;
        }

        Log::info('Contrat entièrement signé, programmation des notifications de test', [
            'location_id' => $location->id,
            'bailleur_signed_at' => $location->bailleur_signed_at,
            'locataire_signed_at' => $location->locataire_signed_at
        ]);

        // Programmer rappel dans 5 minutes
        $this->programmerRappelDans5Minutes($location);

        // Programmer avis de retard dans 10 minutes (5 minutes après le rappel)
        $this->programmerAvisRetardDans10Minutes($location);

        return true;
    }

    /**
     * Programmer un rappel de paiement dans 5 minutes
     */
    private function programmerRappelDans5Minutes(Location $location)
    {
        // Date d'échéance fictive pour le test (dans 5 jours par exemple)
        $dateEcheance = Carbon::now()->addDays(5);

        // Programmer l'envoi dans 5 minutes
        $delai = Carbon::now()->addMinutes(5);

        // Utiliser dispatch avec delay
        dispatch(function () use ($location, $dateEcheance) {
            $this->envoyerRappelPaiementTest($location, $dateEcheance);
        })->delay($delai);

        Log::info('Rappel de paiement programmé', [
            'location_id' => $location->id,
            'envoyer_a' => $delai->format('Y-m-d H:i:s'),
            'echeance_test' => $dateEcheance->format('Y-m-d H:i:s')
        ]);
    }

    /**
     * Programmer un avis de retard dans 10 minutes
     */
    private function programmerAvisRetardDans10Minutes(Location $location)
    {
        // Date d'échéance fictive passée pour simuler un retard
        $dateEcheance = Carbon::now()->subDays(7);
        $joursRetard = 7;

        // Programmer l'envoi dans 10 minutes
        $delai = Carbon::now()->addMinutes(10);

        dispatch(function () use ($location, $dateEcheance, $joursRetard) {
            $this->envoyerAvisRetardTest($location, $dateEcheance, $joursRetard);
        })->delay($delai);

        Log::info('Avis de retard programmé', [
            'location_id' => $location->id,
            'envoyer_a' => $delai->format('Y-m-d H:i:s'),
            'jours_retard' => $joursRetard
        ]);
    }

    /**
     * Envoyer le rappel de paiement de test
     */
    private function envoyerRappelPaiementTest(Location $location, Carbon $dateEcheance)
    {
        try {
            // Recharger la location pour avoir les données fraîches
            $location->load(['client', 'bien.proprietaire']);

            // Créer et envoyer la notification au client
            $notificationClient = new RappelPaiementLoyer($location, $dateEcheance, false);
            $location->client->notify($notificationClient);

            Log::info('Test rappel paiement envoyé au client', [
                'location_id' => $location->id,
                'client_email' => $location->client->email,
                'date_echeance' => $dateEcheance->format('Y-m-d H:i:s')
            ]);

            // Envoyer aussi au propriétaire si existe
            if ($location->bien->proprietaire) {
                $notificationProprietaire = new RappelPaiementLoyer($location, $dateEcheance, true);
                $location->bien->proprietaire->notify($notificationProprietaire);

                Log::info('Test rappel paiement envoyé au propriétaire', [
                    'location_id' => $location->id,
                    'proprietaire_email' => $location->bien->proprietaire->email
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur envoi rappel paiement test', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Envoyer l'avis de retard de test
     */
    private function envoyerAvisRetardTest(Location $location, Carbon $dateEcheance, int $joursRetard)
    {
        try {
            // Recharger la location pour avoir les données fraîches
            $location->load(['client', 'bien.proprietaire']);

            // Créer et envoyer la notification au client
            $notificationClient = new AvisRetardPaiement($location, $dateEcheance, $joursRetard, false);
            $location->client->notify($notificationClient);

            Log::info('Test avis de retard envoyé au client', [
                'location_id' => $location->id,
                'client_email' => $location->client->email,
                'jours_retard' => $joursRetard
            ]);

            // Envoyer aussi au propriétaire si existe
            if ($location->bien->proprietaire) {
                $notificationProprietaire = new AvisRetardPaiement($location, $dateEcheance, $joursRetard, true);
                $location->bien->proprietaire->notify($notificationProprietaire);

                Log::info('Test avis de retard envoyé au propriétaire', [
                    'location_id' => $location->id,
                    'proprietaire_email' => $location->bien->proprietaire->email
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Erreur envoi avis retard test', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifier si le contrat est entièrement signé
     */
    private function isContratEntierementSigne(Location $location)
    {
        return $location->signature_status === 'entierement_signe' &&
            !is_null($location->bailleur_signed_at) &&
            !is_null($location->locataire_signed_at) &&
            !is_null($location->bailleur_signature_data) &&
            !is_null($location->locataire_signature_data);
    }

    /**
     * Programmer des notifications personnalisées
     */
    public function programmerNotificationsPersonnalisees(
        Location $location,
        int $delaiRappelMinutes = 5,
        int $delaiAvisMinutes = 10
    ) {
        if (!$this->isContratEntierementSigne($location)) {
            return false;
        }

        // Programmer rappel personnalisé
        $delaiRappel = Carbon::now()->addMinutes($delaiRappelMinutes);
        dispatch(function () use ($location) {
            $dateEcheance = Carbon::now()->addDays(5);
            $this->envoyerRappelPaiementTest($location, $dateEcheance);
        })->delay($delaiRappel);

        // Programmer avis de retard personnalisé
        $delaiAvis = Carbon::now()->addMinutes($delaiAvisMinutes);
        dispatch(function () use ($location) {
            $dateEcheance = Carbon::now()->subDays(7);
            $this->envoyerAvisRetardTest($location, $dateEcheance, 7);
        })->delay($delaiAvis);

        Log::info('Notifications personnalisées programmées', [
            'location_id' => $location->id,
            'rappel_dans' => $delaiRappelMinutes . ' minutes',
            'avis_dans' => $delaiAvisMinutes . ' minutes'
        ]);

        return true;
    }
}
