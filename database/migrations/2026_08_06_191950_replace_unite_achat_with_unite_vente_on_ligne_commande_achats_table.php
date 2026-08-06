<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remplace le couple texte libre unite_achat ('piece'|'groupe') +
     * qte_par_groupe (ressaisi à chaque ligne) par une référence directe aux
     * UniteVente déjà définies sur le produit (même référentiel que la
     * vente/le devis, voir Unite) : plus de double saisie ni de facteur
     * incohérent entre achat et vente pour un même produit. Aucune commande
     * d'achat n'existe encore en base à cette date (catalogue de test tout
     * juste généré) : pas de backfill nécessaire.
     */
    public function up(): void
    {
        if (Schema::hasColumn('ligne_commande_achats', 'unite_achat')) {
            Schema::table('ligne_commande_achats', function (Blueprint $table) {
                $table->dropColumn(['unite_achat', 'qte_par_groupe']);
            });
        }

        if (! Schema::hasColumn('ligne_commande_achats', 'unite_vente_id')) {
            Schema::table('ligne_commande_achats', function (Blueprint $table) {
                $table->foreignId('unite_vente_id')->nullable()->after('produit_id')
                    ->constrained('unite_ventes')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('ligne_commande_achats', 'unite_vente_id')) {
            Schema::table('ligne_commande_achats', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unite_vente_id');
            });
        }

        if (! Schema::hasColumn('ligne_commande_achats', 'unite_achat')) {
            Schema::table('ligne_commande_achats', function (Blueprint $table) {
                $table->string('unite_achat')->default('piece')->after('produit_id');
                $table->unsignedInteger('qte_par_groupe')->nullable()->after('unite_achat');
            });
        }
    }
};
