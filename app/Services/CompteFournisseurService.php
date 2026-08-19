<?php

namespace App\Services;

use App\Enums\EcritureCompteFournisseurType;
use App\Exceptions\AvoirFournisseurInsuffisantException;
use App\Exceptions\SoldeFournisseurInsuffisantException;
use App\Models\EcritureCompteFournisseur;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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

    /**
     * Crédite un avoir suite à un retour de marchandise au fournisseur.
     * Contrairement à enregistrerReglement(), aucun plafond bas — même
     * raisonnement que CompteClientService::crediterRetour().
     */
    public function crediterRetour(Fournisseur $fournisseur, int $montant, Model $reference, User $auteur): EcritureCompteFournisseur
    {
        $fournisseur = Fournisseur::whereKey($fournisseur->id)->lockForUpdate()->firstOrFail();

        return EcritureCompteFournisseur::create([
            'fournisseur_id' => $fournisseur->id,
            'type' => EcritureCompteFournisseurType::RetourFournisseur,
            'montant' => -$montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }

    /**
     * Reverse la dette posée par crediterDette() lors de l'annulation d'un
     * achat. Aucun plafond bas : si un règlement partiel avait déjà été fait
     * entre-temps, le solde peut légitimement passer en avoir (le fournisseur
     * nous doit alors ce qui a déjà été payé pour une marchandise finalement
     * non reçue).
     */
    public function annulerDette(Fournisseur $fournisseur, int $montant, Model $reference, User $auteur): EcritureCompteFournisseur
    {
        $fournisseur = Fournisseur::whereKey($fournisseur->id)->lockForUpdate()->firstOrFail();

        return EcritureCompteFournisseur::create([
            'fournisseur_id' => $fournisseur->id,
            'type' => EcritureCompteFournisseurType::AnnulationAchat,
            'montant' => -$montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }

    /**
     * Rembourse (le fournisseur nous reverse) tout ou partie de l'avoir qu'il
     * nous doit (solde négatif). Écriture positive : ramène le solde vers 0,
     * jamais au-delà — mirroring CompteClientService::rembourserAvoir().
     */
    public function rembourserAvoir(Fournisseur $fournisseur, int $montant, Model $reference, User $auteur): EcritureCompteFournisseur
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du remboursement doit être positif.');
        }

        $fournisseur = Fournisseur::whereKey($fournisseur->id)->lockForUpdate()->firstOrFail();
        $avoirDisponible = max(0, -$this->solde($fournisseur));

        if ($montant > $avoirDisponible) {
            throw new AvoirFournisseurInsuffisantException($fournisseur, $montant, $avoirDisponible);
        }

        return EcritureCompteFournisseur::create([
            'fournisseur_id' => $fournisseur->id,
            'type' => EcritureCompteFournisseurType::RemboursementAvoir,
            'montant' => $montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }
}
