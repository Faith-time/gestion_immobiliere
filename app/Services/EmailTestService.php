<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use App\Notifications\RappelPaiementLoyer;
use App\Notifications\AvisRetardPaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class EmailTestService
{
    /**
     * Tester avec de vrais utilisateurs et délais réduits
     */
    public function testerAvecVraisUtilisateurs($locationId = null, $delaiMinutes = 5)
    {
        try {
            // Récupérer une location réelle ou la première disponible
            if ($locationId) {
                $location = Location::with(['client', 'bien.proprietaire'])->find($locationId);
            } else {
                $location = Location::with(['client', 'bien.proprietaire'])->first();
            }

            if (!$location) {
                return [
                    'success' => false,
                    'message' => 'Aucune location trouvée dans la base de données'
                ];
            }

            // Tester rappel de paiement (X minutes après maintenant)
            $this->testerRappelAvecDelaiReduit($location, $delaiMinutes);

            // Tester avis de retard (X*2 minutes après le rappel)
            $this->testerAvisRetardAvecDelaiReduit($location, $delaiMinutes * 2);

            return [
                'success' => true,
                'message' => 'Tests programmés avec délais réduits',
                'details' => [
                    'location_id' => $location->id,
                    'client' => $location->client->name,
                    'client_email' => $location->client->email,
                    'proprietaire' => $location->bien->proprietaire->name ?? 'Non défini',
                    'proprietaire_email' => $location->bien->proprietaire->email ?? 'Non défini',
                    'rappel_dans' => $delaiMinutes . ' minutes',
                    'avis_retard_dans' => ($delaiMinutes * 2) . ' minutes'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur test avec vrais utilisateurs: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer rappel de paiement avec délai réduit pour test
     */
    private function testerRappelAvecDelaiReduit($location, $delaiMinutes)
    {
        // Date d'échéance fictive pour le test (dans X jours)
        $dateEcheance = Carbon::now()->addDays(5);

        // Programmer l'envoi dans X minutes
        dispatch(function () use ($location, $dateEcheance) {
            $notification = new RappelPaiementLoyer($location, $dateEcheance);
            $location->client->notify($notification);

            // Envoyer au propriétaire si existe
            if ($location->bien->proprietaire) {
                $notificationProprietaire = new RappelPaiementLoyer($location, $dateEcheance, true);
                $location->bien->proprietaire->notify($notificationProprietaire);
            }

            Log::info('Rappel test envoyé', [
                'client' => $location->client->email,
                'location_id' => $location->id,
                'echeance_test' => $dateEcheance->format('Y-m-d H:i:s')
            ]);
        })->delay(Carbon::now()->addMinutes($delaiMinutes));
    }

    /**
     * Envoyer avis de retard avec délai réduit pour test
     */
    private function testerAvisRetardAvecDelaiReduit($location, $delaiMinutes)
    {
        // Simuler une échéance déjà passée pour le test
        $dateEcheance = Carbon::now()->subDays(7);
        $joursRetard = 7; // Simuler 7 jours de retard

        // Programmer l'envoi dans X minutes
        dispatch(function () use ($location, $dateEcheance, $joursRetard) {
            $notification = new AvisRetardPaiement($location, $dateEcheance, $joursRetard);
            $location->client->notify($notification);

            // Envoyer au propriétaire si existe
            if ($location->bien->proprietaire) {
                $notificationProprietaire = new AvisRetardPaiement($location, $dateEcheance, $joursRetard, true);
                $location->bien->proprietaire->notify($notificationProprietaire);
            }

            Log::info('Avis retard test envoyé', [
                'client' => $location->client->email,
                'location_id' => $location->id
            ]);
        })->delay(Carbon::now()->addMinutes($delaiMinutes));
    }

    /**
     * Lister toutes les locations disponibles pour les tests
     */
    public function listerLocationsDisponibles()
    {
        $locations = Location::with(['client', 'bien.proprietaire'])
            ->get()
            ->map(function ($location) {
                return [
                    'id' => $location->id,
                    'bien' => $location->bien->title,
                    'client' => $location->client->name,
                    'client_email' => $location->client->email,
                    'proprietaire' => $location->bien->proprietaire->name ?? 'Non défini',
                    'proprietaire_email' => $location->bien->proprietaire->email ?? 'Non défini',
                    'loyer' => number_format($location->loyer_mensuel, 0, ',', ' ') . ' FCFA'
                ];
            });

        return [
            'success' => true,
            'locations' => $locations,
            'total' => $locations->count()
        ];
    }

    /**
     * Envoyer immédiatement à une location spécifique
     */
    public function envoyerImmediatement($locationId, $type = 'rappel')
    {
        try {
            $location = Location::with(['client', 'bien.proprietaire'])->find($locationId);

            if (!$location) {
                return ['success' => false, 'message' => 'Location non trouvée'];
            }

            if ($type === 'rappel') {
                $dateEcheance = Carbon::now()->addDays(5);
                $notification = new RappelPaiementLoyer($location, $dateEcheance);
                $notificationProprietaire = new RappelPaiementLoyer($location, $dateEcheance, true);
            } else {
                $dateEcheance = Carbon::now()->subDays(7);
                $joursRetard = 7;
                $notification = new AvisRetardPaiement($location, $dateEcheance, $joursRetard);
                $notificationProprietaire = new AvisRetardPaiement($location, $dateEcheance, $joursRetard, true);
            }

            // Envoyer au client
            $location->client->notify($notification);

            // Envoyer au propriétaire si existe
            if ($location->bien->proprietaire) {
                $location->bien->proprietaire->notify($notificationProprietaire);
            }

            return [
                'success' => true,
                'message' => ucfirst($type) . ' envoyé immédiatement',
                'destinataires' => [
                    'client' => $location->client->email,
                    'proprietaire' => $location->bien->proprietaire->email ?? 'Non défini'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ];
        }
    }
}
