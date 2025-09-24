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
        Schema::table('locations', function (Blueprint $table) {
            // Champs pour la signature du bailleur (propriétaire)
            $table->longText('bailleur_signature_data')->nullable()->after('date_fin');
            $table->timestamp('bailleur_signed_at')->nullable()->after('bailleur_signature_data');
            $table->string('bailleur_signature_ip', 45)->nullable()->after('bailleur_signed_at');

            // Champs pour la signature du locataire
            $table->longText('locataire_signature_data')->nullable()->after('bailleur_signature_ip');
            $table->timestamp('locataire_signed_at')->nullable()->after('locataire_signature_data');
            $table->string('locataire_signature_ip', 45)->nullable()->after('locataire_signed_at');

            // Statut de signature et PDF
            $table->enum('signature_status', ['non_signe', 'partiellement_signe', 'entierement_signe'])
                ->default('non_signe')->after('locataire_signature_ip');
            $table->string('pdf_path')->nullable()->after('signature_status');
            $table->timestamp('pdf_generated_at')->nullable()->after('pdf_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn([
                'bailleur_signature_data',
                'bailleur_signed_at',
                'bailleur_signature_ip',
                'locataire_signature_data',
                'locataire_signed_at',
                'locataire_signature_ip',
                'signature_status',
                'pdf_path',
                'pdf_generated_at'
            ]);
        });
    }
};
