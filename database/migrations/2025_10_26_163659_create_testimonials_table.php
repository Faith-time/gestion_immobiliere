<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('bien_id')->nullable()->constrained()->onDelete('set null');
            $table->text('content'); // Le témoignage
            $table->integer('rating')->default(5); // Note sur 5
            $table->string('avatar')->nullable(); // Photo du client (optionnel)
            $table->boolean('is_approved')->default(false); // Validation admin
            $table->boolean('is_featured')->default(false); // Témoignage mis en avant
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
