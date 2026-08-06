<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            // Nullable : une vente comptant reste anonyme. Obligatoire côté
            // service dès que le total des paiements est inférieur au net à
            // payer (vente à crédit, règle 13).
            $table->foreignId('client_id')->nullable()->after('caissier_id')
                ->constrained('clients')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ventes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });
    }
};
