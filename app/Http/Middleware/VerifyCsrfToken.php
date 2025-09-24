<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Exclure les webhooks de paiement de la vérification CSRF
        '/paiement/notify',
        '/api/paiement/notify', // Au cas où vous utiliseriez cette route aussi
    ];
}
