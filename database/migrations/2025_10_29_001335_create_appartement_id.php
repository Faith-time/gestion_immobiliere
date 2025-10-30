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
        Schema::table('images', function (Blueprint $table) {
            // Ajouter la colonne appartement_id (nullable car images générales du bien)
            $table->unsignedBigInteger('appartement_id')->nullable()->after('bien_id');

            // Ajouter la clé étrangère vers la table appartements
            $table->foreign('appartement_id')
                ->references('id')
                ->on('appartements')
                ->onDelete('set null'); // Si appartement supprimé, image reste liée au bien

            // Index pour améliorer les performances des requêtes
            $table->index('appartement_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('images', function (Blueprint $table) {
            // Supprimer la clé étrangère
            $table->dropForeign(['appartement_id']);

            // Supprimer la colonne
            $table->dropColumn('appartement_id');
        });
    }
};
