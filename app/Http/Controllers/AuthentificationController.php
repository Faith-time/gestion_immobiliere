<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class AuthentificationController extends Controller
{
    public function showRegister()
    {
        return inertia('Auth/Register');
    }

    public function register(Request $request)
    {
        $validateData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        // ✅ Si l'utilisateur était un visiteur, on le supprime
        if (Auth::check() && Auth::user()->is_guest) {
            $oldGuest = Auth::user();
            Auth::logout();
            $oldGuest->delete();
        }

        $user = User::create([
            'name' => $validateData['name'],
            'email' => $validateData['email'],
            'password' => Hash::make($validateData['password']),
            'is_guest' => false,
        ]);

        // ✅ Assigner le rôle Client par défaut
        $user->assignRole('client');

        Auth::login($user);

        return Inertia::location(route('home'));
    }

    // ✅ MODIFICATION ICI - Passer le message à la vue
    public function showLogin()
    {
        return inertia('Auth/Login', [
            'message' => session('message') // ⬅️ NOUVEAU
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // ✅ Si l'utilisateur était un visiteur, on le supprime
        if (Auth::check() && Auth::user()->is_guest) {
            $oldGuest = Auth::user();
            Auth::logout();
            $oldGuest->delete();
        }

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // ✅ S'assurer que l'utilisateur n'est pas un visiteur
            if (Auth::user()->is_guest) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Erreur d\'authentification.',
                ]);
            }

            $defaultRoute = route('home');
            $intendedRoute = redirect()->intended($defaultRoute)->getTargetUrl();
            return Inertia::Location($intendedRoute);
        }

        return back()->withErrors([
            'email' => 'Email et/ou mot de passe incorrect.',
        ]);
    }

    public function logout(Request $request)
    {
        $wasGuest = Auth::user()->is_guest ?? false;

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($wasGuest) {
            return redirect()->route('home');
        }

        return redirect()->route('login');
    }
}
