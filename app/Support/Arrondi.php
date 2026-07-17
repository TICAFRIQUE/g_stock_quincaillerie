<?php

namespace App\Support;

/**
 * Règle d'arrondi unique (entier le plus proche, half up), appliquée partout où
 * un montant ou un coût XOF doit être résolu en entier (pas de sous-unité).
 */
class Arrondi
{
    public static function entier(float $valeur): int
    {
        return (int) floor($valeur + 0.5);
    }
}
