<?php

namespace App\Exceptions;

use App\Models\LigneVente;
use RuntimeException;

class QuantiteLivraisonInvalideException extends RuntimeException
{
    public function __construct(
        public readonly LigneVente $ligne,
        public readonly int|float $quantiteDemandee,
        public readonly int|float $quantiteRestante,
    ) {
        parent::__construct(
            "Quantité de livraison invalide pour {$ligne->produit->sku} : ".
            'demandé '.quantite($quantiteDemandee)." pièce(s), livrable ".quantite($quantiteRestante).'.'
        );
    }
}
