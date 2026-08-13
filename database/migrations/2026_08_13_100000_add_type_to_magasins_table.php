<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Un dépôt est un lieu de stockage sans caisse ni session (voir
     * CaisseController/UtilisateurController) : même table que les magasins
     * (mêmes stocks, mouvements, transferts, achats, devis), seul le type
     * change le comportement.
     */
    public function up(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            $table->string('type')->default('magasin')->after('nom'); // magasin | depot
        });
    }

    public function down(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
