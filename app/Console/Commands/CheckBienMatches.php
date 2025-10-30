<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BienMatchingService;
use App\Models\ClientDossier;

class CheckBienMatches extends Command
{
    protected $signature = 'biens:check-matches {client_id?}';
    protected $description = 'Vérifie les biens correspondants pour un client';

    public function handle(BienMatchingService $matchingService)
    {
        $clientId = $this->argument('client_id');

        if ($clientId) {
            $dossier = ClientDossier::find($clientId);
            if (!$dossier) {
                $this->error("Dossier client non trouvé");
                return 1;
            }

            $biens = $matchingService->rechercherBiensCorrespondants($dossier);
            $this->info("Biens trouvés : " . $biens->count());

            foreach ($biens as $bien) {
                $this->line("- {$bien->title} ({$bien->city}) - {$bien->price} FCFA");
            }
        }

        return 0;
    }
}
