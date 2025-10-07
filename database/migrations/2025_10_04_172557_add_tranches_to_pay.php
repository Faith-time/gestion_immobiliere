<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->boolean('is_fractionne')->default(false)->after('statut');
            $table->integer('nombre_tranches')->nullable()->after('is_fractionne');
            $table->integer('tranche_actuelle')->default(0)->after('nombre_tranches');
        });
    }

    public function down()
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['is_fractionne', 'nombre_tranches', 'tranche_actuelle']);
        });
    }
};
