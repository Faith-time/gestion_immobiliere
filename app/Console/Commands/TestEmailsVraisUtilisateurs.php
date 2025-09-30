<?php

namespace App\Console\Commands;

use App\Services\EmailTestService;
use Illuminate\Console\Command;

class TestEmailsVraisUtilisateurs extends Command
{
    protected $signature = 'test:emails-vrais-users
                           {--location= : ID de la location à utiliser}
                           {--delai=5 : Délai en minutes pour les tests}
                           {--type=all : Type de test (rappel|retard|all)}
                           {--liste : Lister les locations disponibles}
                           {--immediat : Envoyer immédiatement sans délai}';

    protected $description = 'Tester les emails avec de vrais utilisateurs de la base mais délais réduits';

    private $emailService;

    public function __construct(EmailTestService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    public function handle()
    {
        $this->info('🧪 Test emails avec vrais utilisateurs');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Option pour lister les locations
        if ($this->option('liste')) {
            return $this->listerLocations();
        }

        // Option pour envoi immédiat
        if ($this->option('immediat')) {
            return $this->envoyerImmediatement();
        }

        // Test avec délais
        return $this->testerAvecDelais();
    }

    private function listerLocations()
    {
        $this->info('📋 Locations disponibles pour les tests :');

        $result = $this->emailService->listerLocationsDisponibles();

        if (!$result['success'] || $result['total'] === 0) {
            $this->error('❌ Aucune location trouvée dans la base de données');
            return;
        }

        $headers = ['ID', 'Bien', 'Client', 'Email Client', 'Propriétaire', 'Email Propriétaire', 'Loyer'];
        $rows = collect($result['locations'])->map(function ($location) {
            return [
                $location['id'],
                $location['bien'],
                $location['client'],
                $location['client_email'],
                $location['proprietaire'],
                $location['proprietaire_email'],
                $location['loyer']
            ];
        })->toArray();

        $this->table($headers, $rows);

        $this->info("\n✅ Total: {$result['total']} location(s) disponible(s)");
        $this->line("\nUtilisez: php artisan test:emails-vrais-users --location=ID");
    }

    private function envoyerImmediatement()
    {
        $locationId = $this->option('location') ?: $this->ask('ID de la location');
        $type = $this->option('type') === 'all' ? $this->choice('Type d\'email', ['rappel', 'retard']) : $this->option('type');

        $this->info("📤 Envoi immédiat - Type: {$type}");

        $result = $this->emailService->envoyerImmediatement($locationId, $type);

        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            $this->line("📧 Destinataires:");
            $this->line("   • Client: {$result['destinataires']['client']}");
            $this->line("   • Propriétaire: {$result['destinataires']['proprietaire']}");
            $this->warn("🔍 Vérifiez votre inbox Mailtrap: https://mailtrap.io/inboxes");
        } else {
            $this->error("❌ {$result['message']}");
        }
    }

    private function testerAvecDelais()
    {
        $locationId = $this->option('location');
        $delaiMinutes = (int) $this->option('delai');
        $type = $this->option('type');

        $this->info("⏱️  Test avec délais réduits ({$delaiMinutes} minutes)");

        if (!$locationId) {
            $this->warn('💡 Aucune location spécifiée, utilisation de la première trouvée');
        }

        $result = $this->emailService->testerAvecVraisUtilisateurs($locationId, $delaiMinutes);

        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            $this->line("\n📋 Détails du test:");
            $this->line("   • Location ID: {$result['details']['location_id']}");
            $this->line("   • Client: {$result['details']['client']} ({$result['details']['client_email']})");
            $this->line("   • Propriétaire: {$result['details']['proprietaire']} ({$result['details']['proprietaire_email']})");
            $this->line("   • Rappel dans: {$result['details']['rappel_dans']}");
            $this->line("   • Avis retard dans: {$result['details']['avis_retard_dans']}");

            $this->warn("\n🔍 Vérifiez votre inbox Mailtrap: https://mailtrap.io/inboxes");
            $this->info("⏳ Les emails arriveront dans {$delaiMinutes} et " . ($delaiMinutes * 2) . " minutes");
        } else {
            $this->error("❌ {$result['message']}");

            if (str_contains($result['message'], 'Aucune location trouvée')) {
                $this->line("\n💡 Suggestions:");
                $this->line("1. Créez d'abord des locations dans votre base de données");
                $this->line("2. Ou utilisez: php artisan test:emails-vrais-users --liste");
            }
        }
    }
}
