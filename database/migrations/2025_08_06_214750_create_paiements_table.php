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
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users'); // Corrigé : clients au lieu de users
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->double('montant');
            $table->enum('mode_paiement', ['especes', 'wave', 'orange_money', 'free_money', 'wari']);
            $table->float('taux_comm_agence');
            $table->enum('statut', ['en_attente', 'en_cours', 'completee', 'en_retard', 'annulee']);
            $table->date('date_echeance');
            $table->date('date_paiement');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
