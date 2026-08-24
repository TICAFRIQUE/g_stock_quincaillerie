<?php

namespace App\Enums;

enum EcritureCompteTresorerieType: string
{
    case DepotSessionCloturee = 'depot_session_cloturee';
    case SortieManuelle = 'sortie_manuelle';
    case EntreeManuelle = 'entree_manuelle';
    case ReglementFournisseur = 'reglement_fournisseur';
    case RemboursementAvoirClient = 'remboursement_avoir_client';
    case RemboursementAvoirFournisseur = 'remboursement_avoir_fournisseur';
    case VirementSortant = 'virement_sortant';
    case VirementEntrant = 'virement_entrant';

    public function libelle(): string
    {
        return match ($this) {
            self::DepotSessionCloturee => 'Dépôt (clôture de session)',
            self::SortieManuelle => 'Sortie manuelle',
            self::EntreeManuelle => 'Entrée manuelle',
            self::ReglementFournisseur => 'Règlement fournisseur',
            self::RemboursementAvoirClient => 'Remboursement avoir client',
            self::RemboursementAvoirFournisseur => 'Remboursement avoir fournisseur',
            self::VirementSortant => 'Virement sortant',
            self::VirementEntrant => 'Virement entrant',
        };
    }

    public function classeBadge(): string
    {
        return match ($this) {
            self::DepotSessionCloturee, self::EntreeManuelle, self::VirementEntrant, self::RemboursementAvoirFournisseur => 'text-bg-success',
            self::SortieManuelle, self::VirementSortant, self::ReglementFournisseur, self::RemboursementAvoirClient => 'text-bg-danger',
        };
    }

    /**
     * Un + ou un − devant le montant : direction réelle de l'argent, jamais
     * déduite du signe stocké (qui reste la seule source de vérité pour le
     * solde, voir CompteTresorerieService).
     */
    public function estEntree(): bool
    {
        return match ($this) {
            self::DepotSessionCloturee, self::EntreeManuelle, self::VirementEntrant, self::RemboursementAvoirFournisseur => true,
            self::SortieManuelle, self::VirementSortant, self::ReglementFournisseur, self::RemboursementAvoirClient => false,
        };
    }
}
