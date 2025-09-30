<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            // ❌ NE PAS ajouter 'authenticate' ici car cela affecterait toutes les routes web
        ]);

        // ✅ Configuration des alias de middleware
        $middleware->alias([
            'authenticate' => \App\Http\Middleware\Authenticate::class
        ]);

        // ✅ Exclure explicitement les routes API des middlewares web
        $middleware->group('api', [
            // Les routes API n'héritent que des middlewares de base
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        // ✅ Exclure les webhooks de CSRF et autres protections
        $middleware->validateCsrfTokens(except: [
            'api/paiement/notify',
            'api/paiement/retour/*',
            'webhooks/*', // Pour d'autres webhooks futurs
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->withCommands([
        \App\Console\Commands\NotificationLoyerCommand::class,
        \App\Console\Commands\SurveillerQueueNotifications::class,
    ])
    ->withSchedule(function (Schedule $schedule) {
        // Configuration du planificateur de tâches

        // Traitement complet une fois par jour à 8h
        $schedule->command('loyer:notifications --type=all')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                \Log::info('Notifications loyer traitées avec succès');
            })
            ->onFailure(function () {
                \Log::error('Échec du traitement des notifications loyer');
            });

        // Envoyer les rappels tous les jours à 9h (redondance pour sécurité)
        $schedule->command('loyer:notifications --type=rappels')
            ->dailyAt('09:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->skip(function () {
                // Skip si le traitement complet du matin a déjà été fait
                return \Cache::get('loyer_notifications_processed_today', false);
            });

        // Traiter les retards tous les jours à 10h (redondance pour sécurité)
        $schedule->command('loyer:notifications --type=retards')
            ->dailyAt('10:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->skip(function () {
                // Skip si le traitement complet du matin a déjà été fait
                return \Cache::get('loyer_notifications_processed_today', false);
            });

        // Nettoyer le cache à minuit pour permettre le traitement du jour suivant
        $schedule->call(function () {
            \Cache::forget('loyer_notifications_processed_today');
        })->dailyAt('00:01');

        // Optionnel: Nettoyage des anciens avis (plus de 2 ans)
        $schedule->call(function () {
            \App\Models\AvisRetard::where('created_at', '<', now()->subYears(2))->delete();
            \Log::info('Nettoyage des anciens avis de retard effectué');
        })->monthly();
    })->create();
?>
