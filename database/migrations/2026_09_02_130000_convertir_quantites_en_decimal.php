<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quantités décimales (ex. 1.5 mètre de câble) partout où une quantité est
 * transactionnelle — achat, vente, devis, inventaire, retours, mouvements de
 * stock. `unite_ventes.facteur` (le multiplicateur d'un lot, ex. carton de
 * 12) reste volontairement entier : ce n'est pas une quantité, un facteur
 * fractionnaire n'a pas de sens métier.
 *
 * decimal(12,3) : 3 décimales suffisent largement (mètre/kilo/litre au
 * dixième ou centième near-toujours), 12 chiffres au total laisse une marge
 * confortable avant tout dépassement réaliste. Une quantité entière stockée
 * aujourd'hui (ex. 5) devient 5.000, affichée "5" via le helper quantite()
 * (voir app/helpers.php) — comportement strictement inchangé pour qui ne
 * saisit jamais de décimale.
 *
 * ->change() indisponible (pas de doctrine/dbal installé, voir les
 * migrations précédentes qui contournent déjà cette limite) : ALTER TABLE
 * en SQL brut. UNSIGNED volontairement abandonné sur les colonnes decimal
 * (déprécié par MySQL depuis 8.0.17, jamais requis ailleurs dans ce schéma
 * pour une colonne decimal — la validation applicative empêche déjà toute
 * quantité négative en usage normal, comme pour stocks.quantite qui n'a
 * jamais été unsigned non plus, seulement protégée par une CHECK).
 */
return new class extends Migration
{
    /** @var array<int, array{table: string, colonne: string, definition: string}> */
    private array $colonnes = [
        ['table' => 'stocks', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL DEFAULT 0'],
        ['table' => 'mouvement_stocks', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'transferts', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_ventes', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_ventes', 'colonne' => 'quantite_pieces', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_commande_achats', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_commande_achats', 'colonne' => 'quantite_pieces', 'definition' => 'DECIMAL(12,3) NOT NULL DEFAULT 0'],
        ['table' => 'ligne_inventaires', 'colonne' => 'quantite_theorique', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_inventaires', 'colonne' => 'quantite_comptee', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_inventaires', 'colonne' => 'ecart', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_devis', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_vente_en_attentes', 'colonne' => 'quantite', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_retour_ventes', 'colonne' => 'quantite_pieces', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_retour_achats', 'colonne' => 'quantite_pieces', 'definition' => 'DECIMAL(12,3) NOT NULL'],
        ['table' => 'ligne_bon_livraisons', 'colonne' => 'quantite_pieces', 'definition' => 'DECIMAL(12,3) NOT NULL'],
    ];

    /** @var array<int, array{table: string, colonne: string, definition: string}> */
    private array $colonnesOriginales = [
        ['table' => 'stocks', 'colonne' => 'quantite', 'definition' => 'INT NOT NULL DEFAULT 0'],
        ['table' => 'mouvement_stocks', 'colonne' => 'quantite', 'definition' => 'INT NOT NULL'],
        ['table' => 'transferts', 'colonne' => 'quantite', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_ventes', 'colonne' => 'quantite', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_ventes', 'colonne' => 'quantite_pieces', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_commande_achats', 'colonne' => 'quantite', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_commande_achats', 'colonne' => 'quantite_pieces', 'definition' => 'INT UNSIGNED NOT NULL DEFAULT 0'],
        ['table' => 'ligne_inventaires', 'colonne' => 'quantite_theorique', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_inventaires', 'colonne' => 'quantite_comptee', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_inventaires', 'colonne' => 'ecart', 'definition' => 'INT NOT NULL'],
        ['table' => 'ligne_devis', 'colonne' => 'quantite', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_vente_en_attentes', 'colonne' => 'quantite', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_retour_ventes', 'colonne' => 'quantite_pieces', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_retour_achats', 'colonne' => 'quantite_pieces', 'definition' => 'INT UNSIGNED NOT NULL'],
        ['table' => 'ligne_bon_livraisons', 'colonne' => 'quantite_pieces', 'definition' => 'INT UNSIGNED NOT NULL'],
    ];

    public function up(): void
    {
        foreach ($this->colonnes as $c) {
            DB::statement("ALTER TABLE {$c['table']} MODIFY COLUMN {$c['colonne']} {$c['definition']}");
        }
    }

    public function down(): void
    {
        foreach ($this->colonnesOriginales as $c) {
            DB::statement("ALTER TABLE {$c['table']} MODIFY COLUMN {$c['colonne']} {$c['definition']}");
        }
    }
};
