<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration pour gérer les appartements dans un immeuble
     *
     * Fonctionnalités :
     * - Chaque bien peut avoir plusieurs appartements (du RDC au dernier étage)
     * - Chaque appartement a un numéro unique
     * - Le statut du bien est calculé selon les appartements loués
     */
    public function up()
    {
        // Table des appartements
        Schema::create('appartements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bien_id')->constrained('biens')->onDelete('cascade');
            $table->string('numero'); // Ex: "RDC", "1er", "2ème", "A1", "B2"
            $table->integer('etage'); // 0 = RDC, 1 = 1er étage, etc.
            $table->decimal('superficie', 10, 2)->nullable();
            $table->integer('nbpieces')->nullable();
            $table->integer('nbchambres')->nullable();
            $table->integer('nbsalles_bain')->nullable();
            $table->enum('statut', ['disponible', 'loue', 'reserve', 'maintenance'])->default('disponible');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['bien_id', 'numero']);
            $table->index(['bien_id', 'statut']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('appartement_id')->nullable()->after('reservation_id')->constrained('appartements')->onDelete('cascade');
            $table->foreignId('bien_id')->nullable()->after('appartement_id')->constrained('biens')->onDelete('cascade');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('appartement_id')->nullable()->after('bien_id')->constrained('appartements')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['appartement_id']);
            $table->dropColumn('appartement_id');
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['appartement_id']);
            $table->dropForeign(['bien_id']);
            $table->dropColumn(['appartement_id', 'bien_id']);
        });

        Schema::dropIfExists('appartements');
    }
};
