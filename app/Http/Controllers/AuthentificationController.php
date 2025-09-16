<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use function Termwind\render;

class AuthentificationController extends Controller
{
    public function showRegister(): \Inertia\Response|\Inertia\ResponseFactory
    {
        return inertia('Auth/Register');
    }

    public function register(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $validateData = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $validateData['name'],
            'email' => $validateData['email'],
            'password' => Hash::make($validateData['password']),
        ]);

        Auth::login($user);

        return Inertia::location(route('home'));
    }

    public function showLogin(): \Inertia\Response|\Inertia\ResponseFactory
    {
        return inertia('Auth/Login');
    }

    public function login(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            $defaultRoute = route('home');
            $intendesRoute = redirect()->intended($defaultRoute)->getTargetUrl();
            return Inertia::Location($intendesRoute);
        }

        return back()->withErrors([
            'email' => 'Email et/ou mot de passe incorrect.',
        ]);
    }



    public function logout(Request $request){
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return inertia('Auth/Login');
    }

}
