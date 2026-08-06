<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotente à dessein : MySQL committe chaque DDL implicitement, donc
     * une exécution interrompue en cours de route (ex. ordre d'index)
     * laisse des changements déjà appliqués. Chaque étape vérifie son propre
     * état avant d'agir, pour pouvoir relancer `migrate` sans erreur.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('unite_ventes', 'unite_id')) {
            Schema::table('unite_ventes', function (Blueprint $table) {
                $table->foreignId('unite_id')->nullable()->after('produit_id')
                    ->constrained('unites')->restrictOnDelete();
            });
        }

        // Backfill : "Lot de 6" -> unité "Lot" (le facteur, déjà en colonne,
        // porte le "6"). Le libellé complet redevient un accesseur composé
        // (voir UniteVente::libelle()), plus une colonne stockée.
        if (Schema::hasColumn('unite_ventes', 'libelle')) {
            $uniteIdParNom = [];
            foreach (DB::table('unite_ventes')->whereNull('unite_id')->select('id', 'libelle')->get() as $ligne) {
                $nom = trim(preg_replace('/\s+de\s+\d+$/i', '', $ligne->libelle)) ?: $ligne->libelle;

                if (! isset($uniteIdParNom[$nom])) {
                    $uniteId = DB::table('unites')->where('nom', $nom)->value('id');
                    if (! $uniteId) {
                        $uniteId = DB::table('unites')->insertGetId([
                            'nom' => $nom,
                            'actif' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                    $uniteIdParNom[$nom] = $uniteId;
                }

                DB::table('unite_ventes')->where('id', $ligne->id)->update(['unite_id' => $uniteIdParNom[$nom]]);
            }
        }

        // L'ancien index unique (produit_id, libelle) supporte aussi la clé
        // étrangère produit_id : MySQL refuse de le supprimer tant qu'aucun
        // autre index ne prend le relais. Le nouveau doit donc être créé
        // avant de supprimer l'ancien, jamais l'inverse.
        if (! $this->indexExists('unite_ventes', 'unite_ventes_produit_id_unite_id_facteur_unique')) {
            Schema::table('unite_ventes', function (Blueprint $table) {
                // Un même conditionnement peut revenir avec un facteur différent
                // (« Carton de 12 » et « Carton de 24 ») : l'unicité porte sur la
                // combinaison unité + facteur, pas l'unité seule.
                $table->unique(['produit_id', 'unite_id', 'facteur']);
            });
        }

        if (Schema::hasColumn('unite_ventes', 'libelle')) {
            if ($this->indexExists('unite_ventes', 'unite_ventes_produit_id_libelle_unique')) {
                Schema::table('unite_ventes', function (Blueprint $table) {
                    $table->dropUnique('unite_ventes_produit_id_libelle_unique');
                });
            }

            Schema::table('unite_ventes', function (Blueprint $table) {
                $table->dropColumn('libelle');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('unite_ventes', 'libelle')) {
            Schema::table('unite_ventes', function (Blueprint $table) {
                $table->string('libelle')->nullable()->after('unite_id');
            });

            foreach (DB::table('unite_ventes')->select('id', 'unite_id', 'facteur')->get() as $ligne) {
                $nom = DB::table('unites')->where('id', $ligne->unite_id)->value('nom') ?? 'Lot';
                DB::table('unite_ventes')->where('id', $ligne->id)->update(['libelle' => "{$nom} de {$ligne->facteur}"]);
            }
        }

        // Même contrainte qu'en up() : créer l'index de repli avant de
        // supprimer celui qui supporte actuellement la clé étrangère produit_id.
        if (! $this->indexExists('unite_ventes', 'unite_ventes_produit_id_libelle_unique')) {
            Schema::table('unite_ventes', function (Blueprint $table) {
                $table->unique(['produit_id', 'libelle']);
            });
        }

        if ($this->indexExists('unite_ventes', 'unite_ventes_produit_id_unite_id_facteur_unique')) {
            Schema::table('unite_ventes', function (Blueprint $table) {
                $table->dropUnique(['produit_id', 'unite_id', 'facteur']);
            });
        }

        if (Schema::hasColumn('unite_ventes', 'unite_id')) {
            Schema::table('unite_ventes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('unite_id');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $index) => $index['name'] === $indexName);
    }
};
