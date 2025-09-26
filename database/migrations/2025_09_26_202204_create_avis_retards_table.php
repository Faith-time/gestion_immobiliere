<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('avis_retards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained('locations')->onDelete('cascade');
            $table->enum('type', ['rappel', 'retard'])->default('rappel');
            $table->date('date_echeance');
            $table->decimal('montant_du', 10, 2);
            $table->integer('jours_retard')->default(0);
            $table->enum('statut', ['envoye', 'paye', 'annule'])->default('envoye');
            $table->timestamp('date_envoi')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->text('commentaires')->nullable();
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['location_id', 'type']);
            $table->index(['date_echeance']);
            $table->index(['statut']);
            $table->index(['type', 'statut']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('avis_retards');
    }
};
