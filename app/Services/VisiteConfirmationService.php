<?php

namespace App\Services;

use App\Models\Visite;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Log;

class VisiteConfirmationService
{
    /**
     * Envoie un message de confirmation de visite au client
     */
    public function envoyerConfirmation(Visite $visite): bool
    {
        try {
            Log::info('🚀 Début envoi confirmation visite', [
                'visite_id' => $visite->id,
                'client_id' => $visite->client_id,
                'statut' => $visite->statut
            ]);

            if (!$visite->client) {
                Log::error('❌ Client introuvable pour la visite', [
                    'visite_id' => $visite->id,
                ]);
                return false;
            }

            Log::info('✅ Client trouvé', [
                'client_id' => $visite->client->id,
                'client_name' => $visite->client->name
            ]);

            if (!$visite->bien) {
                Log::error('❌ Bien introuvable pour la visite', [
                    'visite_id' => $visite->id,
                ]);
                return false;
            }

            Log::info('✅ Bien trouvé', [
                'bien_id' => $visite->bien->id,
                'bien_title' => $visite->bien->title
            ]);

            // ✅ CORRECTION : Créer une conversation spécifique pour cette confirmation
            $conversation = $this->getOrCreateConversationForVisite($visite);

            if (!$conversation) {
                Log::error('❌ Impossible de créer/récupérer la conversation', [
                    'visite_id' => $visite->id,
                    'client_id' => $visite->client_id,
                ]);
                return false;
            }

            Log::info('✅ Conversation trouvée/créée', [
                'conversation_id' => $conversation->id,
                'subject' => $conversation->subject
            ]);

            // Générer le message de confirmation
            $messageText = $this->genererMessageConfirmation($visite);

            Log::info('✅ Message généré', [
                'longueur_message' => strlen($messageText)
            ]);

            // Créer le message
            $message = $conversation->messages()->create([
                'sender_id' => 1, // ID de l'admin
                'message' => $messageText,
                'type' => 'text',
                'is_read' => false,
            ]);

            Log::info('✅ Message créé dans la base', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id
            ]);

            // Mettre à jour la conversation
            $conversation->update([
                'last_message_at' => now(),
                'updated_at' => now(),
            ]);

            // Incrémenter le compteur de messages non lus pour le client
            $conversation->participantDetails()
                ->where('user_id', $visite->client_id)
                ->first()
                ?->incrementUnread();

            Log::info('✅ Message de confirmation visite envoyé avec succès', [
                'visite_id' => $visite->id,
                'client_id' => $visite->client_id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi confirmation visite: ' . $e->getMessage(), [
                'visite_id' => $visite->id,
                'client_id' => $visite->client_id ?? null,
                'exception' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return false;
        }
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Récupère ou crée une conversation spécifique pour cette visite
     */
    private function getOrCreateConversationForVisite(Visite $visite): ?Conversation
    {
        try {
            $bienTitle = $visite->bien->title ?? 'Bien';
            $subjectPrefix = "Confirmation visite - {$bienTitle}";

            Log::info('🔍 Recherche conversation pour visite', [
                'visite_id' => $visite->id,
                'client_id' => $visite->client_id,
                'subject_prefix' => $subjectPrefix
            ]);

            // ✅ Chercher une conversation EXISTANTE avec ce client qui parle de ce bien
            $conversation = Conversation::where('client_id', $visite->client_id)
                ->where('status', 'active')
                ->where('subject', 'like', $subjectPrefix . '%')
                ->first();

            // Si aucune conversation trouvée, en créer une nouvelle
            if (!$conversation) {
                Log::info('📝 Création nouvelle conversation pour la visite', [
                    'client_id' => $visite->client_id,
                    'subject' => $subjectPrefix
                ]);

                $conversation = Conversation::create([
                    'client_id' => $visite->client_id,
                    'admin_id' => 1, // ID de l'admin principal
                    'subject' => $subjectPrefix,
                    'status' => 'active',
                    'last_message_at' => now(),
                ]);

                // Ajouter les participants
                $conversation->participants()->attach($visite->client_id);
                $conversation->participants()->attach(1); // Admin

                Log::info('✅ Nouvelle conversation créée pour la visite', [
                    'conversation_id' => $conversation->id,
                    'subject' => $conversation->subject
                ]);
            } else {
                Log::info('✅ Conversation existante trouvée', [
                    'conversation_id' => $conversation->id,
                    'subject' => $conversation->subject
                ]);
            }

            return $conversation;

        } catch (\Exception $e) {
            Log::error('❌ Erreur création/récupération conversation pour visite', [
                'visite_id' => $visite->id,
                'client_id' => $visite->client_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Génère le message de confirmation professionnel
     */
    private function genererMessageConfirmation(Visite $visite): string
    {
        $dateVisite = $visite->date_visite->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[h]mm');
        $bienTitle = $visite->bien->title;
        $bienAddress = $visite->bien->address . ', ' . $visite->bien->city;
        $typeMandat = $visite->bien->mandat->type_mandat ?? 'location';
        $typeMandatLabel = $typeMandat === 'vente' ? 'vente' : 'location';

        // Message de base
        $message = "**Bonjour {$visite->client->name},**\n\n";
        $message .= "Nous avons le plaisir de vous confirmer votre demande de visite.\n\n";
        $message .= "## 📋 Détails de la visite\n\n";
        $message .= "**Date et heure :** {$dateVisite}\n";
        $message .= "**Bien concerné :** {$bienTitle}\n";
        $message .= "**Adresse :** {$bienAddress}\n";
        $message .= "**Type de transaction :** " . ucfirst($typeMandatLabel) . "\n";

        // Si c'est un appartement spécifique dans un immeuble
        if ($visite->appartement) {
            $appartement = $visite->appartement;
            $message .= "**Appartement :** N° {$appartement->numero} - {$appartement->getEtageLabel()}\n";
            $message .= "**Superficie :** {$appartement->superficie} m²\n";

            $pieces = $appartement->salons + $appartement->chambres;
            if ($pieces > 0) {
                $message .= "**Composition :** {$pieces} pièce(s)";

                $details = [];
                if ($appartement->salons > 0) {
                    $details[] = "{$appartement->salons} salon(s)";
                }
                if ($appartement->chambres > 0) {
                    $details[] = "{$appartement->chambres} chambre(s)";
                }
                if ($appartement->salles_bain > 0) {
                    $details[] = "{$appartement->salles_bain} salle(s) de bain";
                }
                if ($appartement->cuisines > 0) {
                    $details[] = "{$appartement->cuisines} cuisine(s)";
                }

                if (!empty($details)) {
                    $message .= " (" . implode(', ', $details) . ")";
                }
                $message .= "\n";
            }
        }

        // Informations pratiques
        $message .= "\n## ℹ️ Informations pratiques\n\n";
        $message .= "- Merci d'arriver **5 minutes avant** l'heure prévue\n";
        $message .= "- Un de nos agents vous accueillera sur place\n";
        $message .= "- N'hésitez pas à préparer vos questions\n";
        $message .= "- Si la visite répond à vos attentes, vous pourrez effectuer votre réservation directement sur notre plateforme pour poursuivre la transaction\n";

        // Notes admin si présentes
        if ($visite->notes_admin) {
            $message .= "\n**Note importante :** {$visite->notes_admin}\n";
        }

        // Contact
        $message .= "\n## 📞 Contactez-nous\n\n";
        $message .= "Pour toute question ou en cas d'empêchement, nous restons à votre disposition :\n\n";
        $message .= "**Cauris Immobilière**\n";
        $message .= "📍 Parcelles assainies, Keur Massar, Dakar\n";
        $message .= "☎️ **Téléphone :** +221 78 291 53 18\n";
        $message .= "📧 **Email :** caurisimmobiliere@gmail.com\n";
        $message .= "💬 **Messagerie :** Répondez directement à cette conversation\n";

        // Signature
        $message .= "\n---\n\n";
        $message .= "Nous vous remercions de votre confiance et restons à votre entière disposition.\n\n";
        $message .= "**L'équipe Cauris Immo**\n";
        $message .= "*Votre partenaire immobilier de confiance*";

        return $message;
    }
}
