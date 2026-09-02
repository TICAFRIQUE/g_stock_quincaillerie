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
