<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Référentiel des offres d'abonnement (voir Abonnement::activer()) :
     * soit un nombre de jours, soit illimité (jours alors nul).
     */
    public function up(): void
    {
        Schema::create('formule_abonnements', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->unsignedInteger('jours')->nullable();
            $table->boolean('illimite')->default(false);
            $table->unsignedInteger('prix');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formule_abonnements');
    }
};
