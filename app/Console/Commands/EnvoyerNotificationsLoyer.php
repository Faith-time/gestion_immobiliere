<?php

namespace App\Console\Commands;

use App\Services\NotificationLoyerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnvoyerNotificationsLoyer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loyer:notifications
                            {--type= : Type de notification (rappel, retard, tous)}
                            {--force : Forcer l\'envoi même si ce n\'est pas le bon jour}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envoyer automatiquement les notifications de loyer (rappels et avis de retard)';

    protected $notificationService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(NotificationLoyerService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->option('type') ?? 'tous';

        $this->info('🚀 Démarrage de l\'envoi des notifications de loyer...');
        $this->info('📅 Date: ' . now()->format('d/m/Y H:i:s'));
        $this->newLine();

        $rappelsEnvoyes = 0;
        $avisEnvoyes = 0;

        try {
            // Envoyer les rappels (J-5)
            if ($type === 'rappel' || $type === 'tous') {
                $this->info('📤 Envoi des rappels de paiement...');

                $resultRappels = $this->notificationService->envoyerRappelsMensuels();
                $rappelsEnvoyes = $resultRappels['rappels_envoyes'] ?? 0;

                if ($rappelsEnvoyes > 0) {
                    $this->info("✅ {$rappelsEnvoyes} rappel(s) envoyé(s)");
                } else {
                    $this->warn("⚠️ " . $resultRappels['message']);
                }

                $this->newLine();
            }

            // Envoyer les avis de retard (après J+10)
            if ($type === 'retard' || $type === 'tous') {
                $this->info('📤 Envoi des avis de retard...');

                $resultRetards = $this->notificationService->envoyerAvisRetards();
                $avisEnvoyes = $resultRetards['avis_envoyes'] ?? 0;

                if ($avisEnvoyes > 0) {
                    $this->info("✅ {$avisEnvoyes} avis de retard envoyé(s)");
                } else {
                    $this->warn("⚠️ " . $resultRetards['message']);
                }

                $this->newLine();
            }

            // Résumé
            $this->info('📊 RÉSUMÉ');
            $this->table(
                ['Type', 'Nombre envoyé'],
                [
                    ['Rappels', $rappelsEnvoyes],
                    ['Avis de retard', $avisEnvoyes],
                    ['TOTAL', $rappelsEnvoyes + $avisEnvoyes]
                ]
            );

            Log::info('✅ Notifications loyer envoyées', [
                'rappels' => $rappelsEnvoyes,
                'avis_retard' => $avisEnvoyes,
                'total' => $rappelsEnvoyes + $avisEnvoyes
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de l\'envoi des notifications');
            $this->error($e->getMessage());

            Log::error('❌ Erreur commande notifications loyer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 1;
        }
    }
}
