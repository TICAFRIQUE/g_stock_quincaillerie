<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référence du bon de livraison remis par le fournisseur à la livraison
     * physique — distinct de `numero_facture_fournisseur` (le document de
     * facturation, parfois remis en même temps, parfois séparément/plus
     * tard selon le fournisseur, notamment en achat à crédit). Les deux
     * sont optionnels et indépendants, voir CLAUDE.md.
     */
    public function up(): void
    {
        Schema::table('reception_achats', function (Blueprint $table) {
            $table->string('numero_bon_livraison_fournisseur')->nullable()->after('numero_facture_fournisseur');
        });
    }

    public function down(): void
    {
        Schema::table('reception_achats', function (Blueprint $table) {
            $table->dropColumn('numero_bon_livraison_fournisseur');
        });
    }
};
