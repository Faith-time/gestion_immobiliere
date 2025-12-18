<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_dossiers', function (Blueprint $table) {
            // Remplacer ancien champ
            $table->dropColumn(['photo_cni', 'derniere_quittance_loyer']); // Si booléens

            // Ajouter nouveaux champs
            $table->string('carte_identite_path')->nullable();
            $table->string('derniere_quittance_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_dossiers', function (Blueprint $table) {
            //
        });
    }
};
