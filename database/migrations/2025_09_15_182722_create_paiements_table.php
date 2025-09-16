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
            $table->enum('type', ['vente', 'location']);
            $table->double('montant_total');
            $table->double('montant_paye')->default(0);
            $table->double('montant_restant')->default(0);
            $table->float('commission_agence')->default(0);

            // Intégration CinetPay
            $table->string('transaction_id')->nullable(); // ID retourné par CinetPay
            $table->enum('mode_paiement', ['carte', 'mobile_money', 'virement', 'cash'])->nullable();
            $table->enum('statut', ['en_attente', 'reussi', 'echoue'])->default('en_attente');
            $table->dateTime('date_transaction');

            // Lier à vente ou location
            $table->foreignId('vente_id')->nullable()->constrained('ventes')->onDelete('cascade');
            $table->foreignId('location_id')->nullable()->constrained('locations')->onDelete('cascade');

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
