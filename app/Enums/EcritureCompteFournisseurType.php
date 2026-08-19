<?php

namespace App\Enums;

enum EcritureCompteFournisseurType: string
{
    case AchatCredit = 'achat_credit';
    case Reglement = 'reglement';
    case RetourFournisseur = 'retour_fournisseur';
    case AnnulationAchat = 'annulation_achat';
    case RemboursementAvoir = 'remboursement_avoir';

    public function libelle(): string
    {
        return match ($this) {
            self::AchatCredit => 'Achat à crédit',
            self::Reglement => 'Règlement',
            self::RetourFournisseur => 'Retour fournisseur',
            self::AnnulationAchat => 'Annulation d\'achat',
            self::RemboursementAvoir => 'Remboursement d\'avoir',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::AchatCredit => 'text-bg-warning',
            self::Reglement => 'text-bg-success',
            self::RetourFournisseur => 'text-bg-info',
            self::AnnulationAchat => 'text-bg-dark',
            self::RemboursementAvoir => 'text-bg-primary',
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
            self::RetourFournisseur => 'table-info',
            self::AnnulationAchat => 'table-secondary',
            self::RemboursementAvoir => 'table-primary',
        };
    }
}
