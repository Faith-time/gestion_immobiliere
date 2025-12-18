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
        Schema::table('visites', function (Blueprint $table) {
            // Ajouter la colonne appartement_id si elle n'existe pas
            if (!Schema::hasColumn('visites', 'appartement_id')) {
                $table->unsignedBigInteger('appartement_id')->nullable()->after('bien_id');
                $table->foreign('appartement_id')->references('id')->on('appartements')->onDelete('cascade');
            }

            // Ajouter les colonnes de traçabilité si elles n'existent pas
            if (!Schema::hasColumn('visites', 'notes_admin')) {
                $table->text('notes_admin')->nullable()->after('message');
            }

            if (!Schema::hasColumn('visites', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable()->after('notes_admin');
            }

            if (!Schema::hasColumn('visites', 'motif_annulation')) {
                $table->text('motif_annulation')->nullable()->after('motif_rejet');
            }

            if (!Schema::hasColumn('visites', 'commentaire_visite')) {
                $table->text('commentaire_visite')->nullable()->after('motif_annulation');
            }

            // Colonnes de dates et utilisateurs
            if (!Schema::hasColumn('visites', 'confirmee_at')) {
                $table->timestamp('confirmee_at')->nullable()->after('commentaire_visite');
            }

            if (!Schema::hasColumn('visites', 'confirmee_par')) {
                $table->unsignedBigInteger('confirmee_par')->nullable()->after('confirmee_at');
                $table->foreign('confirmee_par')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('visites', 'rejetee_at')) {
                $table->timestamp('rejetee_at')->nullable()->after('confirmee_par');
            }

            if (!Schema::hasColumn('visites', 'rejetee_par')) {
                $table->unsignedBigInteger('rejetee_par')->nullable()->after('rejetee_at');
                $table->foreign('rejetee_par')->references('id')->on('users')->onDelete('set null');
            }

            if (!Schema::hasColumn('visites', 'effectuee_at')) {
                $table->timestamp('effectuee_at')->nullable()->after('rejetee_par');
            }

            if (!Schema::hasColumn('visites', 'effectuee_par')) {
                $table->unsignedBigInteger('effectuee_par')->nullable()->after('effectuee_at');
                $table->foreign('effectuee_par')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visites', function (Blueprint $table) {
            // Supprimer les clés étrangères d'abord
            $table->dropForeign(['appartement_id']);
            $table->dropForeign(['confirmee_par']);
            $table->dropForeign(['rejetee_par']);
            $table->dropForeign(['effectuee_par']);

            // Supprimer les colonnes
            $table->dropColumn([
                'appartement_id',
                'notes_admin',
                'motif_rejet',
                'motif_annulation',
                'commentaire_visite',
                'confirmee_at',
                'confirmee_par',
                'rejetee_at',
                'rejetee_par',
                'effectuee_at',
                'effectuee_par'
            ]);
        });
    }
};
