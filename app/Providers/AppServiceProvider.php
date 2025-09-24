<?php

namespace App\Providers;

use App\Services\ElectronicSignatureService;
use App\Services\MandatPdfService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ElectronicSignatureService::class, function ($app) {
            return new ElectronicSignatureService($app->make(MandatPdfService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ✅ Définir le rate limiter 'api' manquant
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // ✅ Rate limiter spécial pour les webhooks (plus permissif)
        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(100)->by($request->ip());
        });

        Paginator::useBootstrapFive();

    }
}
