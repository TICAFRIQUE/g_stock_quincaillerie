<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            // Optionnelle : a du sens pour une unité de mesure (Litre -> L,
            // Mètre -> m) mais pas forcément pour un conditionnement (Carton,
            // Bidon), voir Produit::uniteBaseLibelle().
            $table->string('abbreviation', 10)->nullable()->after('nom');
        });
    }

    public function down(): void
    {
        Schema::table('unites', function (Blueprint $table) {
            $table->dropColumn('abbreviation');
        });
    }
};
