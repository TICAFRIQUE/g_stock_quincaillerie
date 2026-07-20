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
        // Le client peut donner plus que le net à payer : on garde une trace
        // du montant réellement remis en main et de la monnaie rendue, pour
        // que le ticket les affiche — sans jamais impacter les paiements
        // enregistrés (toujours plafonnés au net à payer, voir VenteService).
        Schema::table('ventes', function (Blueprint $table) {
            $table->unsignedInteger('montant_recu')->nullable()->after('total_net');
            $table->unsignedInteger('monnaie_rendue')->default(0)->after('montant_recu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn(['montant_recu', 'monnaie_rendue']);
        });
    }
};
