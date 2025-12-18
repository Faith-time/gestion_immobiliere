<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanupGuestUsers extends Command
{
    protected $signature = 'guests:cleanup';
    protected $description = 'Nettoyer les visiteurs inactifs depuis plus de 24h';

    public function handle()
    {
        $deleted = User::where('is_guest', true)
            ->where('updated_at', '<', now()->subDay())
            ->delete();

        $this->info("$deleted visiteurs inactifs supprimés.");

        return CommandAlias::SUCCESS;
    }
}
