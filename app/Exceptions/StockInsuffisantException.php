<?php

namespace App\Exceptions;

use App\Models\Magasin;
use App\Models\Produit;
use RuntimeException;

class StockInsuffisantException extends RuntimeException
{
    public function __construct(
        public readonly Produit $produit,
        public readonly Magasin $magasin,
        public readonly int|float $quantiteDemandee,
        public readonly int|float $quantiteDisponible,
    ) {
        parent::__construct(
            "Stock insuffisant pour {$produit->sku} au magasin {$magasin->nom} : ".
            'demandé '.quantite($quantiteDemandee)." pièce(s), disponible ".quantite($quantiteDisponible).'.'
        );
    }
}
