<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mandats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bien_id');
            $table->enum('type_mandat', ['vente', 'gestion_locative']);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('commission_pourcentage', 5, 2)->default(0); // Commission en %
            $table->decimal('commission_fixe', 10, 2)->default(0); // Commission fixe
            $table->text('conditions_particulieres')->nullable();
            $table->enum('statut', ['actif', 'expire', 'resilie'])->default('actif');
            $table->timestamps();

            $table->foreign('bien_id')->references('id')->on('biens')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mandats');
    }
};
