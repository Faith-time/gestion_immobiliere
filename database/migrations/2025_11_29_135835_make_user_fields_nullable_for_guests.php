<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Permettre que email et password soient nullable pour les visiteurs temporaires
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();

            // Ajouter un champ pour identifier les visiteurs temporaires
            $table->boolean('is_guest')->default(false)->after('remember_token');
            $table->string('session_id')->nullable()->after('is_guest');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
            $table->dropColumn(['is_guest', 'session_id']);
        });
    }
};
