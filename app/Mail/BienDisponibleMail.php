<?php

namespace App\Mail;

use App\Models\Bien;
use App\Models\Appartement;
use App\Models\ClientDossier;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BienDisponibleMail extends Mailable
{
    use Queueable, SerializesModels;

    public $bien;
    public $appartement;
    public $dossier;

    public function __construct(Bien $bien, ClientDossier $dossier, Appartement $appartement)
    {
        $this->bien = $bien;
        $this->appartement = $appartement;
        $this->dossier = $dossier;
    }

    public function build()
    {
        return $this->subject('🎉 Un appartement correspondant à vos critères est disponible !')
            ->view('emails.bien_disponible');
    }
}
