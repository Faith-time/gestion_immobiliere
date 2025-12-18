<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequireAuthenticatedUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si l'utilisateur n'est pas connecté ou est un visiteur
        if (!Auth::check() || Auth::user()->is_guest) {
            // Sauvegarder l'URL demandée pour redirection après connexion
            session()->put('url.intended', $request->url());

            // Rediriger vers la page de connexion avec un message
            return redirect()->route('login')->with('message', 'Veuillez vous connecter pour continuer.');
        }

        return $next($request);
    }
}
