<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Exceptions\StockInsuffisantException;
use App\Models\Magasin;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use App\Support\Arrondi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Point de passage unique pour toute variation de stock. Le stock est dérivé,
 * jamais écrasé : chaque appel insère un mouvement immuable et répercute la
 * variation sur le compteur matérialisé `stocks` dans la même transaction.
 */
class StockService
{
    /**
     * @param  int  $quantite  Signé : positif = entrée, négatif = sortie.
     * @param  int|null  $prixAchat  Requis uniquement pour un mouvement de type Reception
     *                               (sert au recalcul du coût moyen pondéré).
     */
    public function enregistrerMouvement(
        Produit $produit,
        Magasin $magasin,
        int $quantite,
        MouvementStockType $type,
        User $auteur,
        ?Model $reference = null,
        ?string $motif = null,
        ?int $prixAchat = null,
    ): MouvementStock {
        if ($type === MouvementStockType::Reception && $prixAchat === null) {
            throw new InvalidArgumentException('prixAchat est requis pour un mouvement de type réception.');
        }

        return DB::transaction(function () use ($produit, $magasin, $quantite, $type, $auteur, $reference, $motif, $prixAchat) {
            $stock = Stock::query()
                ->where('produit_id', $produit->id)
                ->where('magasin_id', $magasin->id)
                ->lockForUpdate()
                ->first();

            if (! $stock) {
                $stock = new Stock([
                    'produit_id' => $produit->id,
                    'magasin_id' => $magasin->id,
                    'quantite' => 0,
                    'cout_moyen_pondere' => 0,
                ]);
            }

            $nouvelleQuantite = $stock->quantite + $quantite;

            if ($nouvelleQuantite < 0) {
                throw new StockInsuffisantException($produit, $magasin, abs($quantite), $stock->quantite);
            }

            if ($type === MouvementStockType::Reception) {
                $stock->cout_moyen_pondere = $stock->quantite > 0
                    ? Arrondi::entier(
                        ($stock->quantite * $stock->cout_moyen_pondere + $quantite * $prixAchat) / $nouvelleQuantite
                    )
                    : $prixAchat;
            }

            $stock->quantite = $nouvelleQuantite;
            $stock->save();

            return MouvementStock::create([
                'produit_id' => $produit->id,
                'magasin_id' => $magasin->id,
                'type' => $type,
                'quantite' => $quantite,
                'reference_type' => $reference?->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'motif' => $motif,
                'created_by' => $auteur->id,
            ]);
        });
    }

    public function quantiteDisponible(Produit $produit, Magasin $magasin): int
    {
        return Stock::query()
            ->where('produit_id', $produit->id)
            ->where('magasin_id', $magasin->id)
            ->value('quantite') ?? 0;
    }

    public function coutMoyenPondere(Produit $produit, Magasin $magasin): int
    {
        return Stock::query()
            ->where('produit_id', $produit->id)
            ->where('magasin_id', $magasin->id)
            ->value('cout_moyen_pondere') ?? 0;
    }
}
