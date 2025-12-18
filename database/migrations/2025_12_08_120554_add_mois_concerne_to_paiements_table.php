<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paiements', function (Blueprint $table) {
            // Ajouter la colonne mois_concerne pour identifier le mois payé
            $table->date('mois_concerne')->nullable()->after('date_transaction');

            // Ajouter un index pour améliorer les performances
            $table->index(['location_id', 'mois_concerne', 'type']);
        });
    }

    public function down()
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropIndex(['location_id', 'mois_concerne', 'type']);
            $table->dropColumn('mois_concerne');
        });
    }
};
