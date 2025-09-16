<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Réinitialiser le cache des permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer les rôles
        $adminRole = Role::create(['name' => 'admin']);
        $agentRole = Role::create(['name' => 'agent']);
        $proprietaireRole = Role::create(['name' => 'proprietaire']);
        $acheteurRole = Role::create(['name' => 'acheteur']);
        $locataireRole = Role::create(['name' => 'locataire']);
        $visitorRole = Role::create(['name' => 'visiteur']);

    }
}
