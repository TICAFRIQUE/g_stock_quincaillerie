<?php

namespace Database\Seeders;

use App\Models\Caisse;
use App\Models\Categorie;
use App\Models\Fournisseur;
use App\Models\Magasin;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Référentiel de démonstration : magasins, caisses, catégories, fournisseurs,
 * produits (avec quelques unités de vente) et utilisateurs de test. Le stock
 * n'est pas touché ici — il n'existe qu'au travers des mouvements générés par
 * DemoTransactionsSeeder.
 */
class DemoCatalogueSeeder extends Seeder
{
    /** @var array<int, Magasin> */
    public array $magasins = [];

    /** @var array<int, Caisse> */
    public array $caisses = [];

    /** @var array<int, Produit> */
    public array $produits = [];

    /** @var array<int, User> */
    public array $gerants = [];

    /** @var array<int, User> */
    public array $caissiers = [];

    public function run(): void
    {
        $this->creerMagasinsEtCaisses();
        $this->creerCategories();
        $this->creerFournisseurs();
        $this->creerProduits();
        $this->creerUtilisateurs();
    }

    private function creerMagasinsEtCaisses(): void
    {
        $magasinPrincipal = Magasin::firstOrCreate(
            ['nom' => 'Magasin Principal'],
            ['adresse' => 'Plateau, Abidjan', 'telephone' => '27 20 21 22 23']
        );

        $definitions = [
            ['nom' => 'Magasin Cocody', 'adresse' => 'Cocody Angré, Abidjan', 'telephone' => '27 22 44 55 66'],
            ['nom' => 'Magasin Yopougon', 'adresse' => 'Yopougon Selmer, Abidjan', 'telephone' => '27 23 66 77 88'],
            ['nom' => 'Magasin Marcory', 'adresse' => 'Marcory Zone 4, Abidjan', 'telephone' => '27 21 33 44 55'],
        ];

        $this->magasins = [$magasinPrincipal];
        foreach ($definitions as $definition) {
            $this->magasins[] = Magasin::firstOrCreate(['nom' => $definition['nom']], $definition);
        }

        foreach ($this->magasins as $magasin) {
            $nombreCaisses = $magasin->is($magasinPrincipal) ? 1 : 2;
            for ($i = 1; $i <= $nombreCaisses; $i++) {
                $nom = $magasin->is($magasinPrincipal) && $i === 1 ? 'Caisse 1' : "Caisse {$i} — {$magasin->nom}";
                $this->caisses[] = Caisse::firstOrCreate(['magasin_id' => $magasin->id, 'nom' => $nom]);
            }
        }
    }

    private function creerCategories(): void
    {
        Categorie::firstOrCreate(['nom' => 'Général']);

        foreach ([
            'Assiettes',
            'Verres',
            'Tasses & Mugs',
            'Couverts',
            'Plats de service',
            'Bols & Saladiers',
            'Théières & Cafetières',
            'Casseroles & Marmites',
        ] as $nom) {
            Categorie::firstOrCreate(['nom' => $nom]);
        }
    }

    private function creerFournisseurs(): void
    {
        $noms = [
            'Céramiques de Bassam', 'Import Vaisselle Abidjan', 'Porcelaine d\'Ivoire',
            'Verrerie du Golfe', 'Ustensiles & Cie', 'Comptoir de la Table',
            'Distribution Arts de la Table CI', 'Cristal Distribution', 'Maison Kouassi Vaisselle',
            'Grossiste Vaisselle Plateau', 'Table & Style Import', 'Afrique Porcelaine',
            'Négoce Adjamé Vaisselle', 'Import-Export Diallo', 'Comptoir Ivoire Céramique',
            'Vaisselle & Arts de la Maison', 'Sud Distribution Vaisselle', 'Kouadio Frères Import',
            'Porcelaine Express CI', 'Table d\'Ivoire Distribution', 'Bassam Céramique Export',
            'Grand Marché Vaisselle Gros', 'Diaby Import Vaisselle',
        ];

        foreach ($noms as $nom) {
            Fournisseur::firstOrCreate(['nom' => $nom], [
                'telephone' => '05 '.fake()->numerify('## ## ## ##'),
                'email' => \Illuminate\Support\Str::slug($nom).'@fournisseur.example',
                'adresse' => fake()->streetAddress(),
            ]);
        }
    }

