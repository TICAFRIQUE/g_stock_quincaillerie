<?php

namespace App\Exceptions;

use App\Models\LigneCommandeAchat;
use RuntimeException;

class QuantiteReceptionInvalideException extends RuntimeException
{
    public function __construct(
        public readonly LigneCommandeAchat $ligne,
        public readonly int|float $quantiteDemandee,
        public readonly int|float $quantiteRestante,
    ) {
        parent::__construct(
            "Quantité de réception invalide pour {$ligne->produit->sku} : ".
            'demandé '.quantite($quantiteDemandee)." pièce(s), recevable ".quantite($quantiteRestante).'.'
        );
    }
}
