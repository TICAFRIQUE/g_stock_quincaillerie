<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registre immuable, mirroring ecriture_compte_fournisseurs : le solde
     * d'un compte de trésorerie est toujours la somme de ses écritures,
     * jamais une valeur stockée/écrasée.
     */
    public function up(): void
    {
        Schema::create('ecriture_compte_tresoreries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compte_tresorerie_id')->constrained('compte_tresoreries')->restrictOnDelete();
            $table->string('type'); // depot_session_cloturee | sortie_manuelle | entree_manuelle | reglement_fournisseur | remboursement_avoir_client | remboursement_avoir_fournisseur | virement_sortant | virement_entrant
            $table->integer('montant'); // signé : + entrée / - sortie
            $table->string('motif')->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('compte_tresorerie_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecriture_compte_tresoreries');
    }
};
