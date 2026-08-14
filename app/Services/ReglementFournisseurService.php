<?php

namespace App\Services;

use App\Models\CommandeAchat;
use App\Models\Fournisseur;
use App\Models\ReglementFournisseur;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Paiement d'une dette fournisseur, immuable comme ReglementClientService
 * mais SANS session de caisse : le règlement fournisseur est indépendant du
 * tiroir (décision produit, voir CLAUDE.md — contrairement au règlement
 * client, régi par la règle 14).
 */
class ReglementFournisseurService
{
    public function __construct(private readonly CompteFournisseurService $compteFournisseurService) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function encaisser(Fournisseur $fournisseur, User $auteur, array $paiements, ?CommandeAchat $commandeAchat = null): ReglementFournisseur
    {
        if (empty($paiements)) {
            throw new InvalidArgumentException('Un règlement doit comporter au moins un paiement.');
        }

        $montant = array_sum(array_column($paiements, 'montant'));
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du règlement doit être positif.');
        }

        return DB::transaction(function () use ($fournisseur, $auteur, $paiements, $montant, $commandeAchat) {
            $reglement = ReglementFournisseur::create([
                'fournisseur_id' => $fournisseur->id,
                'commande_achat_id' => $commandeAchat?->id,
                'created_by' => $auteur->id,
                'montant' => $montant,
            ]);

            foreach ($paiements as $p) {
                $reglement->paiements()->create([
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            // Lève SoldeFournisseurInsuffisantException si le règlement
            // dépasse la dette actuelle, ce qui annule toute la transaction.
            $this->compteFournisseurService->enregistrerReglement($fournisseur, $montant, $reglement, $auteur);

            return $reglement->refresh();
        });
    }
}
