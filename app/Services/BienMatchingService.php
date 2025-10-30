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
     * Convertir l'enum revenus_mensuels en montant maximum
     */
    private function getMaxBudgetFromRevenus(string $revenus): int
    {
        $mapping = [
            'plus_100000' => 100000,
            'plus_200000' => 200000,
            'plus_300000' => 300000,
            'plus_500000' => 500000,
        ];

        return $mapping[$revenus] ?? 0;
    }

    /**
     * Vérifie si un appartement correspond aux critères d'un dossier client
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

        // 2. Vérifier le budget (revenus_mensuels >= prix du bien)
        if ($dossier->revenus_mensuels) {
            $budgetMax = $this->getMaxBudgetFromRevenus($dossier->revenus_mensuels);

            if ($bien->price > $budgetMax) {
                Log::info("❌ Prix trop élevé pour le client", [
                    'prix_bien' => $bien->price,
                    'budget_max' => $budgetMax,
                    'revenus' => $dossier->revenus_mensuels
                ]);
                return false;
            }
        }

        // 3. Vérifier le quartier (LIKE)
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

        // 4. Comparaison stricte des pièces (avec mapping correct des noms de colonnes)
        $criteresMatch = [
            'salons' => [
                'appartement_value' => $appartement->salons,
                'dossier_value' => $dossier->nbsalons ?? 0,
                'match' => $appartement->salons >= ($dossier->nbsalons ?? 0)
            ],
            'chambres' => [
                'appartement_value' => $appartement->chambres,
                'dossier_value' => $dossier->nbchambres ?? 0,
                'match' => $appartement->chambres >= ($dossier->nbchambres ?? 0)
            ],
            'salles_bain' => [
                'appartement_value' => $appartement->salles_bain,
                'dossier_value' => $dossier->nbsalledebains ?? 0,
                'match' => $appartement->salles_bain >= ($dossier->nbsalledebains ?? 0)
            ],
            'cuisines' => [
                'appartement_value' => $appartement->cuisines,
                'dossier_value' => $dossier->nbcuisines ?? 0,
                'match' => $appartement->cuisines >= ($dossier->nbcuisines ?? 0)
            ],
        ];

        foreach ($criteresMatch as $critere => $data) {
            if (!$data['match']) {
                Log::info("❌ Critère {$critere} non respecté", [
                    'appartement_id' => $appartement->id,
                    'appartement_value' => $data['appartement_value'],
                    'dossier_value' => $data['dossier_value']
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
     * Notifier un client qu'un appartement correspondant est disponible
     */
    public function notifierClient(Appartement $appartement, ClientDossier $dossier): bool
    {
        try {
            $bien = $appartement->bien;
            $client = $dossier->client;

            // 1. Envoyer l'email via Mailtrap
            Mail::to($client->email)->send(
                new BienDisponibleMail($bien, $dossier, $appartement)
            );

            Log::info("📧 Email envoyé à {$client->email} pour l'appartement {$appartement->id}");

            // 2. Créer/récupérer une conversation avec l'admin
            $admin = User::role('admin')->first();

            if (!$admin) {
                Log::warning("⚠️ Aucun admin trouvé pour créer la conversation");
                return true; // Email envoyé, mais pas de conversation
            }

            // Vérifier si une conversation existe déjà entre ce client et cet admin
            $conversation = Conversation::where('client_id', $client->id)
                ->where('admin_id', $admin->id)
                ->where('status', 'active')
                ->first();

            if (!$conversation) {
                // Créer une nouvelle conversation
                $conversation = Conversation::create([
                    'client_id' => $client->id,
                    'admin_id' => $admin->id,
                    'subject' => "Appartement disponible correspondant à vos critères",
                    'status' => 'active',
                    'last_message_at' => now(),
                ]);

                // Ajouter les participants à la table pivot
                $conversation->participants()->attach($client->id, [
                    'last_read_at' => null,
                    'unread_count' => 0,
                    'is_typing' => false,
                    'typing_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $conversation->participants()->attach($admin->id, [
                    'last_read_at' => now(),
                    'unread_count' => 0,
                    'is_typing' => false,
                    'typing_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("💬 Nouvelle conversation créée", [
                    'conversation_id' => $conversation->id,
                    'client_id' => $client->id,
                    'admin_id' => $admin->id
                ]);
            }

            // 3. Envoyer le message dans la conversation
            $messageContent = $this->genererMessageNotification($bien, $appartement, $dossier);

            $message = $conversation->messages()->create([
                'sender_id' => $admin->id,
                'message' => $messageContent,
                'type' => 'text',
                'is_read' => false,
                'read_at' => null,
            ]);

            // Mettre à jour la date du dernier message
            $conversation->update(['last_message_at' => now()]);

            // Incrémenter le compteur non lu pour le client dans la table pivot
            $conversation->participants()->updateExistingPivot($client->id, [
                'unread_count' => DB::raw('unread_count + 1'),
                'updated_at' => now(),
            ]);

            Log::info("✅ Message envoyé dans la conversation {$conversation->id}", [
                'message_id' => $message->id,
                'client_id' => $client->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("❌ Erreur lors de la notification du client {$dossier->client->email}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Générer le message de notification
     */
    private function genererMessageNotification(Bien $bien, Appartement $appartement, ClientDossier $dossier): string
    {
        return "🎉 Bonne nouvelle ! Nous avons trouvé un appartement qui correspond parfaitement à vos critères !\n\n" .
            "📍 Localisation : {$bien->address}, {$bien->city}\n" .
            "🏢 Étage : {$appartement->etage}\n" .
            "🚪 Numéro : {$appartement->numero}\n" .
            "📐 Superficie : {$appartement->superficie} m²\n\n" .
            "🛋️ Composition :\n" .
            "   • Salons : {$appartement->salons}\n" .
            "   • Chambres : {$appartement->chambres}\n" .
            "   • Salles de bain : {$appartement->salles_bain}\n" .
            "   • Cuisines : {$appartement->cuisines}\n\n" .
            "💰 Prix : " . number_format($bien->price, 0, ',', ' ') . " FCFA\n\n" .
            "N'hésitez pas à nous contacter pour organiser une visite !";
    }

    /**
     * Notifier tous les clients correspondants pour un nouveau bien avec appartements
     * 🔥 APPELÉ DEPUIS BienController::valider() après validation du bien
     */
    public function notifierClientsCorrespondants(Bien $bien): int
    {
        // Vérifier que le bien a un mandat actif de gestion locative
        if (!$bien->mandatActuel || $bien->mandatActuel->type_mandat !== 'gestion_locative') {
            Log::info("⚠️ Bien {$bien->id} n'a pas de mandat actif de gestion locative", [
                'mandat_exists' => $bien->mandatActuel ? true : false,
                'type_mandat' => $bien->mandatActuel?->type_mandat
            ]);
            return 0;
        }

        // Vérifier que c'est un immeuble avec des appartements
        if (!$bien->category || strtolower($bien->category->name) !== 'appartement') {
            Log::info("⚠️ Bien {$bien->id} n'est pas de catégorie Appartement", [
                'category_name' => $bien->category?->name
            ]);
            return 0;
        }

        // Récupérer UNIQUEMENT les appartements disponibles (statut de l'appartement, pas du bien)
        $appartements = $bien->appartements()->where('statut', 'disponible')->get();

        if ($appartements->isEmpty()) {
            Log::info("⚠️ Aucun appartement disponible pour le bien {$bien->id}");
            return 0;
        }

        Log::info("🔍 Recherche de clients pour {$appartements->count()} appartements du bien {$bien->id}");

        $dossiersClients = ClientDossier::with('client')->get();
        $nombreNotifications = 0;

        foreach ($dossiersClients as $dossier) {
            foreach ($appartements as $appartement) {
                if ($this->appartementCorrespondAuxCriteres($appartement, $dossier)) {
                    if ($this->notifierClient($appartement, $dossier)) {
                        $nombreNotifications++;
                    }
                    // Un seul appartement par client (le premier qui match)
                    break;
                }
            }
        }

        Log::info("✅ {$nombreNotifications} client(s) notifié(s) pour le bien {$bien->id}");

        return $nombreNotifications;
    }

    /**
     * Rechercher des appartements correspondants pour un client
     */
    public function rechercherAppartementsCorrespondants(ClientDossier $dossier)
    {
        $biens = Bien::with(['category', 'mandatActuel', 'images', 'appartements'])
            ->where('status', 'disponible')
            ->whereHas('mandatActuel', function($query) {
                $query->where('type_mandat', 'gestion_locative')
                    ->where('statut', 'actif');
            })
            ->whereHas('category', function($query) {
                $query->where('name', 'appartement');
            })
            ->whereHas('appartements', function($query) {
                $query->where('statut', 'disponible');
            })
            ->get();

        $appartementsCorrespondants = collect();

        foreach ($biens as $bien) {
            foreach ($bien->appartements as $appartement) {
                if ($this->appartementCorrespondAuxCriteres($appartement, $dossier)) {
                    $appartementsCorrespondants->push($appartement);
                }
            }
        }

        return $appartementsCorrespondants;
    }
}
