<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biens', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('description');
            $table->string('image')->nullable();
            $table->integer('rooms');
            $table->integer('floors');
            $table->integer('bathrooms');
            $table->string('city');
            $table->string('address');
            $table->double('superficy');
            $table->double('price');
            $table->enum('type',['Vente','Location']);
            $table->enum('status',['A vendre','Vendu','A louer','Loué'])->default('A vendre');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
