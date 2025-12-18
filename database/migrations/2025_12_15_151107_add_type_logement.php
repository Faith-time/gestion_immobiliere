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
            // Modifier la colonne type_logement pour accepter une valeur string au lieu d'un JSON
            // En gardant aussi la compatibilité avec les anciennes données
            $table->string('type_logement', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_dossiers', function (Blueprint $table) {
            // Revenir au type JSON si nécessaire
            $table->json('type_logement')->nullable()->change();
        });
    }
};
