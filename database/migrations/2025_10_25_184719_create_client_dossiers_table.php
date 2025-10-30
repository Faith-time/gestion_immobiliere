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
        Schema::create('client_dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');

            // Profession
            $table->string('profession')->nullable();

            // Numéro CNI
            $table->string('numero_cni')->nullable();

            // Personne à contacter
            $table->string('personne_contact')->nullable();
            $table->string('telephone_contact')->nullable();

            // Revenus mensuels
            $table->enum('revenus_mensuels', [
                'plus_100000',
                'plus_200000',
                'plus_300000',
                'plus_500000'
            ])->nullable();

            // Nombre de personnes à loger
            $table->integer('nombre_personnes')->nullable();

            // Situation familiale
            $table->enum('situation_familiale', ['celibataire', 'marie'])->nullable();

            // Type de logement recherché
            $table->json('type_logement')->nullable(); // ['chambre_simple', 'salon', 'studio', '2_chambres_salon', '3_chambres_salon', '4_chambres_salon', 'magasin', 'autres']
            $table->string('type_logement_autres')->nullable();

            // Quartier souhaité
            $table->string('quartier_souhaite')->nullable();

            // Budget mensuel
            $table->decimal('budget_mensuel', 10, 2)->nullable();

            // Date d'entrée souhaitée
            $table->date('date_entree_souhaitee')->nullable();

            // Pièces à fournir (photocopies)
            $table->boolean('carte_identite')->default(false);
            $table->boolean('dernier_recu_loyer')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_dossiers');
    }
};
