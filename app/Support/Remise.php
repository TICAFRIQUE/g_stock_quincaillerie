<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Résolution d'une remise (montant ou pourcentage) en montant entier,
 * jamais au-delà de sa base — logique unique partagée entre la vente
 * (VenteService) et les montants indicatifs d'un devis (Devis), pour que
 * les deux restent reproductibles avec la même règle d'arrondi.
 */
class Remise
{
    public static function resoudre(?string $type, ?int $valeur, int $base): int
    {
        if ($type === null || $valeur === null) {
            return 0;
        }

        $montant = match ($type) {
            'montant' => $valeur,
            'pourcentage' => Arrondi::entier($base * $valeur / 100),
            default => throw new InvalidArgumentException("Type de remise inconnu : {$type}"),
        };

        return min($montant, $base);
    }
}
