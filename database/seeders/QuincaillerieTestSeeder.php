<?php

namespace Database\Seeders;

use App\Models\Categorie;
use App\Models\Produit;
use App\Models\Unite;
use Illuminate\Database\Seeder;

/**
 * Jeu de données de test pour une quincaillerie : unités, catégories/sous-
 * catégories et produits couvrant plusieurs unités de base (pièce, mètre,
 * kilo, litre, sac) et des unités de vente variées — à lancer sur une base
 * déjà vidée (voir demande utilisateur du 2026-08-06 : rôles/permissions/
 * admin conservés, reste supprimé).
 */
class QuincaillerieTestSeeder extends Seeder
{
    /** @var array<string, Unite> */
    private array $unites = [];

    /** @var array<string, Categorie> */
    private array $categories = [];

    public function run(): void
    {
        $this->creerUnites();
        $this->creerCategories();
        $this->creerProduits();
    }

    private function creerUnites(): void
    {
        foreach ([
            'Pièce' => 'pc',
            'Litre' => 'L',
            'Mètre' => 'm',
            'Kilogramme' => 'kg',
            'Carton' => null,
            'Bidon' => null,
            'Sac' => null,
            'Rouleau' => null,
            'Boîte' => null,
            'Barre' => null,
            'Palette' => null,
        ] as $nom => $abbreviation) {
            $this->unites[$nom] = Unite::firstOrCreate(
                ['nom' => $nom],
                ['abbreviation' => $abbreviation, 'actif' => true]
            );
        }
    }

    private function creerCategories(): void
    {
        $arbre = [
            'Quincaillerie générale' => ['Vis et boulons', 'Clous et pointes', 'Chevilles et fixations', 'Serrurerie'],
            'Outillage' => ['Outillage à main', 'Outillage électroportatif', 'Mesure et traçage'],
            'Plomberie' => ['Tuyauterie PVC', 'Raccords', 'Robinetterie'],
            'Électricité' => ['Câbles et fils', 'Interrupteurs et prises', 'Éclairage'],
            'Peinture et droguerie' => ['Peintures', 'Enduits et mastics', 'Pinceaux et rouleaux'],
            'Matériaux de construction' => ['Ciment et liants', 'Fers et métaux', 'Bois et dérivés'],
        ];

        foreach ($arbre as $parentNom => $enfants) {
            $parent = Categorie::firstOrCreate(['nom' => $parentNom], ['actif' => true]);

            foreach ($enfants as $enfantNom) {
                $this->categories[$enfantNom] = Categorie::firstOrCreate(
                    ['nom' => $enfantNom],
                    ['parent_id' => $parent->id, 'actif' => true]
                );
            }
        }
    }

