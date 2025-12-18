<?php

namespace App\Services;

use App\Models\Vente;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Illuminate\Support\Facades\Log;

class SaleCompletionNotificationService
{
    /**
     * ✅ Envoie un message de confirmation d'achat au client
     */
    public function envoyerNotificationAchat(Vente $vente): bool
    {
        try {
            Log::info('🚀 Début envoi notification achat', [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id,
                'status' => $vente->status
            ]);

            // Vérifier l'acheteur
            if (!$vente->acheteur) {
                Log::error('❌ Acheteur introuvable pour la vente', [
                    'vente_id' => $vente->id,
                ]);
                return false;
            }

            Log::info('✅ Acheteur trouvé', [
                'acheteur_id' => $vente->acheteur->id,
                'acheteur_name' => $vente->acheteur->name
            ]);

            // Vérifier le bien via reservation
            if (!$vente->reservation || !$vente->reservation->bien) {
                Log::error('❌ Bien introuvable pour la vente', [
                    'vente_id' => $vente->id,
                ]);
                return false;
            }

            $bien = $vente->reservation->bien;

            Log::info('✅ Bien trouvé', [
                'bien_id' => $bien->id,
                'bien_title' => $bien->title
            ]);

            // ✅ Créer ou récupérer la conversation
            $conversation = $this->getOrCreateConversationForVente($vente);

            if (!$conversation) {
                Log::error('❌ Impossible de créer/récupérer la conversation', [
                    'vente_id' => $vente->id,
                    'acheteur_id' => $vente->acheteur_id,
                ]);
                return false;
            }

            Log::info('✅ Conversation trouvée/créée', [
                'conversation_id' => $conversation->id,
                'subject' => $conversation->subject
            ]);

            // Générer le message de confirmation
            $messageText = $this->genererMessageConfirmationAchat($vente);

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

            // Incrémenter le compteur de messages non lus pour l'acheteur
            $conversation->participantDetails()
                ->where('user_id', $vente->acheteur_id)
                ->first()
                ?->incrementUnread();

            Log::info('✅ Message de confirmation achat envoyé avec succès', [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id,
                'conversation_id' => $conversation->id,
                'message_id' => $message->id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi notification achat: ' . $e->getMessage(), [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id ?? null,
                'exception' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return false;
        }
    }

    /**
     * ✅ Récupère ou crée une conversation spécifique pour cette vente
     */
    private function getOrCreateConversationForVente(Vente $vente): ?Conversation
    {
        try {
            $bien = $vente->reservation?->bien;
            $bienTitle = $bien->title ?? 'Bien';
            $subjectPrefix = "Confirmation d'achat - {$bienTitle}";

            Log::info('🔍 Recherche conversation pour vente', [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id,
                'subject_prefix' => $subjectPrefix
            ]);

            // ✅ Chercher une conversation EXISTANTE avec cet acheteur qui parle de ce bien
            $conversation = Conversation::where('client_id', $vente->acheteur_id)
                ->where('status', 'active')
                ->where('subject', 'like', $subjectPrefix . '%')
                ->first();

            // Si aucune conversation trouvée, en créer une nouvelle
            if (!$conversation) {
                Log::info('📝 Création nouvelle conversation pour la vente', [
                    'acheteur_id' => $vente->acheteur_id,
                    'subject' => $subjectPrefix
                ]);

                $conversation = Conversation::create([
                    'client_id' => $vente->acheteur_id,
                    'admin_id' => 1, // ID de l'admin principal
                    'subject' => $subjectPrefix,
                    'status' => 'active',
                    'last_message_at' => now(),
                ]);

                // Ajouter les participants
                $conversation->participants()->attach($vente->acheteur_id);
                $conversation->participants()->attach(1); // Admin

                Log::info('✅ Nouvelle conversation créée pour la vente', [
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
            Log::error('❌ Erreur création/récupération conversation pour vente', [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * ✅ Génère le message de confirmation d'achat
     */
    private function genererMessageConfirmationAchat(Vente $vente): string
    {
        $bien = $vente->reservation->bien;
        $acheteur = $vente->acheteur;
        $appartement = $vente->reservation->appartement;

        $bienTitle = $bien->title;
        $bienAddress = $bien->address . ', ' . $bien->city;
        $prixVente = number_format($vente->prix_vente, 0, ',', ' ');

        // Message de félicitations
        $message = "**🎉 Félicitations {$acheteur->name} !**\n\n";
        $message .= "Nous avons le grand plaisir de vous confirmer la **finalisation complète de votre achat**.\n\n";
        $message .= "## 📋 Récapitulatif de votre acquisition\n\n";
        $message .= "**Bien acquis :** {$bienTitle}\n";
        $message .= "**Adresse :** {$bienAddress}\n";
        $message .= "**Prix de vente :** {$prixVente} FCFA\n";
        $message .= "**Date de vente :** " . $vente->date_vente->format('d/m/Y') . "\n";

        // Si c'est un appartement spécifique
        if ($appartement) {
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

        // Statut de la transaction
        $message .= "\n## ✅ Statut de la transaction\n\n";
        $message .= "- ✅ **Paiement intégral :** Effectué\n";
        $message .= "- ✅ **Signatures :** Complètes (Vendeur & Acheteur)\n";
        $message .= "- ✅ **Transfert de propriété :** Effectué\n";
        $message .= "- ✅ **Vous êtes maintenant propriétaire officiel de ce bien**\n";

        // Prochaines étapes
        $message .= "\n## 📄 Documents et prochaines étapes\n\n";
        $message .= "1. **Contrat de vente signé** : Disponible dans votre espace \"Mes Ventes\"\n";
        $message .= "2. **Reçu de paiement** : Envoyé par email\n";
        $message .= "3. **Documents administratifs** : Seront préparés sous 7 jours ouvrables\n";
        $message .= "4. **Remise des clés** : Notre équipe vous contactera pour organiser la remise\n";

        // Informations importantes
        $message .= "\n## ℹ️ Informations importantes\n\n";
        $message .= "- Conservez précieusement tous les documents de vente\n";
        $message .= "- Les taxes foncières sont désormais à votre charge\n";
        $message .= "- Pensez à souscrire une assurance habitation\n";
        $message .= "- Pour toute question administrative, contactez-nous\n";

        // Contact
        $message .= "\n## 📞 Besoin d'assistance ?\n\n";
        $message .= "Notre équipe reste à votre entière disposition :\n\n";
        $message .= "**Cauris Immobilière**\n";
        $message .= "📍 Parcelles assainies, Keur Massar, Dakar\n";
        $message .= "☎️ **Téléphone :** +221 78 291 53 18\n";
        $message .= "📧 **Email :** caurisimmobiliere@gmail.com\n";
        $message .= "💬 **Messagerie :** Répondez directement à cette conversation\n";

        // Signature
        $message .= "\n---\n\n";
        $message .= "Nous vous félicitons pour cette acquisition et vous souhaitons beaucoup de bonheur dans votre nouveau bien !\n\n";
        $message .= "**L'équipe Cauris Immo**\n";
        $message .= "*Votre partenaire immobilier de confiance* 🏡";

        return $message;
    }
}
