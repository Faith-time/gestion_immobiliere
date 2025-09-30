<?php

namespace App\Services;

use App\Models\Location;
use App\Models\User;
use App\Notifications\RappelPaiementLoyer;
use App\Notifications\AvisRetardPaiement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class MailtrapEmailService
{
    /**
     * Tester la connexion Mailtrap
     */
    public function testerConnexion()
    {
        try {
            Mail::raw('Test de connexion Mailtrap', function ($message) {
                $message->to('test@example.com')
                    ->subject('Test de connexion - ' . now()->format('Y-m-d H:i:s'));
            });

            Log::info('Test email Mailtrap envoyé avec succès');

            return [
                'success' => true,
                'message' => 'Email de test envoyé à Mailtrap avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur test Mailtrap: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors du test Mailtrap: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tester l'envoi d'un rappel de paiement
     */
    public function testerRappelPaiement($locationId = null)
    {
        try {
            // Utiliser une location existante ou créer des données de test
            if ($locationId) {
                $location = Location::with(['client', 'bien.proprietaire'])->find($locationId);
                if (!$location) {
                    throw new \Exception("Location #{$locationId} non trouvée");
                }
            } else {
                // Créer des données de test fictives
                $location = $this->creerLocationTest();
            }

            $dateEcheance = Carbon::today()->addDays(5);

            // Créer et envoyer la notification de test
            $notification = new RappelPaiementLoyer($location, $dateEcheance);

            // Envoyer au client
            $location->client->notify($notification);

            // Envoyer au propriétaire si existe
            if ($location->bien->proprietaire) {
                $location->bien->proprietaire->notify(
                    new RappelPaiementLoyer($location, $dateEcheance, true)
                );
            }

            Log::info('Test rappel paiement envoyé', [
                'location_id' => $location->id,
                'client_email' => $location->client->email,
                'date_echeance' => $dateEcheance->format('Y-m-d')
            ]);

            return [
                'success' => true,
                'message' => 'Rappel de paiement test envoyé à Mailtrap',
                'details' => [
                    'location' => $location->bien->title,
                    'client' => $location->client->name,
                    'montant' => number_format($location->loyer_mensuel, 0, ',', ' ') . ' FCFA',
                    'date_echeance' => $dateEcheance->format('d/m/Y')
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur test rappel paiement: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors du test rappel: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Tester l'envoi d'un avis de retard
     */
    public function testerAvisRetard($locationId = null, $joursRetard = 7)
    {
        try {
            // Utiliser une location existante ou créer des données de test
            if ($locationId) {
                $location = Location::with(['client', 'bien.proprietaire'])->find($locationId);
                if (!$location) {
                    throw new \Exception("Location #{$locationId} non trouvée");
                }
            } else {
                // Créer des données de test fictives
                $location = $this->creerLocationTest();
            }

            $dateEcheance = Carbon::today()->subDays($joursRetard);

            // Créer et envoyer la notification de test
            $notification = new AvisRetardPaiement($location, $dateEcheance, $joursRetard);

            // Envoyer au client
            $location->client->notify($notification);

            // Envoyer au propriétaire si existe
            if ($location->bien->proprietaire) {
                $location->bien->proprietaire->notify(
                    new AvisRetardPaiement($location, $dateEcheance, $joursRetard, true)
                );
            }

            Log::info('Test avis retard envoyé', [
                'location_id' => $location->id,
                'client_email' => $location->client->email,
                'jours_retard' => $joursRetard,
                'date_echeance' => $dateEcheance->format('Y-m-d')
            ]);

            return [
                'success' => true,
                'message' => 'Avis de retard test envoyé à Mailtrap',
                'details' => [
                    'location' => $location->bien->title,
                    'client' => $location->client->name,
                    'montant' => number_format($location->loyer_mensuel, 0, ',', ' ') . ' FCFA',
                    'jours_retard' => $joursRetard,
                    'penalites' => number_format($this->calculerPenalites($location->loyer_mensuel, $joursRetard), 0, ',', ' ') . ' FCFA'
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Erreur test avis retard: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors du test avis retard: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Envoyer un email personnalisé de test
     */
    public function envoyerEmailPersonnalise($destinataire, $sujet, $contenu)
    {
        try {
            Mail::raw($contenu, function ($message) use ($destinataire, $sujet) {
                $message->to($destinataire)
                    ->subject($sujet);
            });

            Log::info('Email personnalisé envoyé', [
                'destinataire' => $destinataire,
                'sujet' => $sujet
            ]);

            return [
                'success' => true,
                'message' => 'Email personnalisé envoyé avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur email personnalisé: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Vérifier la configuration Mailtrap
     */
    public function verifierConfiguration()
    {
        $config = [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'), // Gardez comme string
            'MAIL_USERNAME' => config('mail.mailers.smtp.username'),
            'MAIL_ENCRYPTION' => config('mail.mailers.smtp.encryption'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
        ];

        $isValid = true;
        $errors = [];

        // Vérifications
        if ($config['MAIL_MAILER'] !== 'smtp') {
            $isValid = false;
            $errors[] = 'MAIL_MAILER doit être "smtp"';
        }

        if ($config['MAIL_HOST'] !== 'sandbox.smtp.mailtrap.io') {
            $isValid = false;
            $errors[] = 'MAIL_HOST doit être "sandbox.smtp.mailtrap.io"';
        }

        // Comparer comme string ou convertir en int
        if ((string)$config['MAIL_PORT'] !== '2525' && (int)$config['MAIL_PORT'] !== 2525) {
            $isValid = false;
            $errors[] = "MAIL_PORT doit être 2525 (actuellement: {$config['MAIL_PORT']})";
        }

        if (empty($config['MAIL_USERNAME'])) {
            $isValid = false;
            $errors[] = 'MAIL_USERNAME est requis';
        }

        if (empty(config('mail.mailers.smtp.password'))) {
            $isValid = false;
            $errors[] = 'MAIL_PASSWORD est requis';
        }

        if (empty($config['MAIL_ENCRYPTION'])) {
            $isValid = false;
            $errors[] = 'MAIL_ENCRYPTION doit être défini (tls recommandé)';
        }

        return [
            'is_valid' => $isValid,
            'config' => $config,
            'errors' => $errors
        ];
    }
    /**
     * Créer une location fictive pour les tests
     */
    private function creerLocationTest()
    {
        // Créer des objets temporaires pour le test
        $client = new User([
            'id' => 999,
            'name' => 'Client Test Mailtrap',
            'email' => 'client-test@mailtrap.local'
        ]);

        $proprietaire = new User([
            'id' => 998,
            'name' => 'Propriétaire Test Mailtrap',
            'email' => 'proprietaire-test@mailtrap.local'
        ]);

        $bien = new \App\Models\Bien([
            'id' => 999,
            'title' => 'Appartement Test Mailtrap',
            'address' => '123 Rue de Test',
            'city' => 'Dakar',
            'proprietaire_id' => 998
        ]);

        // Associer le propriétaire au bien
        $bien->setRelation('proprietaire', $proprietaire);

        $location = new Location([
            'id' => 999,
            'loyer_mensuel' => 150000,
            'date_debut' => Carbon::now()->subMonths(3),
            'date_fin' => Carbon::now()->addMonths(9),
            'client_id' => 999,
            'bien_id' => 999
        ]);

        // Associer les relations
        $location->setRelation('client', $client);
        $location->setRelation('bien', $bien);

        return $location;
    }

    /**
     * Calculer les pénalités de retard
     */
    private function calculerPenalites($montantLoyer, $joursRetard)
    {
        $semaines = ceil($joursRetard / 7);
        $taux = config('app.loyer_penalite_taux', 0.02);
        return $montantLoyer * $taux * $semaines;
    }

    /**
     * Obtenir les statistiques Mailtrap (si API disponible)
     */
    public function getStatistiquesMailtrap()
    {
        // Cette méthode pourrait être étendue pour utiliser l'API Mailtrap
        // si vous avez besoin de récupérer des statistiques d'envoi

        return [
            'note' => 'Consultez votre inbox Mailtrap pour voir les emails envoyés',
            'url' => 'https://mailtrap.io/inboxes',
            'conseil' => 'Vérifiez les emails dans votre sandbox Mailtrap'
        ];
    }
}
