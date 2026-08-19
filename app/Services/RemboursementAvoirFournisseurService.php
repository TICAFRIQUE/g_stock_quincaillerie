<?php

namespace App\Services;

use App\Enums\MouvementCaisseType;
use App\Models\Fournisseur;
use App\Models\MoyenPaiement;
use App\Models\RemboursementAvoirFournisseur;
use App\Models\SessionCaisse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rembourse (tout ou partie) l'avoir qu'un fournisseur nous doit — le
 * fournisseur nous reverse cet argent. Symétrique de
 * RemboursementAvoirClientService : la part reçue en espèces exige une
 * session ouverte et génère une ENTRÉE de caisse liée (l'argent rentre dans
 * le tiroir, contrairement au remboursement client qui en sort).
 */
class RemboursementAvoirFournisseurService
{
    public function __construct(
        private readonly CompteFournisseurService $compteFournisseurService,
        private readonly CaisseMouvementService $caisseMouvementService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function rembourser(
        Fournisseur $fournisseur,
        User $auteur,
        array $paiements,
        ?SessionCaisse $session = null,
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

        if ($montantEspeces > 0 && $session === null) {
            throw new InvalidArgumentException(
                'Une session de caisse est requise : une partie du remboursement entre en espèces dans le tiroir.'
            );
        }

        return DB::transaction(function () use ($fournisseur, $auteur, $paiements, $montant, $session, $montantEspeces) {
            $remboursement = RemboursementAvoirFournisseur::create([
                'fournisseur_id' => $fournisseur->id,
                'session_caisse_id' => $session?->id,
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
                // Peut lever SoldeCaisseInsuffisantException — annule tout.
                $this->caisseMouvementService->enregistrer(
                    session: $session,
                    type: MouvementCaisseType::Entree,
                    montant: $montantEspeces,
                    motif: "Remboursement avoir fournisseur {$fournisseur->nom}",
                    auteur: $auteur,
                    reference: $remboursement,
                );
            }

            return $remboursement->refresh();
        });
    }
}
