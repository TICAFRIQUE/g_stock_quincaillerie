<?php

use App\Models\Devise;

if (! function_exists('montant')) {
    /**
     * Formatage centralisé d'un montant (toujours un entier, franc — voir
     * CLAUDE.md "Argent et arrondis") avec l'abréviation de la devise
     * actuellement configurée (Paramètres). Remplace les ~34 vues qui
     * concaténaient auparavant `number_format($x, 0, ',', ' ') . ' F'` en
     * dur — un seul endroit à corriger si le format doit changer.
     */
    function montant(int|float|null $valeur): string
    {
        return number_format((float) ($valeur ?? 0), 0, ',', ' ').' '.Devise::abreviationActuelle();
    }
}

if (! function_exists('quantite')) {
    /**
     * Formatage centralisé d'une quantité (colonnes decimal(12,3), voir
     * migration ..._convertir_quantites_en_decimal) : jusqu'à 3 décimales,
     * les zéros non significatifs sont retirés (5.000 → "5", 5.200 → "5.2",
     * 5.250 → "5.25") — une quantité entière (cas de la grande majorité des
     * ventes/achats) s'affiche donc exactement comme avant l'introduction
     * du décimal. Le cast Eloquent decimal renvoie une chaîne (ex.
     * "5.000") : accepté tel quel en plus d'un int/float.
     */
    function quantite(int|float|string|null $valeur): string
    {
        $formate = number_format((float) ($valeur ?? 0), 3, '.', '');

        return rtrim(rtrim($formate, '0'), '.');
    }
}
