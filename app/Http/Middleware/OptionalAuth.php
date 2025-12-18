<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Si l'utilisateur est déjà authentifié et n'est pas un guest
        if (Auth::check() && !Auth::user()->is_guest) {
            return $next($request);
        }

        // Si l'utilisateur est authentifié mais est un guest
        if (Auth::check() && Auth::user()->is_guest) {
            // Vérifier si la session correspond toujours
            $currentSessionId = session()->getId();
            $guestSessionId = Auth::user()->session_id;

            // Si les sessions ne correspondent pas, déconnecter et créer un nouveau guest
            if ($currentSessionId !== $guestSessionId) {
                Auth::logout();
            } else {
                return $next($request);
            }
        }

        // Créer ou récupérer un utilisateur visiteur
        $sessionId = session()->getId();

        // Vérifier si un visiteur existe déjà pour cette session
        $guest = User::where('session_id', $sessionId)
            ->where('is_guest', true)
            ->first();

        if (!$guest) {
            // Créer un nouvel utilisateur visiteur
            $guestNumber = rand(100000, 999999);

            $guest = User::create([
                'name' => 'Visiteur_' . $guestNumber,
                'email' => 'guest_' . $guestNumber . '@temporary.local',
                'password' => bcrypt(Str::random(32)), // Mot de passe aléatoire non utilisable
                'is_guest' => true,
                'session_id' => $sessionId,
                'email_verified_at' => null,
            ]);

            // Assigner le rôle visiteur
            $guest->assignRole('visiteur');

            \Log::info('Nouveau visiteur créé', [
                'guest_id' => $guest->id,
                'session_id' => $sessionId
            ]);
        }

        // Connecter automatiquement le visiteur
        Auth::login($guest);

        return $next($request);
    }
}
