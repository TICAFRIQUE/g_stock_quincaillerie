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

    /**
     * Arrondi une quantité à 3 décimales (précision des colonnes
     * decimal(12,3), voir migration ..._convertir_quantites_en_decimal) —
     * évite qu'une imprécision flottante intermédiaire (ex. quantite ×
     * facteur) ne fausse une comparaison de stock disponible avant que la
     * valeur n'atteigne la colonne DECIMAL, qui arrondirait de toute façon
     * mais seulement à l'insertion.
     */
    public static function quantite(float $valeur): float
    {
        return round($valeur, 3);
    }
}
