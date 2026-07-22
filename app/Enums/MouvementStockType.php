<?php

namespace App\Enums;

enum MouvementStockType: string
{
    case Reception = 'reception';
    case Vente = 'vente';
    case Casse = 'casse';
    case Transfert = 'transfert';
    case Ajustement = 'ajustement';
    case Annulation = 'annulation';

    public function libelle(): string
    {
        return match ($this) {
            self::Reception => 'Réception',
            self::Vente => 'Vente',
            self::Casse => 'Casse',
            self::Transfert => 'Transfert',
            self::Ajustement => 'Ajustement',
            self::Annulation => 'Annulation de vente',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::Reception => 'text-bg-success',
            self::Vente => 'text-bg-primary',
            self::Casse => 'text-bg-danger',
            self::Transfert => 'text-bg-info',
            self::Ajustement => 'text-bg-secondary',
            self::Annulation => 'text-bg-warning',
        };
    }
}
