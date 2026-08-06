<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            // Séquence de numérotation des devis, continue par magasin, jamais
            // remise à zéro — même principe que Caisse::sequence_ventes. Le
            // devis n'est pas rattaché à une caisse (créé hors caisse, voir
            // CLAUDE.md), donc la séquence vit sur le magasin.
            $table->unsignedBigInteger('sequence_devis')->default(0)->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            $table->dropColumn('sequence_devis');
        });
    }
};
