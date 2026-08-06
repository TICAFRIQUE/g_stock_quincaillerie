<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Registre immuable : aucun UPDATE/DELETE applicatif, pas de colonne
        // updated_at. Le solde d'un client est toujours la somme de ses
        // écritures — jamais une valeur stockée/écrasée (même principe que
        // mouvement_stocks pour le stock, voir CLAUDE.md règle 12).
        Schema::create('ecriture_compte_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->restrictOnDelete();
            $table->string('type'); // vente_credit | reglement
            $table->integer('montant'); // signé : + dette (vente à crédit) / - dette (règlement)
            $table->nullableMorphs('reference'); // -> Vente ou ReglementClient
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->index('client_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ecriture_compte_clients');
    }
};
