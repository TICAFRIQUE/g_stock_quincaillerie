<?php

namespace App\Enums;

enum EcritureCompteClientType: string
{
    case VenteCredit = 'vente_credit';
    case Reglement = 'reglement';
    case RetourClient = 'retour_client';
    case AnnulationVente = 'annulation_vente';
    case RemboursementAvoir = 'remboursement_avoir';

    public function libelle(): string
    {
        return match ($this) {
            self::VenteCredit => 'Vente à crédit',
            self::Reglement => 'Règlement',
            self::RetourClient => 'Retour client',
            self::AnnulationVente => 'Annulation de vente',
            self::RemboursementAvoir => 'Remboursement d\'avoir',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::VenteCredit => 'text-bg-warning',
            self::Reglement => 'text-bg-success',
            self::RetourClient => 'text-bg-info',
            self::AnnulationVente => 'text-bg-dark',
            self::RemboursementAvoir => 'text-bg-primary',
        };
    }
}
