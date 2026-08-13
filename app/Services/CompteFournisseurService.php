<?php

namespace App\Services;

use App\Enums\EcritureCompteFournisseurType;
use App\Exceptions\SoldeFournisseurInsuffisantException;
use App\Models\EcritureCompteFournisseur;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Point de passage unique pour toute variation du compte d'un fournisseur.
 * Le solde est dérivé, jamais écrasé : chaque appel insère une écriture
 * immuable — mirroring CompteClientService, sans notion de limite de crédit
 * (pas demandée côté fournisseur).
 */
class CompteFournisseurService
{
    public function solde(Fournisseur $fournisseur): int
    {
        return EcritureCompteFournisseur::where('fournisseur_id', $fournisseur->id)->sum('montant');
    }

    /**
     * Enregistre la dette d'un achat à crédit (reste dû après paiements
     * immédiats). À appeler à l'intérieur de la transaction de validation de
     * la commande d'achat.
     */
    public function crediterDette(Fournisseur $fournisseur, int $montant, Model $reference, User $auteur): EcritureCompteFournisseur
    {
        $fournisseur = Fournisseur::whereKey($fournisseur->id)->lockForUpdate()->firstOrFail();

        return EcritureCompteFournisseur::create([
            'fournisseur_id' => $fournisseur->id,
            'type' => EcritureCompteFournisseurType::AchatCredit,
            'montant' => $montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }

    /**
     * Enregistre un règlement (paiement total ou partiel d'une dette). Ne
     * peut jamais dépasser la dette actuelle (pas de solde créditeur dans le
     * MVP).
     */
    public function enregistrerReglement(Fournisseur $fournisseur, int $montant, Model $reference, User $auteur): EcritureCompteFournisseur
    {
        $fournisseur = Fournisseur::whereKey($fournisseur->id)->lockForUpdate()->firstOrFail();
        $soldeActuel = $this->solde($fournisseur);

        if ($montant > $soldeActuel) {
            throw new SoldeFournisseurInsuffisantException($fournisseur, $montant, $soldeActuel);
        }

        return EcritureCompteFournisseur::create([
            'fournisseur_id' => $fournisseur->id,
            'type' => EcritureCompteFournisseurType::Reglement,
            'montant' => -$montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }
}
