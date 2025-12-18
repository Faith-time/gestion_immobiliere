<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class VisiteurRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer le rôle visiteur s'il n'existe pas
        $visiteurRole = Role::firstOrCreate(['name' => 'visiteur']);

        // Définir les permissions pour les visiteurs
        $visiteurPermissions = [
            // Navigation publique
            'view_home',
            'view_catalogue',
            'view_bien_details',

            // Filtres et recherche
            'use_filters',
            'search_biens',
        ];

        // Créer les permissions si elles n'existent pas
        foreach ($visiteurPermissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName]);

            // Assigner la permission au rôle visiteur
            if (!$visiteurRole->hasPermissionTo($permission)) {
                $visiteurRole->givePermissionTo($permission);
            }
        }

        $this->command->info('✓ Rôle visiteur créé avec succès avec ' . count($visiteurPermissions) . ' permissions');

        // Afficher les permissions
        $this->command->info('Permissions du visiteur :');
        foreach ($visiteurPermissions as $perm) {
            $this->command->line("  - {$perm}");
        }
    }
}
