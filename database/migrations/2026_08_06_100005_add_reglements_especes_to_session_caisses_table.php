<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_caisses', function (Blueprint $table) {
            // Clôture = fond de caisse + ventes espèces + règlements clients
            // espèces (règle 10). Colonne distincte de total_ventes_especes
            // pour garder le détail des deux sources dans le rapport de
            // clôture.
            $table->unsignedInteger('total_reglements_especes')->default(0)->after('total_ventes_especes');
        });
    }

    public function down(): void
    {
        Schema::table('session_caisses', function (Blueprint $table) {
            $table->dropColumn('total_reglements_especes');
        });
    }
};
