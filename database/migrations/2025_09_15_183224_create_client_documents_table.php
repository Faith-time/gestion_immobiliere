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
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();

            // Lien vers le client (user avec rôle "client")
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();

            // Type de document (CNI, passeport, justificatif de domicile, fiche de paie…)
            $table->string('type_document');

            // Fichier stocké (PDF, image…)
            $table->string('fichier_path');

            // Statut du document
            $table->enum('statut', [
                'en_attente',   // Uploadé mais pas encore validé
                'validé',       // Vérifié par l’agence
                'refusé'        // Document non conforme
            ])->default('en_attente');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
