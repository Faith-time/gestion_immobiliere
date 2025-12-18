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
        Schema::table('biens', function (Blueprint $table) {
            // Ajouter les colonnes de validation si elles n'existent pas
            if (!Schema::hasColumn('biens', 'validated_at')) {
                $table->timestamp('validated_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('biens', 'validated_by')) {
                $table->foreignId('validated_by')->nullable()->after('validated_at')->constrained('users')->onDelete('set null');
            }

            // Ajouter les colonnes de rejet si elles n'existent pas
            if (!Schema::hasColumn('biens', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable()->after('validated_by');
            }

            if (!Schema::hasColumn('biens', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('motif_rejet');
            }

            if (!Schema::hasColumn('biens', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('rejected_at')->constrained('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('biens', function (Blueprint $table) {
            $table->dropColumn([
                'validated_at',
                'validated_by',
                'motif_rejet',
                'rejected_at',
                'rejected_by'
            ]);
        });
    }
};
