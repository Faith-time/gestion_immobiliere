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
        Schema::create('transfert_proprietes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bien_id')->constrained('biens');
            $table->foreignId('ancien_proprietaire_id')->constrained('users');
            $table->foreignId('nouveau_proprietaire_id')->constrained('users');
            $table->date('date_transfert');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfert_proprietes');
    }
};
