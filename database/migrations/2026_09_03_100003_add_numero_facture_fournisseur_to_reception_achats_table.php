<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référence libre du document papier remis par le livreur (numéro de
     * facture propre au fournisseur, distinct de notre propre `numero`
     * généré) — permet de retrouver une réception à partir de ce que le
     * fournisseur a écrit sur sa facture. Distinct du champ `motif`
     * (note libre quelconque), voir CLAUDE.md.
     */
    public function up(): void
    {
        Schema::table('reception_achats', function (Blueprint $table) {
            $table->string('numero_facture_fournisseur')->nullable()->after('motif');
        });
    }

    public function down(): void
    {
        Schema::table('reception_achats', function (Blueprint $table) {
            $table->dropColumn('numero_facture_fournisseur');
        });
    }
};
