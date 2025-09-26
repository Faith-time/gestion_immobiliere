<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('paiements', function (Blueprint $table) {

            $table->enum('mode_paiement', [
                'carte',
                'mobile_money',
                'wave',
                'orange_money',
                'mtn_money',
                'moov_money',
                'virement'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('paiements', function (Blueprint $table) {

        });
    }
};
