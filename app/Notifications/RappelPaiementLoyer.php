<?php

namespace App\Notifications;

use App\Models\Location;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RappelPaiementLoyer extends Notification implements ShouldQueue
{
    use Queueable;

    protected $location;
    protected $dateEcheance;
    protected $isProprietaire;

    /**
     * Create a new notification instance.
     */
    public function __construct(Location $location, Carbon $dateEcheance, bool $isProprietaire = false)
    {
        $this->location = $location;
        $this->dateEcheance = $dateEcheance;
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
        $joursRestants = Carbon::today()->diffInDays($this->dateEcheance);

        if ($this->isProprietaire) {
            return (new MailMessage)
                ->subject('Rappel de paiement de loyer - Propriétaire')
                ->greeting('Bonjour ' . $notifiable->name)
                ->line("Ceci est un rappel concernant le paiement du loyer pour votre bien situé à {$adresseBien}.")
                ->line("Locataire: {$this->location->client->name}")
                ->line("Montant du loyer: {$montant} FCFA")
                ->line("Date d'échéance: " . $this->dateEcheance->format('d/m/Y'))
                ->line("Il reste {$joursRestants} jour(s) avant l'échéance.")
                ->line('Vous pouvez contacter votre locataire si nécessaire.')
                ->action('Voir la location', url('/locations/' . $this->location->id))
                ->line('Merci de votre attention.');
        }

        return (new MailMessage)
            ->subject('Rappel de paiement de loyer')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line("Ceci est un rappel amical concernant le paiement de votre loyer.")
            ->line("Bien loué: {$this->location->bien->title}")
            ->line("Adresse: {$adresseBien}")
            ->line("Montant du loyer: {$montant} FCFA")
            ->line("Date d'échéance: " . $this->dateEcheance->format('d/m/Y'))
            ->line("Il vous reste {$joursRestants} jour(s) pour effectuer le paiement.")
            ->action('Effectuer le paiement', url('/locations/' . $this->location->id))
            ->line('Merci de régler votre loyer dans les temps pour éviter les pénalités de retard.')
            ->line('Cordialement, l\'équipe de gestion immobilière.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'rappel_paiement_loyer',
            'location_id' => $this->location->id,
            'bien_titre' => $this->location->bien->title,
            'montant' => $this->location->loyer_mensuel,
            'date_echeance' => $this->dateEcheance->format('Y-m-d'),
            'jours_restants' => Carbon::today()->diffInDays($this->dateEcheance),
            'is_proprietaire' => $this->isProprietaire,
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType($notifiable)
    {
        return 'rappel_paiement';
    }
}
