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
            Schema::create('documents', function (Blueprint $table) {
                $table->id();
                $table->string('nom_fichier');
                $table->string('url_doc_pdf');
                $table->enum('type', ['contrat_location', 'contrat_vente', 'mandat_location', 'mandat_vente']);
                $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doc');
    }
};
