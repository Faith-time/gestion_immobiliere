<?php
// database/migrations/xxxx_xx_xx_add_signature_fields_to_mandats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mandats', function (Blueprint $table) {
            // Champs pour la signature du propriétaire
            $table->text('proprietaire_signature_data')->nullable(); // Données de la signature (SVG ou base64)
            $table->timestamp('proprietaire_signed_at')->nullable(); // Date de signature
            $table->string('proprietaire_signature_ip')->nullable(); // IP lors de la signature

            // Champs pour la signature de l'agence (si nécessaire)
            $table->text('agence_signature_data')->nullable();
            $table->timestamp('agence_signed_at')->nullable();
            $table->string('agence_signature_ip')->nullable();

            // Statut de signature
            $table->enum('signature_status', ['non_signe', 'partiellement_signe', 'entierement_signe'])
                ->default('non_signe');

            // Version du document signé
            $table->string('signed_pdf_path')->nullable(); // PDF avec signatures
            $table->timestamp('final_pdf_generated_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('mandats', function (Blueprint $table) {
            $table->dropColumn([
                'proprietaire_signature_data',
                'proprietaire_signed_at',
                'proprietaire_signature_ip',
                'agence_signature_data',
                'agence_signed_at',
                'agence_signature_ip',
                'signature_status',
                'signed_pdf_path',
                'final_pdf_generated_at'
            ]);
        });
    }
};
