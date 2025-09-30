<?php

namespace App\Notifications;

use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AvisRetardPaiement extends Notification implements ShouldQueue
{
    use Queueable;

    protected $location;
    protected $dateEcheance;
    protected $joursRetard;
    protected $isProprietaire;

    /**
     * Create a new notification instance.
     */
    public function __construct(Location $location, Carbon $dateEcheance, int $joursRetard, bool $isProprietaire = false)
    {
        $this->location = $location;
        $this->dateEcheance = $dateEcheance;
        $this->joursRetard = $joursRetard;
        $this->isProprietaire = $isProprietaire;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $montant = number_format($this->location->loyer_mensuel, 0, ',', ' ');
        $adresseBien = $this->location->bien->address . ', ' . $this->location->bien->city;
        $penalites = $this->calculatePenalties();
        $montantTotal = $this->location->loyer_mensuel + $penalites;

        if ($this->isProprietaire) {
            return (new MailMessage)
                ->subject('Avis de retard de paiement - Propriétaire')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("Nous vous informons qu'un retard de paiement a été constaté pour votre bien situé à {$adresseBien}.")
                ->line("Locataire: {$this->location->client->name}")
                ->line("Montant du loyer: {$montant} FCFA")
                ->line("Date d'échéance dépassée: " . $this->dateEcheance->format('d/m/Y'))
                ->line("Nombre de jours de retard: {$this->joursRetard} jour(s)")
                ->when($penalites > 0, function ($mail) use ($penalites) {
                    $penalitesFormatees = number_format($penalites, 0, ',', ' ');
                    return $mail->line("Pénalités de retard: {$penalitesFormatees} FCFA");
                })
                ->line('Nous recommandons de contacter le locataire pour régulariser la situation.')
                ->action('Voir la location', url('/locations/' . $this->location->id))
                ->line('L\'équipe de gestion immobilière.');
        }

        $mailMessage = (new MailMessage)
            ->subject('URGENT - Avis de retard de paiement de loyer')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line("⚠️ **AVIS DE RETARD DE PAIEMENT** ⚠️")
            ->line("Nous constatons un retard dans le paiement de votre loyer.")
            ->line("**Détails du retard:**")
            ->line("• Bien loué: {$this->location->bien->title}")
            ->line("• Adresse: {$adresseBien}")
            ->line("• Montant du loyer: {$montant} FCFA")
            ->line("• Date d'échéance dépassée: " . $this->dateEcheance->format('d/m/Y'))
            ->line("• Nombre de jours de retard: **{$this->joursRetard} jour(s)**");

        if ($penalites > 0) {
            $penalitesFormatees = number_format($penalites, 0, ',', ' ');
            $montantTotalFormatte = number_format($montantTotal, 0, ',', ' ');
            $mailMessage->line("• Pénalités de retard: {$penalitesFormatees} FCFA")
                ->line("• **Montant total à régler: {$montantTotalFormatte} FCFA**");
        }

        return $mailMessage
            ->line("**Veuillez régulariser votre situation dans les plus brefs délais.**")
            ->line("En cas de difficultés, contactez-nous pour trouver une solution.")
            ->action('Régler maintenant', url('/locations/' . $this->location->id))
            ->line('**Attention:** Des mesures supplémentaires pourront être prises en cas de non-règlement prolongé.')
            ->line('Cordialement, l\'équipe de gestion immobilière.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'avis_retard_paiement',
            'location_id' => $this->location->id,
            'bien_titre' => $this->location->bien->title,
            'montant' => $this->location->loyer_mensuel,
            'date_echeance' => $this->dateEcheance->format('Y-m-d'),
            'jours_retard' => $this->joursRetard,
            'penalites' => $this->calculatePenalties(),
            'montant_total' => $this->location->loyer_mensuel + $this->calculatePenalties(),
            'is_proprietaire' => $this->isProprietaire,
            'urgence' => $this->getUrgenceLevel(),
        ];
    }

    /**
     * Calculer les pénalités de retard
     */
    private function calculatePenalties()
    {
        // 2% du loyer par semaine de retard (arrondi à la semaine supérieure)
        $semaines = ceil($this->joursRetard / 7);
        return $this->location->loyer_mensuel * 0.02 * $semaines;
    }

    /**
     * Déterminer le niveau d'urgence
     */
    private function getUrgenceLevel()
    {
        if ($this->joursRetard <= 7) return 'faible';
        if ($this->joursRetard <= 14) return 'moyenne';
        if ($this->joursRetard <= 30) return 'haute';
        return 'critique';
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable)
    {
        return 'avis_retard';
    }
}
