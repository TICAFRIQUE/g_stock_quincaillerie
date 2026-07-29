<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            $table->string('unite_achat')->default('piece')->after('produit_id');
            $table->unsignedInteger('qte_par_groupe')->nullable()->after('unite_achat');
            // Quantité en pièces dérivée de quantite × qte_par_groupe : c'est
            // elle qui alimente le mouvement de stock (le stock se compte
            // toujours en pièces, jamais en groupes/cartons).
            $table->unsignedInteger('quantite_pieces')->default(0)->after('quantite');
        });

        // Lignes existantes : déjà saisies en pièces (unite_achat par défaut).
        DB::table('ligne_commande_achats')->update(['quantite_pieces' => DB::raw('quantite')]);
    }

    public function down(): void
    {
        Schema::table('ligne_commande_achats', function (Blueprint $table) {
            $table->dropColumn(['unite_achat', 'qte_par_groupe', 'quantite_pieces']);
        });
    }
};
