<?php

namespace App\Enums;

enum MouvementCaisseType: string
{
    case Entree = 'entree';
    case Sortie = 'sortie';

    public function libelle(): string
    {
        return match ($this) {
            self::Entree => 'Entrée',
            self::Sortie => 'Sortie',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::Entree => 'text-bg-success',
            self::Sortie => 'text-bg-danger',
        };
    }
}
