<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        $userData = null;
        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_guest' => (bool) $user->is_guest,
                'session_id' => $user->session_id,
                'roles' => $user->roles->pluck('name')->toArray(),
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            ];

            \Log::info('🔍 Inertia sharing user data', [
                'user_id' => $user->id,
                'is_guest' => $user->is_guest,
                'is_guest_bool' => (bool) $user->is_guest,
                'roles' => $userData['roles'],
                'email' => $user->email
            ]);
        }

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $userData,
            ],

            // ✅ NE PAS ajouter Ziggy ici
            // Les routes sont injectées via @routes dans app.blade.php

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],

            'errors' => fn () => $request->session()->get('errors')
                ? $request->session()->get('errors')->getBag('default')->getMessages()
                : (object) [],
        ]);
    }
}
