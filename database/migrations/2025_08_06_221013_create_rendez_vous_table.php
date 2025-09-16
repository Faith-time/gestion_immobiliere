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
            Schema::create('rendez_vous', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('users');
                $table->foreignId('client_id')->constrained('users');
                $table->dateTime('date_rv');
                $table->enum('statut', ['en_attente', 'confirmee', 'annulee']);
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};
