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
        Schema::table('ventes', function (Blueprint $table) {
            // Ajouter reservation_id
            $table->foreignId('reservation_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Garder biens_id temporairement pour la migration des données
            // On le supprimera après avoir migré les données
        });

        Schema::table('locations', function (Blueprint $table) {
            // Ajouter reservation_id
            $table->foreignId('reservation_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Garder bien_id temporairement
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropColumn('reservation_id');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropColumn('reservation_id');
        });
    }
};
