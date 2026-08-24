<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comptes de trésorerie de l'entreprise (Caisse Générale + comptes
     * bancaires/autres) — indépendants des caisses de vente des caissiers
     * (voir CLAUDE.md, Trésorerie). Un seul enregistrement de type
     * caisse_generale existe, créé par CompteTresorerieSeeder.
     */
    public function up(): void
    {
        Schema::create('compte_tresoreries', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('type'); // caisse_generale | banque | autre
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compte_tresoreries');
    }
};
