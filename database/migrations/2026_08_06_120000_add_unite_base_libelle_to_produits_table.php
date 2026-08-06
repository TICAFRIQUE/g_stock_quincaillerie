<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            // Libellé de l'unité de base du produit (règle 5 : « pièce » est
            // générique — peut être « L », « m », « kg »…). Affiché à la
            // place de « pièce » dans le catalogue, la vente et les devis.
            $table->string('unite_base_libelle')->default('pièce')->after('prix_piece');
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('unite_base_libelle');
        });
    }
};
