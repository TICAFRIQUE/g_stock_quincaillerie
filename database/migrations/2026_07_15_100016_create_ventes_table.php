<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Immuable une fois créée : pas de statut d'annulation, pas d'updated_at
        // (pas de retours dans le MVP).
        Schema::create('ventes', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique(); // M{magasin}-C{caisse}-{séquence}
            $table->foreignId('magasin_id')->constrained('magasins')->restrictOnDelete();
            $table->foreignId('session_caisse_id')->constrained('session_caisses')->restrictOnDelete();
            $table->foreignId('caissier_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('sous_total');
            $table->string('remise_totale_type')->nullable(); // montant | pourcentage
            $table->unsignedInteger('remise_totale_valeur')->nullable();
            $table->unsignedInteger('remise_totale_montant')->default(0);
            $table->unsignedInteger('total_net');
            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventes');
    }
};
