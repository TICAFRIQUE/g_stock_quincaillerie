<?php

namespace App\Exceptions;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Partagée entre LigneVente et LigneCommandeAchat (les deux exposent une
 * relation produit()) — évite de dupliquer la même exception côté client et
 * côté fournisseur.
 */
class QuantiteRetourInvalideException extends RuntimeException
{
    public function __construct(
        public readonly Model $ligne,
        public readonly int|float $quantiteDemandee,
        public readonly int|float $quantiteRestante,
    ) {
        parent::__construct(
            "Quantité de retour invalide pour {$ligne->produit->sku} : ".
            'demandé '.quantite($quantiteDemandee)." pièce(s), retournable ".quantite($quantiteRestante).'.'
        );
    }
}
