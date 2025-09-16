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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            // Référence au client qui réserve
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            // Référence au bien réservé
            $table->foreignId('bien_id')->constrained('biens')->cascadeOnDelete();
            // Montant versé comme avance/caution
            $table->decimal('montant', 15, 2)->nullable();
            // Statut de la réservation
            $table->enum('statut', [
                'en_attente',    // Réservation faite, attente de validation agence
                'confirmée',     // Validée, transformée en vente ou location
                'annulée',       // Annulée, bien redevient disponible
            ])->default('en_attente');
            // Référence au paiement associé (si fait en ligne via CinetPay)
            $table->foreignId('paiement_id')->nullable()->constrained('paiements')->nullOnDelete();
            // Dates
            $table->timestamp('date_reservation')->useCurrent();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
