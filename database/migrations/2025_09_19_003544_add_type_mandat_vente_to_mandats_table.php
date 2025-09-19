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
            $table->enum('type_mandat_vente', ['exclusif', 'simple', 'semi_exclusif'])
                ->nullable()
                ->after('type_mandat')
                ->comment('Type spécifique de mandat de vente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mandats', function (Blueprint $table) {
            $table->dropColumn('type_mandat_vente');
        });
    }
};
