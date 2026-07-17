<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_caisses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->restrictOnDelete();
            $table->foreignId('caissier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('fond_de_caisse');
            $table->timestamp('date_ouverture');
            $table->timestamp('date_cloture')->nullable();
            $table->timestamp('date_fermeture')->nullable();
            $table->unsignedInteger('total_ventes_especes')->nullable();
            $table->unsignedInteger('montant_compte')->nullable();
            $table->integer('ecart')->nullable();
            $table->foreignId('cloture_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('date_ouverture');
            $table->index('date_cloture');

            // Colonne générée : vaut caisse_id tant que la session est ouverte
            // (date_fermeture NULL), NULL sinon. Combinée à l'index unique ci-dessous,
            // ça applique au niveau DB la règle "une seule session ouverte par caisse",
            // même si le verrou applicatif (lockForUpdate) est contourné.
            $table->unsignedBigInteger('est_ouverte')->nullable()
                ->storedAs('IF(date_fermeture IS NULL, caisse_id, NULL)');
            $table->unique('est_ouverte');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_caisses');
    }
};
