<?php

namespace App\Exceptions;

use App\Models\LigneVente;
use RuntimeException;

class QuantiteLivraisonInvalideException extends RuntimeException
{
    public function __construct(
        public readonly LigneVente $ligne,
        public readonly int $quantiteDemandee,
        public readonly int $quantiteRestante,
    ) {
        parent::__construct(
            "Quantité de livraison invalide pour {$ligne->produit->sku} : ".
            "demandé {$quantiteDemandee} pièce(s), livrable {$quantiteRestante}."
        );
    }
}
