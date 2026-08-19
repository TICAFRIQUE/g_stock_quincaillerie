<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rattachement optionnel à une session de caisse : seulement requis à
     * l'application quand une partie du règlement est payée en espèces (voir
     * ReglementFournisseurService::encaisser()), pour savoir de quel tiroir
     * sort la sortie de caisse générée automatiquement. Un règlement 100%
     * non-espèces (virement, chèque…) reste indépendant de toute session,
     * comme avant (règle 17).
     */
    public function up(): void
    {
        Schema::table('reglement_fournisseurs', function (Blueprint $table) {
            $table->foreignId('session_caisse_id')->nullable()->after('commande_achat_id')
                ->constrained('session_caisses')->restrictOnDelete();
            $table->index('session_caisse_id');
        });
    }

    public function down(): void
    {
        Schema::table('reglement_fournisseurs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('session_caisse_id');
        });
    }
};
