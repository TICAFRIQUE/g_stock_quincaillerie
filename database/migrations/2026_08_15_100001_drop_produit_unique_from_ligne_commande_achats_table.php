<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cette contrainte empêchait un même produit d'apparaître deux fois sur
     * une commande, même avec une unité d'achat différente (pièce vs
     * carton) — un cas pourtant légitime. Alignée sur ligne_ventes, qui n'a
     * jamais eu cette contrainte : le doublon (produit + unité identiques)
     * est empêché côté client uniquement (voir estDoublon dans
     * commande-achats/create.blade.php), pas en base.
     */
    public function up(): void
    {
        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            // La contrainte unique servait aussi d'index de support pour la
            // clé étrangère commande_achat_id — un index simple la remplace
            // avant de pouvoir supprimer la contrainte unique.
            $table->index('commande_achat_id');
        });

        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            $table->dropUnique(['commande_achat_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            $table->dropIndex(['commande_achat_id']);
            $table->unique(['commande_achat_id', 'produit_id']);
        });
    }
};
