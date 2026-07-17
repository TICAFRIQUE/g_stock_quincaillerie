<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Seules les espèces entrent dans le comptage du tiroir (règle non négociable) :
        // il faut un moyen fiable d'identifier LE moyen de paiement espèces sans
        // comparer sur le libellé "nom" (fragile si renommé).
        Schema::table('moyen_paiements', function (Blueprint $table) {
            $table->boolean('est_espece')->default(false)->after('nom');
        });
    }

    public function down(): void
    {
        Schema::table('moyen_paiements', function (Blueprint $table) {
            $table->dropColumn('est_espece');
        });
    }
};
