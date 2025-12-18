<?php

namespace App\Services;

use App\Models\Bien;
use App\Models\Appartement;
use App\Models\ClientDossier;
use App\Models\User;
use App\Models\Conversation;
use App\Mail\BienDisponibleMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BienMatchingService
{
    /**
     * 🔥 CORRECTION : Convertir l'enum revenus_mensuels en budget maximum
     */
    private function getMaxBudgetFromRevenus(string $revenus): int
    {
        $mapping = [
            'plus_100000' => 100000,
            'plus_200000' => 200000,
            'plus_300000' => 300000,
            'plus_500000' => 500000,
        ];

        return $mapping[$revenus] ?? PHP_INT_MAX; // Si pas de limite, on accepte tout
    }

    /**
     * 🔥 CORRECTION : Vérifier si un appartement correspond aux critères
     */
    public function appartementCorrespondAuxCriteres(Appartement $appartement, ClientDossier $dossier): bool
    {
        $bien = $appartement->bien;

        if (!$bien) {
            return false;
        }

        // 1. Vérifier le statut de l'appartement
        if ($appartement->statut !== 'disponible') {
            Log::info("❌ Appartement {$appartement->id} non disponible", [
                'statut' => $appartement->statut
            ]);
            return false;
        }

        // 2. 🔥 CORRECTION : Vérifier le type de logement (appartement ou studio)
        if ($dossier->type_logement) {
            $typeDemande = strtolower($dossier->type_logement);

            // Si le client cherche un "studio", il faut 1 chambre max
            if ($typeDemande === 'studio' && $appartement->chambres > 1) {
                Log::info("❌ Pas un studio (trop de chambres)", [
                    'appartement_id' => $appartement->id,
                    'chambres' => $appartement->chambres
                ]);
                return false;
            }
        }

        // 3. Vérifier le budget
        if ($dossier->revenus_mensuels) {
            $budgetMax = $this->getMaxBudgetFromRevenus($dossier->revenus_mensuels);

            if ($bien->price > $budgetMax) {
                Log::info("❌ Prix trop élevé", [
                    'prix_bien' => $bien->price,
                    'budget_max' => $budgetMax,
                ]);
                return false;
            }
        }

        // 4. Vérifier le quartier
        if ($dossier->quartier_souhaite) {
            $quartierSouhaite = strtolower($dossier->quartier_souhaite);
            $bienLocalisation = strtolower($bien->city . ' ' . $bien->address);

            if (strpos($bienLocalisation, $quartierSouhaite) === false) {
                Log::info("❌ Quartier ne correspond pas", [
                    'quartier_souhaite' => $quartierSouhaite,
                    'localisation' => $bienLocalisation
                ]);
                return false;
            }
        }

        // 5. 🔥 CORRECTION : Vérifier les pièces (>=, pas ==)
        $criteresMatch = [
            'chambres' => $appartement->chambres >= ($dossier->nbchambres ?? 0),
            'salons' => $appartement->salons >= ($dossier->nbsalons ?? 0),
            'salles_bain' => $appartement->salles_bain >= ($dossier->nbsalledebains ?? 0),
            'cuisines' => $appartement->cuisines >= ($dossier->nbcuisines ?? 0),
        ];

        foreach ($criteresMatch as $critere => $match) {
            if (!$match) {
                Log::info("❌ Critère {$critere} non respecté", [
                    'appartement_id' => $appartement->id,
                    'appartement_value' => $appartement->$critere,
                    'dossier_value' => $dossier->{"nb$critere"} ?? 0
                ]);
                return false;
            }
        }

        Log::info("✅ Match parfait trouvé!", [
            'appartement_id' => $appartement->id,
            'bien_id' => $bien->id,
            'client_id' => $dossier->client_id
        ]);

        return true;
    }

    /**
     * 🔥 Notifier un client qu'un appartement correspondant est disponible
     */
    public function notifierClient(Appartement $appartement, ClientDossier $dossier): bool
    {
        try {
            $bien = $appartement->bien;
            $client = $dossier->client;

            // 1. Envoyer l'email
            Mail::to($client->email)->send(
                new BienDisponibleMail($bien, $dossier, $appartement)
            );

            Log::info("📧 Email envoyé à {$client->email}");

            // 2. Créer/récupérer une conversation
            $admin = User::role('admin')->first();

            if (!$admin) {
                return true; // Email envoyé quand même
            }

            $conversation = Conversation::firstOrCreate(
                [
                    'client_id' => $client->id,
                    'admin_id' => $admin->id,
                    'status' => 'active',
                ],
                [
                    'subject' => '🏠 Appartements disponibles correspondant à vos critères',
                    'last_message_at' => now(),
                ]
            );

            // Assurer les participants
            if (!$conversation->hasParticipant($client->id)) {
                $conversation->participants()->attach($client->id);
            }
            if (!$conversation->hasParticipant($admin->id)) {
                $conversation->participants()->attach($admin->id);
            }

            // 3. Envoyer le message
            $messageContent = $this->genererMessageNotification($bien, $appartement, $dossier);

            $message = $conversation->messages()->create([
                'sender_id' => $admin->id,
                'message' => $messageContent,
                'type' => 'text',
            ]);

            $conversation->update(['last_message_at' => now()]);

            // Incrémenter le compteur non lu
            $conversation->participantDetails()
                ->where('user_id', $client->id)
                ->first()
                ?->incrementUnread();

            Log::info("✅ Notification complète envoyée", [
                'conversation_id' => $conversation->id,
                'message_id' => $message->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur notification client", [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 🔥 Notifier tous les clients correspondants pour un nouveau bien
     */
    public function notifierClientsCorrespondants(Bien $bien): int
    {
        // Vérifier que le bien a un mandat actif de gestion locative
        if (!$bien->mandatActuel || $bien->mandatActuel->type_mandat !== 'gestion_locative') {
            return 0;
        }

        // Vérifier que c'est un immeuble avec des appartements
        if (!$bien->category || strtolower($bien->category->name) !== 'appartement') {
            return 0;
        }

        // Récupérer UNIQUEMENT les appartements disponibles
        $appartements = $bien->appartements()->where('statut', 'disponible')->get();

        if ($appartements->isEmpty()) {
            return 0;
        }

        Log::info("🔍 Recherche de clients pour {$appartements->count()} appartements");

        $dossiersClients = ClientDossier::with('client')->get();
        $nombreNotifications = 0;

        foreach ($dossiersClients as $dossier) {
            foreach ($appartements as $appartement) {
                if ($this->appartementCorrespondAuxCriteres($appartement, $dossier)) {
                    if ($this->notifierClient($appartement, $dossier)) {
                        $nombreNotifications++;
                    }
                    break; // Un seul appartement par client
                }
            }
        }

        return $nombreNotifications;
    }

    /**
     * 🔥 Générer le message de notification
     */
    private function genererMessageNotification(Bien $bien, Appartement $appartement, ClientDossier $dossier): string
    {
        $urlBien = url('/biens/' . $bien->id);

        return "🎉 **Nouvelle opportunité pour vous !**\n\n" .
            "Nous avons trouvé un bien qui correspond parfaitement à vos critères :\n\n" .

            "🏢 **Appartement N° {$appartement->numero}**\n" .
            "📍 {$bien->address}, {$bien->city}\n" .
            "🏠 Étage : " . $this->getEtageLabel($appartement->etage) . "\n" .
            "📐 Superficie : {$appartement->superficie} m²\n\n" .

            "**Composition :**\n" .
            "🛋️ Salons : {$appartement->salons}\n" .
            "🛏️ Chambres : {$appartement->chambres}\n" .
            "🚿 Salles de bain : {$appartement->salles_bain}\n" .
            "🍳 Cuisines : {$appartement->cuisines}\n\n" .

            "💰 **Loyer mensuel : " . number_format($bien->price, 0, ',', ' ') . " FCFA**\n\n" .

            "👉 Consultez la fiche complète ici : {$urlBien}\n\n" .

            "Nous restons à votre disposition pour organiser une visite.\n\n" .
            "Cordialement,\n" .
            "L'équipe Cauris Immobilier";
    }

    /**
     * Helper : Obtenir le libellé de l'étage
     */
    private function getEtageLabel(int $etage): string
    {
        if ($etage === 0) return 'Rez-de-chaussée';
        if ($etage === 1) return '1er étage';
        return $etage . 'ème étage';
    }
}
