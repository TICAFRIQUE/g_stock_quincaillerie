<?php

namespace App\Services;

use App\Enums\EcritureCompteTresorerieType;
use App\Models\Fournisseur;
use App\Models\MoyenPaiement;
use App\Models\RemboursementAvoirFournisseur;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rembourse (tout ou partie) l'avoir qu'un fournisseur nous doit — le
 * fournisseur nous reverse cet argent. Symétrique de
 * RemboursementAvoirClientService : la part reçue en espèces entre
 * directement dans la Caisse Générale (voir CLAUDE.md, Trésorerie), jamais
 * dans le tiroir d'un caissier — une ENTRÉE (l'argent rentre, contrairement
 * au remboursement client qui en sort).
 */
class RemboursementAvoirFournisseurService
{
    public function __construct(
        private readonly CompteFournisseurService $compteFournisseurService,
        private readonly CompteTresorerieService $compteTresorerieService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function rembourser(
        Fournisseur $fournisseur,
        User $auteur,
        array $paiements,
    ): RemboursementAvoirFournisseur {
        if (empty($paiements)) {
            throw new InvalidArgumentException('Un remboursement doit comporter au moins un paiement.');
        }

        $montant = array_sum(array_column($paiements, 'montant'));
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du remboursement doit être positif.');
        }

        $moyensEspeces = MoyenPaiement::where('est_espece', true)->pluck('id')->all();
        $montantEspeces = array_sum(array_map(
            fn (array $p) => in_array($p['moyen_paiement_id'], $moyensEspeces) ? $p['montant'] : 0,
            $paiements
        ));

        return DB::transaction(function () use ($fournisseur, $auteur, $paiements, $montant, $montantEspeces) {
            $remboursement = RemboursementAvoirFournisseur::create([
                'fournisseur_id' => $fournisseur->id,
                'created_by' => $auteur->id,
                'montant' => $montant,
            ]);

            foreach ($paiements as $p) {
                $remboursement->paiements()->create([
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            // Lève AvoirFournisseurInsuffisantException si le remboursement
            // dépasse l'avoir actuel, ce qui annule toute la transaction.
            $this->compteFournisseurService->rembourserAvoir($fournisseur, $montant, $remboursement, $auteur);

            if ($montantEspeces > 0) {
                $this->compteTresorerieService->crediter(
                    $this->compteTresorerieService->caisseGenerale(),
                    $montantEspeces,
                    EcritureCompteTresorerieType::RemboursementAvoirFournisseur,
                    $auteur,
                    reference: $remboursement,
                    motif: "Remboursement avoir fournisseur {$fournisseur->nom}",
                );
            }

            return $remboursement->refresh();
        });
    }
}
