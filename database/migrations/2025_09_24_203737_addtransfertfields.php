<?php
// database/migrations/xxxx_add_transfer_fields_to_ventes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->boolean('property_transferred')->default(false)->after('signature_status');
            $table->timestamp('property_transferred_at')->nullable()->after('property_transferred');
            $table->unsignedBigInteger('ancien_proprietaire_id')->nullable()->after('property_transferred_at');

            // Index pour optimiser les recherches
            $table->index(['property_transferred', 'property_transferred_at']);

            // Clé étrangère pour l'ancien propriétaire
            $table->foreign('ancien_proprietaire_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropForeign(['ancien_proprietaire_id']);
            $table->dropIndex(['property_transferred', 'property_transferred_at']);
            $table->dropColumn(['property_transferred', 'property_transferred_at', 'ancien_proprietaire_id']);
        });
    }
};
