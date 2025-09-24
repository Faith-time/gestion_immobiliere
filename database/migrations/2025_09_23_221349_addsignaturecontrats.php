<?php

// Migration 1: add_signature_fields_to_ventes_table.php
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
            // Champs pour la signature du vendeur
            $table->longText('vendeur_signature_data')->nullable()->after('date_vente');
            $table->timestamp('vendeur_signed_at')->nullable()->after('vendeur_signature_data');
            $table->string('vendeur_signature_ip', 45)->nullable()->after('vendeur_signed_at');

            // Champs pour la signature de l'acheteur
            $table->longText('acheteur_signature_data')->nullable()->after('vendeur_signature_ip');
            $table->timestamp('acheteur_signed_at')->nullable()->after('acheteur_signature_data');
            $table->string('acheteur_signature_ip', 45)->nullable()->after('acheteur_signed_at');

            // Statut de signature et PDF
            $table->enum('signature_status', ['non_signe', 'partiellement_signe', 'entierement_signe'])
                ->default('non_signe')->after('acheteur_signature_ip');
            $table->string('pdf_path')->nullable()->after('signature_status');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn([
                'vendeur_signature_data',
                'vendeur_signed_at',
                'vendeur_signature_ip',
                'acheteur_signature_data',
                'acheteur_signed_at',
                'acheteur_signature_ip',
                'signature_status',
                'pdf_path',
                'pdf_generated_at'
            ]);
        });
    }
};
