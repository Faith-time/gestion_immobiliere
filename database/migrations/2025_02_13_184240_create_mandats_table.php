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
        Schema::create('mandats', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Bien::class)->constrained()->cascadeOnDelete();
            $table->foreignId('proprietaire_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->enum('statut',['En attente','Validé','Refusé'])->default('En attente');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mandats');
    }
};
