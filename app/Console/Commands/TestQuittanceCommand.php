<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Paiement;
use App\Services\QuittanceService;

class TestQuittanceCommand extends Command
{
    protected $signature = 'quittance:test {paiement}';
    protected $description = 'Tester l\'envoi d\'une quittance';

    public function handle(QuittanceService $quittanceService)
    {
        $paiementId = $this->argument('paiement');
        $paiement = Paiement::find($paiementId);

        if (!$paiement) {
            $this->error('Paiement introuvable');
            return 1;
        }

        $this->info('Envoi de la quittance...');

        if ($paiement->type === 'loyer_mensuel' && $paiement->location) {
            $resultat = $quittanceService->genererEtEnvoyerQuittanceLoyer($paiement);
        } elseif ($paiement->type === 'vente' && $paiement->vente) {
            $resultat = $quittanceService->genererEtEnvoyerRecuVente($paiement->vente, $paiement);
        } else {
            $this->error('Type de paiement non supporté');
            return 1;
        }

        if ($resultat['success']) {
            $this->info('✅ ' . $resultat['message']);
            return 0;
        } else {
            $this->error('❌ ' . $resultat['message']);
            return 1;
        }
    }
}