    private function creerProduits(): void
    {
        $categories = Categorie::all()->keyBy('nom');

        // [nom, libelle_distinctif, categorie, prix_piece, seuil_alerte, unites [[facteur, prix], ...]]
        $definitions = [
            ['Assiette plate blanche', 'Collection Basique', 'Assiettes', 800, 30, [[6, 4500], [12, 8500]]],
            ['Assiette plate blanche', 'Collection Ivoire', 'Assiettes', 1200, 20, [[6, 6800]]],
            ['Assiette creuse blanche', null, 'Assiettes', 900, 30, [[6, 5000]]],
            ['Assiette à dessert', null, 'Assiettes', 600, 25, []],
            ['Assiette plate motif floral', null, 'Assiettes', 1500, 15, [[6, 8500]]],
            ['Assiette carrée noire', null, 'Assiettes', 1300, 15, []],
            ['Assiette plate bord doré', 'Édition Prestige', 'Assiettes', 2200, 10, []],
            ['Verre à eau', null, 'Verres', 500, 40, [[6, 2800], [12, 5200]]],
            ['Verre à vin rouge', null, 'Verres', 700, 25, [[6, 3900]]],
            ['Verre à vin blanc', null, 'Verres', 700, 25, [[6, 3900]]],
            ['Verre à whisky', null, 'Verres', 900, 15, []],
            ['Verre à jus', null, 'Verres', 450, 30, [[6, 2500]]],
            ['Flûte à champagne', null, 'Verres', 1100, 12, [[6, 6200]]],
            ['Tasse à café blanche', null, 'Tasses & Mugs', 600, 30, [[6, 3300]]],
            ['Tasse à thé motif', null, 'Tasses & Mugs', 900, 20, []],
            ['Mug céramique', 'Collection Colorée', 'Tasses & Mugs', 800, 25, [[6, 4500]]],
            ['Sous-tasse blanche', null, 'Tasses & Mugs', 300, 30, []],
            ['Tasse expresso', null, 'Tasses & Mugs', 500, 20, [[6, 2700]]],
            ['Couteau de table inox', null, 'Couverts', 700, 30, [[6, 3900], [12, 7500]]],
            ['Fourchette de table inox', null, 'Couverts', 600, 30, [[6, 3300], [12, 6400]]],
            ['Cuillère à soupe inox', null, 'Couverts', 500, 30, [[6, 2700]]],
            ['Cuillère à café inox', null, 'Couverts', 300, 30, [[6, 1600]]],
            ['Set couverts 3 pièces', null, 'Couverts', 1800, 15, []],
            ['Plat à gratin ovale', null, 'Plats de service', 2500, 10, []],
            ['Plat rectangulaire', null, 'Plats de service', 2200, 10, []],
            ['Plateau de service rond', null, 'Plats de service', 1900, 12, []],
            ['Plat à tajine', 'Import Marocain', 'Plats de service', 3500, 8, []],
            ['Saladier en verre', null, 'Plats de service', 1600, 12, []],
            ['Bol à soupe', null, 'Bols & Saladiers', 700, 25, [[6, 3900]]],
            ['Bol à céréales', null, 'Bols & Saladiers', 650, 25, [[6, 3600]]],
            ['Saladier plastique', null, 'Bols & Saladiers', 900, 20, []],
            ['Coupelle apéritif', null, 'Bols & Saladiers', 400, 25, [[6, 2200]]],
            ['Théière en porcelaine', null, 'Théières & Cafetières', 3200, 8, []],
            ['Cafetière émaillée', null, 'Théières & Cafetières', 4500, 6, []],
            ['Service à thé 6 pièces', 'Coffret cadeau', 'Théières & Cafetières', 9500, 5, []],
            ['Casserole inox 20cm', null, 'Casseroles & Marmites', 5500, 8, []],
            ['Marmite en fonte', null, 'Casseroles & Marmites', 12000, 5, []],
            ['Poêle antiadhésive', null, 'Casseroles & Marmites', 4800, 10, []],
            ['Faitout inox', null, 'Casseroles & Marmites', 8500, 6, []],
            ['Set de table bambou', null, 'Général', 1200, 15, []],
        ];

        foreach ($definitions as [$nom, $libelle, $categorieNom, $prixPiece, $seuil, $unites]) {
            $produit = Produit::create([
                'sku' => $this->genererSku(),
                'nom' => $nom,
                'libelle_distinctif' => $libelle,
                'categorie_id' => $categories[$categorieNom]->id,
                'prix_piece' => $prixPiece,
                'seuil_alerte' => $seuil,
            ]);

            foreach ($unites as [$facteur, $prix]) {
                $produit->uniteVentes()->create([
                    'libelle' => "Lot de {$facteur}",
                    'facteur' => $facteur,
                    'prix' => $prix,
                    'actif' => true,
                ]);
            }

            $this->produits[] = $produit;
        }
    }

    private function creerUtilisateurs(): void
    {
        foreach ($this->magasins as $index => $magasin) {
            $slug = \Illuminate\Support\Str::slug($magasin->nom);

            $gerant = User::firstOrCreate(
                ['email' => "gerant.{$slug}@example.com"],
                [
                    'name' => 'Gérant '.$magasin->nom,
                    'password' => 'password',
                    'magasin_id' => $magasin->id,
                    'actif' => true,
                ]
            );
            $gerant->syncRoles(['Gérant']);
            $this->gerants[] = $gerant;

            foreach (range(1, 5) as $n) {
                $caissier = User::firstOrCreate(
                    ['email' => "caissier{$n}.{$slug}@example.com"],
                    [
                        'name' => "Caissier {$n} ".$magasin->nom,
                        'password' => 'password',
                        'magasin_id' => $magasin->id,
                        'actif' => true,
                    ]
                );
                $caissier->syncRoles(['Caissier']);
                $this->caissiers[] = $caissier;
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
