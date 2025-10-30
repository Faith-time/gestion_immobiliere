<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->text('motif_refus')->nullable()->after('statut');
        });
    }

    public function down()
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropColumn('motif_refus');
        });
    }
};
