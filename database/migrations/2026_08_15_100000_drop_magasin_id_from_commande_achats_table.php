<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La destination vit uniquement par ligne (magasin_destination_id sur
     * ligne_commande_achats, voir migration 2026_08_13_100002) — le magasin
     * d'en-tête n'est utilisé nulle part ailleurs (AchatService résout déjà
     * exclusivement la destination de chaque ligne).
     */
    public function up(): void
    {
        Schema::table('commande_achats', function (Blueprint $table) {
            $table->dropConstrainedForeignId('magasin_id');
        });
    }

    public function down(): void
    {
        Schema::table('commande_achats', function (Blueprint $table) {
            $table->foreignId('magasin_id')->nullable()->after('fournisseur_id')
                ->constrained('magasins')->restrictOnDelete();
        });
    }
};
