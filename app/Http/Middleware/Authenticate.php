<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // Exclure explicitement les routes webhook CinetPay
        if ($this->isWebhookRoute($request)) {
            return null;
        }

        // ✅ Pour les requêtes Inertia, retourner la route nommée
        if ($request->hasHeader('X-Inertia')) {
            return route('login');
        }

        // Pour les requêtes API (Accept: application/json), ne pas rediriger
        if ($request->expectsJson()) {
            return null; // Ceci retournera une réponse 401 JSON
        }

        // Pour les routes web normales, rediriger vers la page de login
        return route('login'); // ✅ Utiliser route() au lieu de url()
    }

    /**
     * Handle an unauthenticated user.
     */
    protected function unauthenticated($request, array $guards)
    {
        // Permettre l'accès aux webhooks sans authentification
        if ($this->isWebhookRoute($request)) {
            return; // Ne pas lever d'exception, laisser la requête continuer
        }

        // Pour toutes les autres routes, utiliser le comportement par défaut
        parent::unauthenticated($request, $guards);
    }

    /**
     * Détermine si la requête concerne une route webhook
     */
    private function isWebhookRoute(Request $request): bool
    {
        return $request->is('api/paiement/notify') ||
            $request->is('api/paiement/retour/*') ||
            $request->routeIs('api.paiement.notify') ||
            $request->routeIs('api.paiement.retour') ||
            $request->is('api/test-*') || // Routes de test temporaires
            $request->is('webhooks/*'); // Autres webhooks futurs
    }

    /**
     * Determine if the user is logged in to any of the given guards.
     */
    protected function authenticate($request, array $guards)
    {
        // Bypasser complètement l'authentification pour les webhooks
        if ($this->isWebhookRoute($request)) {
            return;
        }

        // Pour les autres routes, vérifier l'authentification normalement
        parent::authenticate($request, $guards);
    }
}
