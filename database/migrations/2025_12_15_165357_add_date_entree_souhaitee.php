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
        Schema::table('client_dossiers', function (Blueprint $table) {
            // Ajouter date_entree_souhaitee après quartier_souhaite
            if (!Schema::hasColumn('client_dossiers', 'date_entree_souhaitee')) {
                $table->date('date_entree_souhaitee')->nullable()->after('quartier_souhaite');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_dossiers', function (Blueprint $table) {
            if (Schema::hasColumn('client_dossiers', 'date_entree_souhaitee')) {
                $table->dropColumn('date_entree_souhaitee');
            }
        });
    }
};
