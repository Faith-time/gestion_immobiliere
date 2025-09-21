<?php

namespace App\Providers;

use App\Services\ElectronicSignatureService;
use App\Services\MandatPdfService;
use Illuminate\Pagination\Paginator;
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
       Paginator::useBootstrapFive();
    }
}
