<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Taxe optionnelle par ligne (prix_achat = HT, TTC dérivé) et destination
     * par ligne (magasin ou dépôt) : une même commande peut désormais livrer
     * plusieurs sites. magasin_destination_id est initialisée au magasin_id
     * de l'en-tête pour les lignes existantes ; nullable en base (pas de
     * doctrine/dbal disponible pour un ->change() vers NOT NULL) mais
     * toujours exigée par la validation applicative pour toute ligne
     * nouvelle/modifiée, voir CommandeAchatController.
     */
    public function up(): void
    {
        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            $table->foreignId('taxe_id')->nullable()->after('unite_vente_id')
                ->constrained('taxes')->restrictOnDelete();
            $table->foreignId('magasin_destination_id')->nullable()->after('taxe_id')
                ->constrained('magasins')->restrictOnDelete();
        });

        DB::statement('
            UPDATE ligne_commande_achats
            INNER JOIN commande_achats ON commande_achats.id = ligne_commande_achats.commande_achat_id
            SET ligne_commande_achats.magasin_destination_id = commande_achats.magasin_id
        ');
    }

    public function down(): void
    {
        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('taxe_id');
            $table->dropConstrainedForeignId('magasin_destination_id');
        });
    }
};
