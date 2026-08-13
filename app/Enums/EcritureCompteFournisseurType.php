<?php

namespace App\Enums;

enum EcritureCompteFournisseurType: string
{
    case AchatCredit = 'achat_credit';
    case Reglement = 'reglement';

    public function libelle(): string
    {
        return match ($this) {
            self::AchatCredit => 'Achat à crédit',
            self::Reglement => 'Règlement',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::AchatCredit => 'text-bg-warning',
            self::Reglement => 'text-bg-success',
        };
    }

    /**
     * Fond de ligne (tableau historique du compte) — même code couleur que
     * le badge, pour distinguer d'un coup d'œil achat à crédit et règlement.
     */
    public function classeLigne(): string
    {
        return match ($this) {
            self::AchatCredit => 'table-warning',
            self::Reglement => 'table-success',
        };
    }
}
