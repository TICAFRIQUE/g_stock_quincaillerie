<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->foreignId('unite_base_id')->nullable()->after('prix_piece')
                ->constrained('unites')->restrictOnDelete();
        });

        // Backfill : une ligne « unites » par libellé texte distinct déjà en
        // usage (généralement juste « pièce »), puis rattache chaque produit.
        $libelles = DB::table('produits')->whereNotNull('unite_base_libelle')->distinct()->pluck('unite_base_libelle');
        foreach ($libelles as $libelle) {
            $uniteId = DB::table('unites')->where('nom', $libelle)->value('id');
            if (! $uniteId) {
                $uniteId = DB::table('unites')->insertGetId([
                    'nom' => $libelle,
                    'actif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table('produits')->where('unite_base_libelle', $libelle)->update(['unite_base_id' => $uniteId]);
        }

        Schema::table('produits', function (Blueprint $table) {
            $table->dropColumn('unite_base_libelle');
        });
    }

    public function down(): void
    {
        Schema::table('produits', function (Blueprint $table) {
            $table->string('unite_base_libelle')->default('pièce')->after('prix_piece');
        });

        DB::table('produits')->update([
            'unite_base_libelle' => DB::raw('(select nom from unites where unites.id = produits.unite_base_id)'),
        ]);

        Schema::table('produits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unite_base_id');
        });
    }
};
