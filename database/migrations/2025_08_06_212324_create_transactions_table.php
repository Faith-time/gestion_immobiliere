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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['vente', 'location']);
            $table->date('date_transaction'); // Déplacé ici
            $table->foreignId('agent_id')->constrained('users'); // Corrigé : agents au lieu de users
            $table->foreignId('client_id')->constrained('users'); // Corrigé : clients au lieu de users
            $table->foreignId('bien_id')->constrained('biens');
            $table->decimal('montant_total', 15, 2);
            $table->decimal('montant_paye', 15, 2)->default(0);
            $table->decimal('montant_restant', 15, 2);
            $table->decimal('taux_commission_agence', 5, 2);
            $table->decimal('montant_commission', 15, 2);
            $table->enum('statut', ['en_cours', 'completee', 'annulee']);
            $table->timestamps(); // Une seule fois et à la bonne place
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
