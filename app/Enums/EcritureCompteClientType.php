<?php

namespace App\Enums;

enum EcritureCompteClientType: string
{
    case VenteCredit = 'vente_credit';
    case Reglement = 'reglement';

    public function libelle(): string
    {
        return match ($this) {
            self::VenteCredit => 'Vente à crédit',
            self::Reglement => 'Règlement',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::VenteCredit => 'text-bg-warning',
            self::Reglement => 'text-bg-success',
        };
    }
}
