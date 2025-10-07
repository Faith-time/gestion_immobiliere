<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('bien_id')->nullable()->constrained('biens')->onDelete('set null');
            $table->string('subject')->nullable(); // Sujet de la conversation
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
