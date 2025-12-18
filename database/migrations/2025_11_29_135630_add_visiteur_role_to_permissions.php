<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Créer le rôle Visiteur
        Role::firstOrCreate(['name' => 'visiteur', 'guard_name' => 'web']);
    }

    public function down(): void
    {
        Role::where('name', 'visiteur')->delete();
    }
};
