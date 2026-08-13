<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registre immuable, mirroring ecriture_compte_clients : le solde d'un
     * fournisseur (ce qu'on lui doit) est toujours la somme de ses
     * écritures, jamais une valeur stockée/écrasée (même principe que
     * mouvement_stocks pour le stock et ecriture_compte_clients pour les
     * clients).
     */
    public function up(): void
    {
        Schema::create('ecriture_compte_fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fournisseur_id')->constrained('fournisseurs')->restrictOnDelete();
            $table->string('type'); // achat_credit | reglement
            $table->integer('montant'); // signé : + dette (achat à crédit) / - dette (règlement)
            $table->nullableMorphs('reference'); // -> CommandeAchat ou ReglementFournisseur
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('fournisseur_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecriture_compte_fournisseurs');
    }
};
