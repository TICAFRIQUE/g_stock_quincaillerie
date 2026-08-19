<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Clôture = fond de caisse + ventes espèces + règlements clients espèces
     * + entrées de caisse − sorties de caisse (règle 10 étendue). Colonnes
     * distinctes, même pattern que total_reglements_especes.
     */
    public function up(): void
    {
        Schema::table('session_caisses', function (Blueprint $table) {
            $table->unsignedInteger('total_entrees_especes')->default(0)->after('total_reglements_especes');
            $table->unsignedInteger('total_sorties_especes')->default(0)->after('total_entrees_especes');
        });
    }

    public function down(): void
    {
        Schema::table('session_caisses', function (Blueprint $table) {
            $table->dropColumn(['total_entrees_especes', 'total_sorties_especes']);
        });
    }
};
