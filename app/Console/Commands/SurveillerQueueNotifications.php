<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SurveillerQueueNotifications extends Command
{
    protected $signature = 'queue:monitor-notifications
                           {--location= : Filtrer par location ID}
                           {--clear : Vider la queue}';

    protected $description = 'Surveiller les notifications de location programmées';

    public function handle()
    {
        if ($this->option('clear')) {
            return $this->clearQueue();
        }

        $locationId = $this->option('location');

        $this->info('🔍 État des notifications programmées');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Jobs en attente
        $jobsCount = DB::table('jobs')->count();
        $this->info("📋 Jobs en attente: {$jobsCount}");

        if ($jobsCount > 0) {
            $jobs = DB::table('jobs')
                ->select('id', 'payload', 'available_at', 'created_at')
                ->orderBy('available_at')
                ->get();

            $this->table([
                'ID', 'Disponible dans', 'Créé le'
            ], $jobs->map(function ($job) {
                $availableAt = \Carbon\Carbon::createFromTimestamp($job->available_at);
                $createdAt = \Carbon\Carbon::parse($job->created_at);

                return [
                    $job->id,
                    $availableAt->diffForHumans(),
                    $createdAt->format('H:i:s')
                ];
            })->toArray());
        }

        // Locations récemment signées
        $this->newLine();
        $this->info('📝 Contrats récemment signés');

        $recentSignedContracts = Location::where('signature_status', 'entierement_signe')
            ->where(function ($query) {
                $query->where('bailleur_signed_at', '>=', now()->subHours(2))
                    ->orWhere('locataire_signed_at', '>=', now()->subHours(2));
            })
            ->with(['client', 'bien.proprietaire'])
            ->when($locationId, function ($query, $locationId) {
                return $query->where('id', $locationId);
            })
            ->get();

        if ($recentSignedContracts->isNotEmpty()) {
            $this->table([
                'ID', 'Client', 'Propriétaire', 'Signé entièrement le', 'Status'
            ], $recentSignedContracts->map(function ($location) {
                $lastSignature = max($location->bailleur_signed_at, $location->locataire_signed_at);

                return [
                    $location->id,
                    $location->client->name,
                    $location->bien->proprietaire->name ?? 'N/A',
                    $lastSignature ? \Carbon\Carbon::parse($lastSignature)->format('d/m H:i:s') : 'N/A',
                    $location->signature_status
                ];
            })->toArray());
        } else {
            $this->warn('Aucun contrat récemment signé');
        }

        // Jobs échoués
        $failedJobsCount = DB::table('failed_jobs')->count();
        if ($failedJobsCount > 0) {
            $this->newLine();
            $this->error("❌ Jobs échoués: {$failedJobsCount}");

            $failedJobs = DB::table('failed_jobs')
                ->select('id', 'exception', 'failed_at')
                ->latest('failed_at')
                ->limit(5)
                ->get();

            foreach ($failedJobs as $job) {
                $this->line("• Job #{$job->id} - " . \Carbon\Carbon::parse($job->failed_at)->format('H:i:s'));
                $this->line("  " . substr($job->exception, 0, 100) . '...');
            }
        }

        $this->newLine();
        $this->info('💡 Commandes utiles:');
        $this->line('• php artisan queue:work --verbose (démarrer le worker)');
        $this->line('• php artisan queue:monitor-notifications --clear (vider la queue)');
        $this->line('• php artisan queue:failed (voir les jobs échoués)');

        return Command::SUCCESS;
    }

    private function clearQueue()
    {
        $this->warn('⚠️ Suppression de tous les jobs en attente...');

        if (!$this->confirm('Êtes-vous sûr de vouloir vider la queue ?')) {
            $this->info('Opération annulée');
            return Command::SUCCESS;
        }

        $deleted = DB::table('jobs')->delete();
        $this->info("✅ {$deleted} job(s) supprimé(s)");

        return Command::SUCCESS;
    }
}
