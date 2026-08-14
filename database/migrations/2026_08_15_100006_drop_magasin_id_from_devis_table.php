<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le devis n'a jamais mouvementé le stock ni la caisse (règle 15) ; ce
     * champ ne servait plus qu'à un numéro désormais indépendant (voir
     * DevisService::genererNumero()), à un filtrage par magasin jugé sans
     * utilité réelle, et à une restriction de transformation retirée par la
     * même occasion (voir VenteController) — même principe que le retrait de
     * magasin_id sur commande_achats.
     */
    public function up(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magasin_id');
        });
    }

    public function down(): void
    {
        Schema::table('devis', function (Blueprint $table) {
            $table->foreignId('magasin_id')->nullable()->after('numero')
                ->constrained('magasins')->restrictOnDelete();
        });
    }
};
