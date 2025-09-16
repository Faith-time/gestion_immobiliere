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
        Schema::table('mandats', function (Blueprint $table) {
            $table->removeColumn('type');
            $table->removeColumn('statut');
            $table->enum('type_mandat', ['vente', 'gestion_locative'])->after('biens_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mandats', function (Blueprint $table) {
            //
        });
    }
};
