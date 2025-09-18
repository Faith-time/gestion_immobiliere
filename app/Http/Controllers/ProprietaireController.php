<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class ProprietaireController extends Controller
{
    public function create()
    {
        return Inertia::render('Proprietaire/Demande');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Debug avant
        \Log::info('Avant attribution:', [
            'user_id' => $user->id,
            'roles_before' => $user->roles->pluck('name')->toArray()
        ]);

        if (!$user->hasRole('proprietaire') && !$user->hasRole('admin')) {
            $proprietaireRole = Role::firstOrCreate(['name' => 'proprietaire']);
            $user->assignRole('proprietaire');

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            // Recharger depuis la DB
            $user = $user->fresh(['roles']);
            auth()->setUser($user);

            // Debug après
            \Log::info('Après attribution:', [
                'user_id' => $user->id,
                'roles_after' => $user->roles->pluck('name')->toArray(),
                'has_proprietaire' => $user->hasRole('proprietaire')
            ]);
        }

        return redirect()->route('biens.index')
            ->with('success', 'Vous pouvez maintenant ajouter vos biens pour les faire gérer dans notre agence');
    }

}
