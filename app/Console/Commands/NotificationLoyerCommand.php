<?php

namespace App\Console\Commands;

use App\Http\Controllers\AvisRetardController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class NotificationLoyerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'loyer:notifications
                            {--type=all : Type de notification (rappels, retards, all)}
                            {--dry-run : Simulation sans envoi réel}
                            {--force : Force l\'exécution même si déjà traité aujourd\'hui}';

    /**
     * The console command description.
     */
    protected $description = 'Envoie les notifications de rappel et d\'avis de retard pour les loyers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // Vérifier si déjà traité aujourd'hui (sauf si --force)
        if (!$force && !$dryRun && $type === 'all' && Cache::get('loyer_notifications_processed_today', false)) {
            $this->info('Notifications déjà traitées aujourd\'hui. Utilisez --force pour forcer l\'exécution.');
            return Command::SUCCESS;
        }

        $this->info('🏠 Traitement des notifications de loyer...');

        if ($dryRun) {
            $this->warn('⚠️  Mode simulation activé - aucun email ne sera envoyé');
        }

        $controller = new AvisRetardController();

        try {
            $rappelsCount = 0;
            $retardsCount = 0;

            if ($type === 'rappels' || $type === 'all') {
                $this->info('📅 Traitement des rappels de paiement (J-5)...');

                if (!$dryRun) {
                    $response = $controller->envoyerRappels();
                    $rappelsCount = $response->original['rappels_envoyes'];
                } else {
                    $rappelsCount = $this->simulateRappels();
                }

                $this->info("✅ {$rappelsCount} rappel(s) de paiement traité(s)");
            }

            if ($type === 'retards' || $type === 'all') {
                $this->info('⚠️  Traitement des avis de retard...');

                if (!$dryRun) {
                    $response = $controller->envoyerAvisRetards();
                    $retardsCount = $response->original['avis_envoyes'];
                } else {
                    $retardsCount = $this->simulateRetards();
                }

                $this->info("✅ {$retardsCount} avis de retard traité(s)");
            }

            // Marquer comme traité pour la journée si c'est un traitement complet
            if (!$dryRun && $type === 'all') {
                Cache::put('loyer_notifications_processed_today', true, now()->endOfDay());
            }

            // Résumé
            $this->newLine();
            $this->info('📊 RÉSUMÉ:');
            $this->table([
                'Type', 'Nombre'
            ], [
                ['Rappels envoyés', $rappelsCount],
                ['Avis de retard envoyés', $retardsCount],
                ['Total', $rappelsCount + $retardsCount]
            ]);

            if ($dryRun) {
                $this->warn('ℹ️  Simulation terminée. Utilisez la commande sans --dry-run pour envoyer réellement les notifications.');
            } else {
                $this->info('🎉 Notifications envoyées avec succès !');

                // Log pour le suivi
                Log::info('Notifications loyer traitées via commande', [
                    'rappels' => $rappelsCount,
                    'retards' => $retardsCount,
                    'total' => $rappelsCount + $retardsCount,
                    'type' => $type,
                    'executed_at' => now(),
                    'dry_run' => $dryRun,
                    'forced' => $force
                ]);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du traitement des notifications: ' . $e->getMessage());

            Log::error('Erreur commande notifications loyer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'type' => $type,
                'dry_run' => $dryRun
            ]);

            return Command::FAILURE;
        }
    }

    /**
     * Simuler l'envoi des rappels pour le dry-run
     */
    private function simulateRappels()
    {
        $locations = \App\Models\Location::with(['client', 'bien.proprietaire'])
            ->where('statut', 'active')
            ->get();

        $count = 0;
        $dateRappel = \Carbon\Carbon::today()->addDays(5);

        foreach ($locations as $location) {
            $prochaineDatePaiement = $this->calculerProchaineDatePaiement($location);

            if ($prochaineDatePaiement && $prochaineDatePaiement->isSameDay($dateRappel)) {
                $count++;
                $this->line("  - Rappel pour {$location->client->name} - {$location->bien->title}");
            }
        }

        return $count;
    }

    /**
     * Simuler l'envoi des avis de retard pour le dry-run
     */
    private function simulateRetards()
    {
        $locations = \App\Models\Location::with(['client', 'bien.proprietaire'])
            ->where('statut', 'active')
            ->get();

        $count = 0;
        $dateAujourdhui = \Carbon\Carbon::today();

        foreach ($locations as $location) {
            $derniereDatePaiement = $this->calculerDerniereDatePaiement($location);

            if ($derniereDatePaiement && $dateAujourdhui->isAfter($derniereDatePaiement)) {
                $joursRetard = $dateAujourdhui->diffInDays($derniereDatePaiement);
                $count++;
                $this->line("  - Retard de {$joursRetard} jour(s) pour {$location->client->name} - {$location->bien->title}");
            }
        }

        return $count;
    }

    /**
     * Calculer la prochaine date de paiement pour une location
     */
    private function calculerProchaineDatePaiement(\App\Models\Location $location)
    {
        $dateDebut = \Carbon\Carbon::parse($location->date_debut);
        $maintenant = \Carbon\Carbon::now();

        $moisEcoules = $dateDebut->diffInMonths($maintenant);
        return $dateDebut->copy()->addMonths($moisEcoules + 1);
    }

    /**
     * Calculer la dernière date de paiement attendue
     */
    private function calculerDerniereDatePaiement(\App\Models\Location $location)
    {
        $dateDebut = \Carbon\Carbon::parse($location->date_debut);
        $maintenant = \Carbon\Carbon::now();

        $moisEcoules = $dateDebut->diffInMonths($maintenant);
        $jourPaiement = $dateDebut->day;
        $paiementMoisCourant = $maintenant->copy()->day($jourPaiement);

        if ($maintenant->isAfter($paiementMoisCourant)) {
            return $paiementMoisCourant;
        } else {
            return $dateDebut->copy()->addMonths($moisEcoules);
        }
    }
}
