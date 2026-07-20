<?php

namespace App\Http\Controllers\Concerns;

/**
 * Un utilisateur rattaché à un magasin (caissier, gérant) ne doit pouvoir
 * consulter ou agir que sur les ressources de son propre magasin, même en
 * devinant une URL. Un utilisateur sans magasin_id (superadmin) voit tout —
 * même logique que le filtrage déjà appliqué dans les pages d'index.
 */
trait AutoriseMagasin
{
    protected function assurerMagasin(?int $magasinCible): void
    {
        $magasinId = request()->user()->magasin_id;

        abort_if(
            $magasinId && $magasinCible && $magasinId !== $magasinCible,
            403,
            'Cette ressource appartient à un autre magasin.'
        );
    }
}
