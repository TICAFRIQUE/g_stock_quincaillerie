<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Exceptions\StockInsuffisantException;
use App\Models\Magasin;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\User;
use App\Notifications\StockSousSeuil;
use App\Support\Arrondi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Point de passage unique pour toute variation de stock. Le stock est dérivé,
 * jamais écrasé : chaque appel insère un mouvement immuable et répercute la
 * variation sur le compteur matérialisé `stocks` dans la même transaction.
 */
class StockService
{
    /**
     * @param  int|float  $quantite  Signé : positif = entrée, négatif = sortie.
     * @param  int|null  $prixAchat  Requis uniquement pour un mouvement de type Reception
     *                               (sert au recalcul du coût moyen pondéré).
     */
    public function enregistrerMouvement(
        Produit $produit,
        Magasin $magasin,
        int|float $quantite,
        MouvementStockType $type,
        User $auteur,
        ?Model $reference = null,
        ?string $motif = null,
        ?int $prixAchat = null,
    ): MouvementStock {
        if ($type === MouvementStockType::Reception && $prixAchat === null) {
            throw new InvalidArgumentException('prixAchat est requis pour un mouvement de type réception.');
        }

        $quantite = Arrondi::quantite((float) $quantite);

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

            $ancienneQuantite = (float) $stock->quantite;
            $nouvelleQuantite = Arrondi::quantite($ancienneQuantite + $quantite);

            if ($nouvelleQuantite < 0) {
                throw new StockInsuffisantException($produit, $magasin, abs($quantite), $ancienneQuantite);
            }

            if ($type === MouvementStockType::Reception) {
                $stock->cout_moyen_pondere = $ancienneQuantite > 0
                    ? Arrondi::entier(
                        ($ancienneQuantite * $stock->cout_moyen_pondere + $quantite * $prixAchat) / $nouvelleQuantite
                    )
                    : $prixAchat;
            }

            $stock->quantite = $nouvelleQuantite;

            // État de l'épisode d'alerte (pas juste le franchissement) :
            // si on est sous le seuil et qu'aucune alerte n'est encore
            // active, on la déclenche — que ce soit un nouveau franchissement
            // OU un stock déjà sous le seuil (repris tel quel, jamais notifié
            // jusqu'ici). Dès que le stock repasse au-dessus, on réarme pour
            // la prochaine fois. Envoyée après commit pour ne jamais notifier
            // un mouvement qui serait finalement annulé plus loin.
            if ($nouvelleQuantite <= $produit->seuil_alerte) {
                if ($stock->alerte_seuil_envoyee_at === null) {
                    $stock->alerte_seuil_envoyee_at = now();
                    DB::afterCommit(fn () => $this->notifierStockSousSeuil($produit, $magasin, $nouvelleQuantite));
                }
            } else {
                $stock->alerte_seuil_envoyee_at = null;
            }

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

    public function quantiteDisponible(Produit $produit, Magasin $magasin): float
    {
        return (float) (Stock::query()
            ->where('produit_id', $produit->id)
            ->where('magasin_id', $magasin->id)
            ->value('quantite') ?? 0);
    }

    public function coutMoyenPondere(Produit $produit, Magasin $magasin): int
    {
        return Stock::query()
            ->where('produit_id', $produit->id)
            ->where('magasin_id', $magasin->id)
            ->value('cout_moyen_pondere') ?? 0;
    }

    /**
     * Disponibilité d'un produit sur chaque magasin/dépôt actif — utilisé
     * par le sélecteur "source" à la vente (voir CLAUDE.md, un produit peut
     * être vendu depuis un lieu différent du magasin de la caisse). Une
     * requête, tous les magasins actifs même sans ligne `stocks` (0 par
     * défaut) pour que la liste des lieux proposés reste complète.
     *
     * @return \Illuminate\Support\Collection<int, array{id:int, nom:string, type:string, quantite:float}>
     */
    public function disponibiliteParMagasin(Produit $produit): \Illuminate\Support\Collection
    {
        $quantitesParMagasin = Stock::where('produit_id', $produit->id)->pluck('quantite', 'magasin_id');

        return Magasin::where('actif', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'type'])
            ->map(fn (Magasin $magasin) => [
                'id' => $magasin->id,
                'nom' => $magasin->nom,
                'type' => $magasin->type,
                'quantite' => (float) ($quantitesParMagasin[$magasin->id] ?? 0),
            ]);
    }

    private function notifierStockSousSeuil(Produit $produit, Magasin $magasin, int|float $quantite): void
    {
        $destinataires = User::gerantsEtSuperadmins($magasin->id);

        Notification::send($destinataires, new StockSousSeuil($produit, $magasin, $quantite));
    }
}
