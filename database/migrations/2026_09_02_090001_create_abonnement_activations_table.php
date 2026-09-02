<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registre immuable des activations d'abonnement : aucun UPDATE/DELETE
     * applicatif (voir AbonnementActivation, même principe que
     * ecriture_compte_clients). La situation courante de l'abonnement est
     * toujours celle de la plus récente ligne (voir Abonnement::estBloquant()).
     */
    public function up(): void
    {
        Schema::create('abonnement_activations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formule_id')->nullable()->constrained('formule_abonnements')->nullOnDelete();
            $table->unsignedInteger('montant');
            $table->unsignedInteger('jours')->nullable();
            $table->boolean('illimite')->default(false);
            $table->unsignedInteger('jours_restants_reportes')->default(0);
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnement_activations');
    }
};