    private function creerProduits(): void
    {
        // [nom, libellé distinctif, sous-catégorie, unité de base, prix pièce,
        //  seuil d'alerte, [[unité de vente, facteur, prix], ...]]
        $definitions = [
            ['Vis à bois 4x40mm', null, 'Vis et boulons', 'Pièce', 25, 100, [['Boîte', 100, 2000]]],
            ['Vis à bois 5x50mm', null, 'Vis et boulons', 'Pièce', 35, 100, [['Boîte', 100, 2800]]],
            ['Boulon hexagonal M8x60', null, 'Vis et boulons', 'Pièce', 150, 50, []],
            ['Écrou hexagonal M8', null, 'Vis et boulons', 'Pièce', 50, 100, [['Boîte', 100, 4000]]],
            ['Clou acier 70mm', null, 'Clous et pointes', 'Kilogramme', 800, 10, [['Sac', 5, 3800]]],
            ['Clou acier 100mm', null, 'Clous et pointes', 'Kilogramme', 850, 10, [['Sac', 5, 4000]]],
            ['Pointe tête plate 40mm', null, 'Clous et pointes', 'Kilogramme', 900, 5, []],
            ['Cheville plastique 8mm', null, 'Chevilles et fixations', 'Pièce', 15, 200, [['Boîte', 50, 650]]],
            ['Cheville à frapper 6mm', null, 'Chevilles et fixations', 'Pièce', 20, 200, [['Boîte', 50, 850]]],
            ['Cadenas laiton 40mm', null, 'Serrurerie', 'Pièce', 2500, 15, []],
            ['Verrou de porte', null, 'Serrurerie', 'Pièce', 3200, 10, []],
            ['Poignée de porte', 'Finition chromée', 'Serrurerie', 'Pièce', 4500, 10, []],
            ['Marteau menuisier 500g', null, 'Outillage à main', 'Pièce', 3500, 8, []],
            ['Tournevis cruciforme PH2', null, 'Outillage à main', 'Pièce', 1200, 20, []],
            ['Pince multiprise', null, 'Outillage à main', 'Pièce', 4200, 10, []],
            ['Scie égoïne', null, 'Outillage à main', 'Pièce', 5500, 8, []],
            ['Perceuse électrique 650W', null, 'Outillage électroportatif', 'Pièce', 25000, 5, []],
            ['Meuleuse angulaire 115mm', null, 'Outillage électroportatif', 'Pièce', 32000, 5, []],
            ['Mètre ruban 5m', null, 'Mesure et traçage', 'Pièce', 1500, 15, []],
            ['Niveau à bulle 60cm', null, 'Mesure et traçage', 'Pièce', 3000, 10, []],
            ['Tuyau PVC Ø100mm', 'Évacuation', 'Tuyauterie PVC', 'Mètre', 1200, 20, [['Barre', 4, 4500]]],
            ['Tuyau PVC Ø32mm', 'Alimentation', 'Tuyauterie PVC', 'Mètre', 450, 30, [['Barre', 4, 1700]]],
            ['Coude PVC 90° Ø100', null, 'Raccords', 'Pièce', 800, 20, []],
            ['Té PVC Ø100', null, 'Raccords', 'Pièce', 950, 15, []],
            ['Robinet d\'arrêt 1/2"', null, 'Robinetterie', 'Pièce', 2500, 10, []],
            ['Mitigeur évier', null, 'Robinetterie', 'Pièce', 15000, 5, []],
            ['Câble électrique 2.5mm²', 'Rigide', 'Câbles et fils', 'Mètre', 350, 50, [['Rouleau', 100, 32000]]],
            ['Câble électrique 1.5mm²', 'Rigide', 'Câbles et fils', 'Mètre', 280, 50, [['Rouleau', 100, 26000]]],
            ['Interrupteur simple', null, 'Interrupteurs et prises', 'Pièce', 1200, 20, []],
            ['Prise de courant 2P+T', null, 'Interrupteurs et prises', 'Pièce', 1500, 20, []],
            ['Ampoule LED 9W', null, 'Éclairage', 'Pièce', 1000, 30, [['Carton', 10, 9000]]],
            ['Réglette LED 60cm', null, 'Éclairage', 'Pièce', 4500, 10, []],
            ['Peinture glycéro blanche', null, 'Peintures', 'Litre', 3500, 10, [['Bidon', 5, 16000]]],
            ['Peinture acrylique blanche', null, 'Peintures', 'Litre', 3200, 10, [['Bidon', 5, 15000]]],
            ['Enduit de rebouchage', null, 'Enduits et mastics', 'Kilogramme', 1200, 10, [['Sac', 5, 5500]]],
            ['Mastic silicone', null, 'Enduits et mastics', 'Pièce', 2200, 15, []],
            ['Pinceau plat 50mm', null, 'Pinceaux et rouleaux', 'Pièce', 800, 20, []],
            ['Rouleau à peinture 18cm', null, 'Pinceaux et rouleaux', 'Pièce', 1500, 15, []],
            ['Ciment CPJ 42.5', 'Sac 50kg', 'Ciment et liants', 'Sac', 5500, 30, [['Palette', 40, 210000]]],
            ['Chaux hydraulique', 'Sac 25kg', 'Ciment et liants', 'Sac', 4200, 15, []],
            ['Fer à béton 8mm', null, 'Fers et métaux', 'Mètre', 450, 40, [['Barre', 12, 5200]]],
            ['Fer à béton 10mm', null, 'Fers et métaux', 'Mètre', 650, 40, [['Barre', 12, 7500]]],
            ['Tôle bac acier', null, 'Fers et métaux', 'Pièce', 8500, 10, []],
            ['Contreplaqué 18mm 122x244', null, 'Bois et dérivés', 'Pièce', 18000, 5, []],
            ['Planche de coffrage', null, 'Bois et dérivés', 'Mètre', 900, 20, []],
        ];

        foreach ($definitions as [$nom, $libelle, $categorieNom, $uniteBaseNom, $prixPiece, $seuil, $unitesVente]) {
            $produit = Produit::create([
                'sku' => $this->genererSku(),
                'nom' => $nom,
                'libelle_distinctif' => $libelle,
                'categorie_id' => $this->categories[$categorieNom]->id,
                'prix_piece' => $prixPiece,
                'unite_base_id' => $this->unites[$uniteBaseNom]->id,
                'seuil_alerte' => $seuil,
                'actif' => true,
            ]);

            foreach ($unitesVente as [$uniteNom, $facteur, $prix]) {
                $produit->uniteVentes()->create([
                    'unite_id' => $this->unites[$uniteNom]->id,
                    'facteur' => $facteur,
                    'prix' => $prix,
                    'actif' => true,
                ]);
            }
        }
    }

    private function genererSku(): string
    {
        do {
            $sku = 'PRD-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Produit::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }
}
