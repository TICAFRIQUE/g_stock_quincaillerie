<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Models\Magasin;
use App\Models\Produit;
use App\Models\Transfert;
use App\Models\User;
use App\Support\Arrondi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Transfert inter-magasin simple : sortie du magasin source + entrée dans le
 * magasin destination, sans valorisation (le CMP n'est pas recalculé par un
 * transfert, seul un achat le fait).
 */
class TransfertService
{
    public function __construct(private readonly StockService $stockService) {}

    public function transferer(
        Produit $produit,
        Magasin $magasinSource,
        Magasin $magasinDestination,
        int|float $quantite,
        User $auteur,
    ): Transfert {
        if ($magasinSource->is($magasinDestination)) {
            throw new InvalidArgumentException('Le magasin source et le magasin destination doivent être différents.');
        }

        $quantite = Arrondi::quantite((float) $quantite);

        if ($quantite <= 0) {
            throw new InvalidArgumentException('La quantité transférée doit être positive.');
        }

        return DB::transaction(function () use ($produit, $magasinSource, $magasinDestination, $quantite, $auteur) {
            $transfert = Transfert::create([
                'produit_id' => $produit->id,
                'quantite' => $quantite,
                'magasin_source_id' => $magasinSource->id,
                'magasin_destination_id' => $magasinDestination->id,
                'created_by' => $auteur->id,
            ]);

            $this->stockService->enregistrerMouvement(
                $produit, $magasinSource, -$quantite, MouvementStockType::Transfert, $auteur, reference: $transfert,
            );

            $this->stockService->enregistrerMouvement(
                $produit, $magasinDestination, $quantite, MouvementStockType::Transfert, $auteur, reference: $transfert,
            );

            return $transfert;
        });
    }
}
