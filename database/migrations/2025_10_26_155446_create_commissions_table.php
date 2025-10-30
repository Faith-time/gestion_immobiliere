<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SYSTÈME CORRIGÉ:
     * - Client paye le prix affiché (montant_base)
     * - Agence prend 10% (montant_commission)
     * - Propriétaire reçoit 90% (montant_net_proprietaire)
     */
    public function up(): void
    {
        Schema::create('commissions', function (Blueprint $table) {
            $table->id();

            // Type de transaction
            $table->enum('type', ['location', 'vente']);

            // Relations polymorphiques
            $table->morphs('commissionable');

            // Bien concerné
            $table->foreignId('bien_id')->constrained()->onDelete('cascade');

            // Pour les locations: commission mensuelle
            $table->integer('mois_numero')->nullable(); // Numéro du mois (1, 2, 3...)
            $table->date('mois_concerne')->nullable(); // Date du mois concerné

            // MONTANTS - RÉPARTITION
            $table->decimal('montant_base', 12, 2); // Ce que le CLIENT PAYE (ex: 350,000)
            $table->decimal('taux_commission', 5, 2)->default(10.00); // Toujours 10%
            $table->decimal('montant_commission', 12, 2); // 10% pour AGENCE (ex: 35,000)
            $table->decimal('montant_net_proprietaire', 12, 2); // 90% pour PROPRIO (ex: 315,000)

            // Statut de paiement
            $table->enum('statut', ['en_attente', 'payee', 'annulee'])->default('en_attente');
            $table->timestamp('date_paiement')->nullable();
            $table->foreignId('paiement_id')->nullable()->constrained()->onDelete('set null');

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();

            // Index pour recherches rapides
            $table->index(['type', 'statut']);
            $table->index('mois_concerne');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commissions');
    }
};
