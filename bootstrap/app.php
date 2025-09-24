<?php

use App\Http\Middleware\HandleInertiaRequests;
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
    })->create();
?>
