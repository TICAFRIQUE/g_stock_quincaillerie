<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            // Part de l'avoir du client déjà déduite du solde à crédit posé
            // par cette vente, figée à la création (voir Vente::soldeDuReel())
            // — jamais recalculée après coup, comme cout_applique sur une
            // ligne de vente.
            $table->unsignedInteger('avoir_applique')->default(0)->after('monnaie_rendue');
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropColumn('avoir_applique');
        });
    }
};
