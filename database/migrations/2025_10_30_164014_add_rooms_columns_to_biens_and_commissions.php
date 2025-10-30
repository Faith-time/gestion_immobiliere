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
        // Ajouter les colonnes dans la table biens
        Schema::table('biens', function (Blueprint $table) {
            $table->integer('kitchens')->nullable()->after('bathrooms')->comment('Nombre de cuisines');
            $table->integer('living_rooms')->nullable()->after('kitchens')->comment('Nombre de salons');
        });

        // Ajouter les colonnes dans la table commissions
        Schema::table('commissions', function (Blueprint $table) {
            $table->integer('nbchambres')->nullable()->after('notes')->comment('Nombre de chambres du bien');
            $table->integer('nbsalons')->nullable()->after('nbchambres')->comment('Nombre de salons du bien');
            $table->integer('nbcuisines')->nullable()->after('nbsalons')->comment('Nombre de cuisines du bien');
            $table->integer('nbsalledebains')->nullable()->after('nbcuisines')->comment('Nombre de salles de bains du bien');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les colonnes de la table biens
        Schema::table('biens', function (Blueprint $table) {
            $table->dropColumn(['kitchens', 'living_rooms']);
        });

        // Supprimer les colonnes de la table commissions
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropColumn(['nbchambres', 'nbsalons', 'nbcuisines', 'nbsalledebains']);
        });
    }
};
