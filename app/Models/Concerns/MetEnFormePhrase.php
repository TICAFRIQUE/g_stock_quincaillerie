<?php

namespace App\Models\Concerns;

/**
 * Met en forme automatiquement un nom/libellé à la casse "phrase" (première
 * lettre en majuscule, reste en minuscule) — quelle que soit la saisie (tout
 * en majuscules, tout en minuscules, mélangé), la valeur enregistrée reste
 * cohérente partout où elle est affichée (catalogue, tickets, factures,
 * sélecteurs…). Appliqué à la fois à la création et à la modification,
 * puisqu'il s'agit d'un mutator d'attribut Eloquent (voir chaque modèle
 * utilisateur de ce trait).
 */
trait MetEnFormePhrase
{
    protected static function casseEnPhrase(?string $valeur): ?string
    {
        if ($valeur === null || trim($valeur) === '') {
            return $valeur;
        }

        $valeur = mb_strtolower(trim($valeur));

        return mb_strtoupper(mb_substr($valeur, 0, 1)).mb_substr($valeur, 1);
    }
}
