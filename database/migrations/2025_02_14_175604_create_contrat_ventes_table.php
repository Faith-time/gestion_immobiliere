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
        Schema::create('contrat_ventes', function (Blueprint $table) {
            $table->id();
            $table->enum('type',['location','vente']);
            $table->foreignIdFor(\App\Models\Bien::class)->constrained()->cascadeOnDelete();
            $table->foreignId('proprietaire_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->date('date_vente');
            $table->enum('validation',['validated','unvalidated'])->default('unvalidated');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contrat_ventes');
    }
};
