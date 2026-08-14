<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Client choisi pour une vente à crédit avant sa mise en attente :
        // sans ce champ, la reprise du panier perdait le client sélectionné
        // (obligeant à le re-choisir) et une reprise à crédit sans client
        // échouait la règle 13 (vente sans client = payée intégralement).
        // nullOnDelete() plutôt que restrictOnDelete() : un panier en attente
        // est éphémère, la suppression d'un client ne doit pas en bloquer la
        // suppression — le panier redevient simplement une vente comptant.
        Schema::table('vente_en_attentes', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('caissier_id')
                ->constrained('clients')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vente_en_attentes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
