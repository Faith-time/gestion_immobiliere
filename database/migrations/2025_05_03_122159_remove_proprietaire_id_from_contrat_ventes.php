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
        Schema::table('contrat_ventes', function (Blueprint $table) {
            $table->dropForeign(['proprietaire_id']);
            $table->dropColumn('proprietaire_id');
            $table->dropForeign(['vente_id']);
            $table->dropColumn('vente_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contrat_ventes', function (Blueprint $table) {
            $table->foreignId('proprietaire_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('vente_id')->constrained('ventes')->onDelete('cascade');
        });
    }
};
