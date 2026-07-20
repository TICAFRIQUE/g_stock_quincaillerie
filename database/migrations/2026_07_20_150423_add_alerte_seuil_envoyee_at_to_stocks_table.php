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
        // État de l'épisode d'alerte en cours (pas une simple date de
        // dernier événement) : posé à la 1re notification sous le seuil,
        // remis à null dès que le stock repasse au-dessus — permet de
        // détecter aussi bien un stock déjà sous le seuil avant l'activation
        // de cette fonctionnalité qu'un nouveau franchissement futur.
        Schema::table('stocks', function (Blueprint $table) {
            $table->timestamp('alerte_seuil_envoyee_at')->nullable()->after('cout_moyen_pondere');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('alerte_seuil_envoyee_at');
        });
    }
};
