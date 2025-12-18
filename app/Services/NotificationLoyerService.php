<?php

namespace App\Services;

use App\Models\Location;
use App\Models\AvisRetard;
use App\Models\Conversation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationLoyerService
{
    // Constantes
    const JOUR_ECHEANCE_MENSUELLE = 1; // Le 1er de chaque mois
    const JOURS_DELAI_PAIEMENT = 10; // 10 jours pour payer
    const JOUR_LIMITE_PAIEMENT = 10; // Date limite = 10 du mois
    const TAUX_PENALITE = 0.02; // 2% du loyer
    const JOURS_AVANT_RAPPEL = 5; // Rappel 5 jours avant la date limite

    /**
     * ✅ Envoyer les rappels de paiement (J-5)
     */
    public function envoyerRappelsMensuels()
    {
        $aujourdhui = Carbon::today();
        $jourDuMois = $aujourdhui->day;

        // Envoyer rappels uniquement le 5 du mois
        if ($jourDuMois !== (self::JOUR_LIMITE_PAIEMENT - self::JOURS_AVANT_RAPPEL)) {
            return [
                'success' => true,
                'message' => 'Rappels envoyés uniquement le ' . (self::JOUR_LIMITE_PAIEMENT - self::JOURS_AVANT_RAPPEL) . ' du mois',
                'rappels_envoyes' => 0
            ];
        }

        $moisConcerne = $aujourdhui->format('Y-m');
        $dateEcheance = Carbon::parse($moisConcerne . '-01');
        $dateLimitePaiement = Carbon::parse($moisConcerne . '-' . self::JOUR_LIMITE_PAIEMENT);

        $locations = Location::with(['client', 'reservation.bien.proprietaire'])
            ->whereIn('statut', ['active', 'en_retard'])
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

        Log::info("✅ Rappels de paiement envoyés", [
            'date' => $aujourdhui->format('Y-m-d'),
            'mois_concerne' => $moisConcerne,
            'rappels_envoyes' => $rappelsEnvoyes
        ]);

        return [
            'success' => true,
            'message' => "{$rappelsEnvoyes} rappel(s) envoyé(s)",
            'rappels_envoyes' => $rappelsEnvoyes
        ];
    }

    /**
     * ✅ Envoyer les avis de retard (après le 10)
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

        $locations = Location::with(['client', 'reservation.bien.proprietaire'])
            ->whereIn('statut', ['active', 'en_retard'])
            ->get();

        $avisEnvoyes = 0;

        foreach ($locations as $location) {
            $montantRestant = $this->calculerMontantRestant($location, $moisConcerne);

            if ($montantRestant > 0) {
                if (!$this->avisRetardDejaEnvoye($location, $moisConcerne)) {
                    $this->creerEtEnvoyerAvisRetard($location, $dateEcheance, $joursRetard, $montantRestant);
                    $avisEnvoyes++;

                    // ✅ Mettre à jour le statut de la location
                    if ($location->statut !== 'en_retard') {
                        $location->update(['statut' => 'en_retard']);
                    }
                }
            }
        }

        Log::info("✅ Avis de retard envoyés", [
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
     * ✅ NOUVEAU : Créer et envoyer un rappel dans la messagerie
     */
    private function creerEtEnvoyerRappel(Location $location, Carbon $dateEcheance, Carbon $dateLimite)
    {
        // 1. Créer l'enregistrement dans avis_retards
        $avis = AvisRetard::create([
            'location_id' => $location->id,
            'type' => 'rappel',
            'mois_concerne' => $dateEcheance->format('Y-m'),
            'date_echeance' => $dateEcheance,
            'date_limite_paiement' => $dateLimite,
            'montant_du' => $location->loyer_mensuel,
            'penalites' => 0,
            'statut' => 'envoye',
            'date_envoi' => now()
        ]);

        // 2. ✅ NOUVEAU : Envoyer le message dans la plateforme
        $this->envoyerMessageRappel($location, $dateEcheance, $dateLimite);

        // 3. Envoyer aussi par email (optionnel)
        // $location->client->notify(new RappelPaiementLoyer($location, $dateLimite));

        Log::info("✅ Rappel créé et envoyé", [
            'avis_id' => $avis->id,
            'location_id' => $location->id,
            'mois_concerne' => $dateEcheance->format('Y-m')
        ]);
    }

    /**
     * ✅ NOUVEAU : Créer et envoyer un avis de retard dans la messagerie
     */
    private function creerEtEnvoyerAvisRetard(Location $location, Carbon $dateEcheance, int $joursRetard, float $montantRestant)
    {
        $penalites = $this->calculerPenalites($location->loyer_mensuel);
        $montantTotal = $montantRestant + $penalites;

        // 1. Créer l'enregistrement dans avis_retards
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

        // 2. ✅ NOUVEAU : Envoyer le message dans la plateforme
        $this->envoyerMessageAvisRetard($location, $dateEcheance, $joursRetard, $penalites, $montantTotal);

        Log::info("✅ Avis de retard créé et envoyé", [
            'avis_id' => $avis->id,
            'location_id' => $location->id,
            'jours_retard' => $joursRetard,
            'montant_total' => $montantTotal
        ]);
    }

    /**
     * ✅ NOUVEAU : Envoyer un message de rappel dans la plateforme
     */
    private function envoyerMessageRappel(Location $location, Carbon $dateEcheance, Carbon $dateLimite)
    {
        $admin = User::role('admin')->first();
        if (!$admin) return;

        $sujet = "💰 Rappel de paiement - " . $dateEcheance->translatedFormat('F Y');

        $message = "Bonjour,\n\n";
        $message .= "Ceci est un rappel concernant le paiement de votre loyer.\n\n";
        $message .= "**Détails du paiement :**\n";
        $message .= "- Mois concerné : **" . $dateEcheance->translatedFormat('F Y') . "**\n";
        $message .= "- Montant : **" . number_format($location->loyer_mensuel, 0, ',', ' ') . " FCFA**\n";
        $message .= "- Date limite : **" . $dateLimite->translatedFormat('d F Y') . "**\n";
        $message .= "- Jours restants : **" . now()->diffInDays($dateLimite) . " jours**\n\n";
        $message .= "Merci de procéder au paiement avant la date limite pour éviter des pénalités de retard.\n\n";
        $message .= "Cordialement,\n";
        $message .= "L'équipe Cauris Immo";

        $this->creerConversationSysteme($location->client, $admin, $sujet, $message);
    }

    /**
     * ✅ NOUVEAU : Envoyer un message d'avis de retard dans la plateforme
     */
    private function envoyerMessageAvisRetard(Location $location, Carbon $dateEcheance, int $joursRetard, float $penalites, float $montantTotal)
    {
        $admin = User::role('admin')->first();
        if (!$admin) return;

        $sujet = "⚠️ AVIS DE RETARD - " . $dateEcheance->translatedFormat('F Y');

        $message = "Bonjour,\n\n";
        $message .= "**AVIS DE RETARD DE PAIEMENT**\n\n";
        $message .= "Votre loyer du mois de **" . $dateEcheance->translatedFormat('F Y') . "** n'a pas été réglé.\n\n";
        $message .= "**Détails du retard :**\n";
        $message .= "- Mois concerné : **" . $dateEcheance->translatedFormat('F Y') . "**\n";
        $message .= "- Montant du loyer : **" . number_format($location->loyer_mensuel, 0, ',', ' ') . " FCFA**\n";
        $message .= "- Jours de retard : **{$joursRetard} jour(s)**\n";
        $message .= "- Pénalités (2%) : **" . number_format($penalites, 0, ',', ' ') . " FCFA**\n";
        $message .= "- **MONTANT TOTAL À PAYER : " . number_format($montantTotal, 0, ',', ' ') . " FCFA**\n\n";
        $message .= "Conformément aux conditions de votre contrat, une pénalité de 2% du loyer mensuel a été appliquée.\n\n";
        $message .= "Merci de régulariser votre situation dans les plus brefs délais.\n\n";
        $message .= "Cordialement,\n";
        $message .= "L'équipe Cauris Immo";

        $this->creerConversationSysteme($location->client, $admin, $sujet, $message);
    }

    /**
     * ✅ NOUVEAU : Créer une conversation système pour les notifications
     */
    private function creerConversationSysteme(User $client, User $admin, string $sujet, string $message)
    {
        DB::beginTransaction();

        try {
            // Créer la conversation
            $conversation = Conversation::create([
                'client_id' => $client->id,
                'admin_id' => $admin->id,
                'subject' => $sujet,
                'status' => 'active',
                'last_message_at' => now(),
            ]);

            // Ajouter les participants
            $conversation->participants()->attach($client->id);
            $conversation->participants()->attach($admin->id);

            // Créer le message
            $conversation->messages()->create([
                'sender_id' => $admin->id,
                'message' => $message,
                'type' => 'text',
                'is_read' => false,
            ]);

            // Incrémenter le compteur non lu pour le client
            $conversation->participantDetails()
                ->where('user_id', $client->id)
                ->first()
                ?->incrementUnread();

            DB::commit();

            Log::info("✅ Message système envoyé", [
                'conversation_id' => $conversation->id,
                'client_id' => $client->id,
                'sujet' => $sujet
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error("❌ Erreur création message système", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifier si le loyer du mois est payé
     */
    private function loyerMoisPaye(Location $location, string $moisConcerne): bool
    {
        $montantPaye = \App\Models\Paiement::where('location_id', $location->id)
            ->where('type', 'loyer_mensuel')
            ->whereYear('created_at', Carbon::parse($moisConcerne)->year)
            ->whereMonth('created_at', Carbon::parse($moisConcerne)->month)
            ->where('statut', 'reussi')
            ->sum('montant_paye');

        return $montantPaye >= $location->loyer_mensuel;
    }

    /**
     * Calculer le montant restant
     */
    private function calculerMontantRestant(Location $location, string $moisConcerne): float
    {
        $montantPaye = \App\Models\Paiement::where('location_id', $location->id)
            ->where('type', 'loyer_mensuel')
            ->whereYear('created_at', Carbon::parse($moisConcerne)->year)
            ->whereMonth('created_at', Carbon::parse($moisConcerne)->month)
            ->where('statut', 'reussi')
            ->sum('montant_paye');

        return max(0, $location->loyer_mensuel - $montantPaye);
    }

    /**
     * Calculer les pénalités
     */
    private function calculerPenalites(float $loyerMensuel): float
    {
        return $loyerMensuel * self::TAUX_PENALITE;
    }

    /**
     * Vérifier si un rappel a déjà été envoyé
     */
    private function rappelDejaEnvoye(Location $location, string $moisConcerne): bool
    {
        return AvisRetard::where('location_id', $location->id)
            ->where('type', 'rappel')
            ->where('mois_concerne', $moisConcerne)
            ->exists();
    }

    /**
     * Vérifier si un avis de retard a déjà été envoyé
     */
    private function avisRetardDejaEnvoye(Location $location, string $moisConcerne): bool
    {
        return AvisRetard::where('location_id', $location->id)
            ->where('type', 'retard')
            ->where('mois_concerne', $moisConcerne)
            ->exists();
    }
}
