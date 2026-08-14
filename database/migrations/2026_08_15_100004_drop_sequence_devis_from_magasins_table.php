<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le numéro de devis ne dépend plus du magasin choisi dans le formulaire
     * (voir DevisService::genererNumero(), même principe que
     * CommandeAchat::genererNumero()) : ce compteur par magasin n'est plus
     * utilisé.
     */
    public function up(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            $table->dropColumn('sequence_devis');
        });
    }

    public function down(): void
    {
        Schema::table('magasins', function (Blueprint $table) {
            $table->unsignedBigInteger('sequence_devis')->default(0)->after('actif');
        });
    }
};
