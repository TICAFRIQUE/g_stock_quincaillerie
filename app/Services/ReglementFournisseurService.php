<?php

namespace App\Services;

use App\Enums\MouvementCaisseType;
use App\Models\CommandeAchat;
use App\Models\Fournisseur;
use App\Models\MoyenPaiement;
use App\Models\ReglementFournisseur;
use App\Models\SessionCaisse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Paiement d'une dette fournisseur, immuable comme ReglementClientService.
 * Indépendant de la caisse pour tout paiement non-espèces (règle 17), mais
 * la part payée en espèces exige une session ouverte et génère
 * automatiquement une sortie de caisse liée (voir CLAUDE.md, Mouvements de
 * caisse) — c'est bien l'argent du tiroir qui sert à payer le fournisseur.
 */
class ReglementFournisseurService
{
    public function __construct(
        private readonly CompteFournisseurService $compteFournisseurService,
        private readonly CaisseMouvementService $caisseMouvementService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function encaisser(
        Fournisseur $fournisseur,
        User $auteur,
        array $paiements,
        ?CommandeAchat $commandeAchat = null,
        ?SessionCaisse $session = null,
    ): ReglementFournisseur {
        if (empty($paiements)) {
            throw new InvalidArgumentException('Un règlement doit comporter au moins un paiement.');
        }

        $montant = array_sum(array_column($paiements, 'montant'));
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du règlement doit être positif.');
        }

        $moyensEspeces = MoyenPaiement::where('est_espece', true)->pluck('id')->all();
        $montantEspeces = array_sum(array_map(
            fn (array $p) => in_array($p['moyen_paiement_id'], $moyensEspeces) ? $p['montant'] : 0,
            $paiements
        ));

        if ($montantEspeces > 0 && $session === null) {
            throw new InvalidArgumentException(
                'Une session de caisse est requise : une partie du règlement est payée en espèces, cet argent sort du tiroir.'
            );
        }

        return DB::transaction(function () use ($fournisseur, $auteur, $paiements, $montant, $commandeAchat, $session, $montantEspeces) {
            $reglement = ReglementFournisseur::create([
                'fournisseur_id' => $fournisseur->id,
                'commande_achat_id' => $commandeAchat?->id,
                'session_caisse_id' => $session?->id,
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

            if ($montantEspeces > 0) {
                // Peut lever SoldeCaisseInsuffisantException si le tiroir
                // n'a théoriquement pas assez d'espèces — annule tout.
                $this->caisseMouvementService->enregistrer(
                    session: $session,
                    type: MouvementCaisseType::Sortie,
                    montant: $montantEspeces,
                    motif: "Règlement fournisseur {$fournisseur->nom}",
                    auteur: $auteur,
                    reference: $reglement,
                );
            }

            return $reglement->refresh();
        });
    }
}
