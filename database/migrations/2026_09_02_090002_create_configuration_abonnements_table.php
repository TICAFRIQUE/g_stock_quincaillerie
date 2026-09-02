<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table singleton (une seule ligne, id=1) : coordonnées de contact
     * affichées sur la page "Mon abonnement" quand l'abonnement a expiré.
     * Voir ConfigurationAbonnement::actuel(), même principe que Parametre.
     */
    public function up(): void
    {
        Schema::create('configuration_abonnements', function (Blueprint $table) {
            $table->id();
            $table->string('telephone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configuration_abonnements');
    }
};
