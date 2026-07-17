<?php

namespace Database\Seeders;

use App\Enums\MouvementStockType;
use App\Exceptions\CaisseNonLibreException;
use App\Exceptions\StockInsuffisantException;
use App\Models\Caisse;
use App\Models\CommandeAchat;
use App\Models\Fournisseur;
use App\Models\Inventaire;
use App\Models\Magasin;
use App\Models\Produit;
use App\Models\User;
use App\Services\AchatService;
use App\Services\CaisseSessionService;
use App\Services\InventaireService;
use App\Services\StockService;
use App\Services\TransfertService;
use App\Services\VenteEnAttenteService;
use App\Services\VenteService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Données transactionnelles de démonstration : tout passe par la couche
 * service (StockService, AchatService, CaisseSessionService, VenteService…),
 * jamais par une écriture directe — pour rester cohérent avec les mouvements
 * de stock, le CMP et les séquences de tickets.
 *
 * Les dates sont simulées via Carbon::setTestNow() autour de chaque bloc
 * d'opérations, puisque les services eux-mêmes s'appuient sur now()/today().
 */
class DemoTransactionsSeeder extends Seeder
{
    private array $moyensPaiementActifs;

    public function run(
        StockService $stockService,
        AchatService $achatService,
        TransfertService $transfertService,
        CaisseSessionService $caisseSessionService,
        VenteService $venteService,
        VenteEnAttenteService $venteEnAttenteService,
        InventaireService $inventaireService,
    ): void {
        $magasins = Magasin::all();
        $produits = Produit::all();
        $fournisseurs = Fournisseur::all();
        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'Gérant'))->first()
            ?? User::first();
        $this->moyensPaiementActifs = \App\Models\MoyenPaiement::where('actif', true)->pluck('id')->all();

        if ($magasins->isEmpty() || $produits->isEmpty() || $fournisseurs->isEmpty()) {
            $this->command?->warn('DemoTransactionsSeeder: référentiel vide, exécutez DemoCatalogueSeeder avant.');

            return;
        }

        // --- Étape 1 : stock initial via des commandes d'achat validées (J-25) ---
        $this->simulerJour(now()->subDays(25), function () use ($magasins, $produits, $fournisseurs, $admin, $achatService) {
            foreach ($magasins as $magasin) {
                foreach (range(1, 6) as $n) {
                    $this->creerEtValiderCommande($magasin, $fournisseurs->random(), $produits, $admin, $achatService);
                }
            }
        });

        // --- Étape 2 : transferts et casses ponctuels (J-20 à J-13) ---
        foreach (range(20, 13) as $offset) {
            $this->simulerJour(now()->subDays($offset), function () use ($magasins, $produits, $admin, $transfertService, $stockService) {
                for ($i = 0; $i < 5; $i++) {
                    $this->tenterTransfert($magasins, $admin, $transfertService);
                }
                for ($i = 0; $i < 2; $i++) {
                    $this->tenterCasse($magasins, $produits, $admin, $stockService);
                }
            });
        }

        // --- Étape 3 : inventaires, un par magasin sur plusieurs dates (J-12 à J-8) ---
        foreach (range(12, 8) as $offset) {
            $this->simulerJour(now()->subDays($offset), function () use ($magasins, $produits, $admin, $inventaireService) {
                foreach ($magasins as $magasin) {
                    $this->creerEtValiderInventaire($magasin, $produits, $admin, $inventaireService);
                }
            });
        }

        // --- Étape 4 : sessions de caisse + ventes, J-14 à J-1 (clôturées) ---
        foreach (range(14, 1) as $offset) {
            $this->simulerJour(now()->subDays($offset)->setTime(9, 0), function () use ($magasins, $produits, $caisseSessionService, $venteService) {
                $caissesDuJour = Caisse::inRandomOrder()->limit(random_int(2, 4))->get();
                foreach ($caissesDuJour as $caisse) {
                    $this->simulerSessionComplete($caisse, $produits, $caisseSessionService, $venteService, cloturer: true);
                }
            });
        }

        // --- Étape 5 : aujourd'hui — sessions en cours + ventes en attente ---
        $this->simulerJour(now()->setTime(8, 30), function () use ($produits, $caisseSessionService, $venteService, $venteEnAttenteService) {
            $caissesDuJour = Caisse::inRandomOrder()->limit(3)->get();
            foreach ($caissesDuJour as $index => $caisse) {
                $session = $this->simulerSessionComplete($caisse, $produits, $caisseSessionService, $venteService, cloturer: $index === 2);

                if ($session && $index !== 2) {
                    // Laisse 1 à 2 paniers en attente sur les sessions restées ouvertes.
                    $this->tenterVenteEnAttente($session, $produits, $venteEnAttenteService);
                }
            }
        });
    }

    private function simulerJour(Carbon $date, \Closure $callback): void
    {
        Carbon::setTestNow($date);

        try {
            $callback();
        } finally {
            Carbon::setTestNow();
        }
    }

    private function creerEtValiderCommande(Magasin $magasin, Fournisseur $fournisseur, $produits, User $admin, AchatService $achatService): void
    {
        $commande = CommandeAchat::create([
            'numero' => $this->genererNumeroCommande(),
            'fournisseur_id' => $fournisseur->id,
            'magasin_id' => $magasin->id,
            'statut' => 'brouillon',
            'date_commande' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        foreach ($produits->random(random_int(2, 4)) as $produit) {
            $prixAchat = (int) round($produit->prix_piece * fake()->randomFloat(2, 0.45, 0.7));
            $commande->lignes()->create([
                'produit_id' => $produit->id,
                'quantite' => random_int(80, 250),
                'prix_achat' => max($prixAchat, 50),
            ]);
        }

        // 9 commandes sur 10 sont validées (stock injecté), le reste reste en brouillon.
        if (random_int(1, 10) <= 9) {
            $achatService->valider($commande->fresh(), $admin);
        }
    }

    private function tenterTransfert($magasins, User $admin, TransfertService $transfertService): void
    {
        $source = $magasins->random();

        // On choisit un produit qui a réellement du stock au magasin source, plutôt
        // que de tirer au hasard dans tout le catalogue (sinon le taux d'échec est
        // élevé et on obtient trop peu de transferts pour la démo).
        $stockSource = \App\Models\Stock::where('magasin_id', $source->id)
            ->where('quantite', '>', 15)
            ->inRandomOrder()
            ->with('produit')
            ->first();

        if (! $stockSource) {
            return;
        }

        $destination = $magasins->where('id', '!=', $source->id)->random();
        $quantite = min(random_int(5, 20), intdiv($stockSource->quantite, 2));

        if ($quantite < 1) {
            return;
        }

        try {
            $transfertService->transferer($stockSource->produit, $source, $destination, $quantite, $admin);
        } catch (StockInsuffisantException) {
            // Course improbable entre la lecture du stock et le transfert : on ignore.
        }
    }

    private function tenterCasse($magasins, $produits, User $admin, StockService $stockService): void
    {
        $magasin = $magasins->random();
        $produit = $produits->random();

        try {
            $stockService->enregistrerMouvement(
                $produit,
                $magasin,
                -random_int(1, 4),
                MouvementStockType::Casse,
                $admin,
                motif: fake()->randomElement(['Casse manutention', 'Chute en rayon', 'Produit fêlé', 'Casse transport']),
            );
        } catch (StockInsuffisantException) {
            // Rien à casser à cette date simulée.
        }
    }

    private function creerEtValiderInventaire(Magasin $magasin, $produits, User $admin, InventaireService $inventaireService): void
    {
        $inventaire = Inventaire::create([
            'magasin_id' => $magasin->id,
            'statut' => 'brouillon',
            'date' => now()->toDateString(),
            'created_by' => $admin->id,
        ]);

        foreach ($produits->random(min(8, $produits->count())) as $produit) {
            $theorique = app(StockService::class)->quantiteDisponible($produit, $magasin);
            // Petit écart aléatoire pour illustrer le rapport (±3 pièces, jamais négatif).
            $comptee = max(0, $theorique + random_int(-3, 3));
            $inventaireService->saisirComptage($inventaire, $produit, $comptee);
        }

        $inventaireService->valider($inventaire->fresh(), $admin);
    }

    private function simulerSessionComplete($caisse, $produits, CaisseSessionService $caisseSessionService, VenteService $venteService, bool $cloturer)
    {
        $caissier = User::whereHas('roles', fn ($q) => $q->where('name', 'Caissier'))
            ->where(fn ($q) => $q->where('magasin_id', $caisse->magasin_id)->orWhereNull('magasin_id'))
            ->inRandomOrder()
            ->first() ?? User::first();

        try {
            $session = $caisseSessionService->ouvrir($caisse, $caissier, [5000, 10000, 15000][random_int(0, 2)]);
        } catch (CaisseNonLibreException) {
            return null;
        }

        foreach (range(1, random_int(3, 7)) as $n) {
            $this->tenterVente($session, $caissier, $produits, $venteService);
        }

        if ($cloturer) {
            $totalEspeces = \App\Models\Paiement::query()
                ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
                ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
                ->sum('montant');

            $theorique = $session->fond_de_caisse + $totalEspeces;
            $ecart = random_int(1, 5) === 1 ? random_int(-300, 300) : 0;

            $caisseSessionService->cloturer($session, max($theorique + $ecart, 0), $caissier);
            $caisseSessionService->fermer($session->fresh());
        }

        return $session->fresh();
    }

    private function tenterVente($session, User $caissier, $produits, VenteService $venteService): void
    {
        $magasin = $session->caisse->magasin;
        $lignes = [];

        foreach ($produits->random(random_int(1, 3)) as $produit) {
            $disponible = app(StockService::class)->quantiteDisponible($produit, $magasin);
            if ($disponible < 1) {
                continue;
            }

            $uniteVente = $produit->uniteVentes->where('actif', true)->first();
            $utiliseUnite = $uniteVente && $disponible >= $uniteVente->facteur && random_int(1, 3) === 1;

            $ligne = [
                'produit_id' => $produit->id,
                'unite_vente_id' => $utiliseUnite ? $uniteVente->id : null,
                'quantite' => $utiliseUnite ? 1 : min(random_int(1, 3), $disponible),
            ];

            if (random_int(1, 5) === 1) {
                $ligne['remise_type'] = random_int(0, 1) ? 'pourcentage' : 'montant';
                $ligne['remise_valeur'] = $ligne['remise_type'] === 'pourcentage' ? random_int(5, 15) : random_int(50, 300);
            }

            $lignes[] = $ligne;
        }

        if (empty($lignes)) {
            return;
        }

        // Calcule le net à payer approximatif pour émettre un paiement cohérent
        // (le service revalide/recalcule tout de toute façon).
        $sousTotal = 0;
        foreach ($lignes as $ligne) {
            $produit = $produits->firstWhere('id', $ligne['produit_id']);
            $uniteVente = $ligne['unite_vente_id'] ? $produit->uniteVentes->firstWhere('id', $ligne['unite_vente_id']) : null;
            $prixUnitaire = $uniteVente ? $uniteVente->prix : $produit->prix_piece;
            $sousTotalLigne = $prixUnitaire * $ligne['quantite'];
            if (! empty($ligne['remise_type'])) {
                $remise = $ligne['remise_type'] === 'pourcentage'
                    ? (int) round($sousTotalLigne * $ligne['remise_valeur'] / 100)
                    : min($ligne['remise_valeur'], $sousTotalLigne);
                $sousTotalLigne -= $remise;
            }
            $sousTotal += $sousTotalLigne;
        }

        if ($sousTotal <= 0) {
            return;
        }

        $paiements = random_int(1, 4) === 1 && count($this->moyensPaiementActifs) > 1
            ? $this->paiementMixte($sousTotal)
            : [['moyen_paiement_id' => $this->moyenEspeces(), 'montant' => $sousTotal]];

        try {
            $venteService->vendre(
                session: $session,
                caissier: $caissier,
                lignes: $lignes,
                paiements: $paiements,
            );
        } catch (StockInsuffisantException|\InvalidArgumentException) {
            // Stock ou paiement incohérent pour cette combinaison simulée : on saute cette vente.
        }
    }

    private function tenterVenteEnAttente($session, $produits, VenteEnAttenteService $venteEnAttenteService): void
    {
        $lignes = $produits->random(random_int(1, 2))->map(fn ($p) => [
            'produit_id' => $p->id,
            'unite_vente_id' => null,
            'quantite' => random_int(1, 2),
        ])->values()->all();

        try {
            $venteEnAttenteService->mettreEnAttente($session->fresh(), $session->caissier, $lignes, fake()->randomElement([
                'Client au téléphone', 'Client parti chercher son porte-monnaie', null,
            ]));
        } catch (\Throwable) {
            // Ignoré si la session n'est plus utilisable pour une raison ou une autre.
        }
    }

    private function paiementMixte(int $total): array
    {
        $premier = (int) round($total * fake()->randomFloat(2, 0.3, 0.7));
        $second = $total - $premier;
        $autresMoyens = array_values(array_diff($this->moyensPaiementActifs, [$this->moyenEspeces()]));

        return [
            ['moyen_paiement_id' => $this->moyenEspeces(), 'montant' => $premier],
            ['moyen_paiement_id' => $autresMoyens[array_rand($autresMoyens)] ?? $this->moyenEspeces(), 'montant' => $second],
        ];
    }

    private function moyenEspeces(): int
    {
        static $id = null;
        $id ??= \App\Models\MoyenPaiement::where('est_espece', true)->value('id');

        return $id;
    }

    private function genererNumeroCommande(): string
    {
        do {
            $numero = 'BC-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (CommandeAchat::where('numero', $numero)->exists());

        return $numero;
    }
}
