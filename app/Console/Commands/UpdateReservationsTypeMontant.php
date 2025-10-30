<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateReservationsTypeMontant extends Command
{
    protected $signature = 'reservations:update-type-montant';
    protected $description = 'Mettre à jour le type_montant des réservations existantes';

    public function handle()
    {
        $this->info('🔄 Mise à jour des réservations...');

        DB::transaction(function () {
            $reservations = Reservation::with('bien.mandat')->whereNull('type_montant')->get();

            $count = 0;
            foreach ($reservations as $reservation) {
                if (!$reservation->bien || !$reservation->bien->mandat) {
                    continue;
                }

                $typeMontant = match($reservation->bien->mandat->type_mandat) {
                    'vente' => 'acompte',
                    'gestion_locative' => 'depot_garantie',
                    default => null
                };

                if ($typeMontant) {
                    $reservation->update(['type_montant' => $typeMontant]);
                    $this->info("✅ Réservation #{$reservation->id} : {$typeMontant}");
                    $count++;
                }
            }

            $this->info("✅ {$count} réservations mises à jour !");
        });

        return 0;
    }
}
