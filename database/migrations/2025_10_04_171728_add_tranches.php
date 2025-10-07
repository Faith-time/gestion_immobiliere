<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('paiement_tranches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paiement_id')->constrained()->onDelete('cascade');
            $table->integer('numero_tranche');
            $table->integer('total_tranches');
            $table->decimal('montant_tranche', 15, 2);
            $table->string('transaction_id')->nullable();
            $table->enum('statut', ['en_attente', 'reussi', 'echoue'])->default('en_attente');
            $table->timestamp('date_paiement')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('paiement_tranches');
    }
};
