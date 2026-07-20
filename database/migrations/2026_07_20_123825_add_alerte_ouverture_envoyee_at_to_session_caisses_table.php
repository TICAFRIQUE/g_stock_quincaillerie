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
        Schema::table('session_caisses', function (Blueprint $table) {
            // Évite de renotifier à chaque exécution planifiée une session
            // déjà signalée comme restée ouverte trop longtemps.
            $table->timestamp('alerte_ouverture_envoyee_at')->nullable()->after('ecart');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_caisses', function (Blueprint $table) {
            $table->dropColumn('alerte_ouverture_envoyee_at');
        });
    }
};
