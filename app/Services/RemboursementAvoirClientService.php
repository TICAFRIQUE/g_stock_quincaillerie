<?php

namespace App\Services;

use App\Enums\MouvementCaisseType;
use App\Models\Client;
use App\Models\MoyenPaiement;
use App\Models\RemboursementAvoirClient;
use App\Models\SessionCaisse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rembourse (tout ou partie) l'avoir d'un client — voir CLAUDE.md, section
 * Retours : un retour ne rembourse jamais directement en espèces, il crédite
 * un avoir ; ce service est l'action séparée qui vient régler cet avoir plus
 * tard. La part payée en espèces exige une session ouverte et génère une
 * sortie de caisse liée (symétrique de ReglementFournisseurService pour la
 * part espèces d'un règlement).
 */
class RemboursementAvoirClientService
{
    public function __construct(
        private readonly CompteClientService $compteClientService,
        private readonly CaisseMouvementService $caisseMouvementService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function rembourser(
        Client $client,
        User $auteur,
        array $paiements,
        ?SessionCaisse $session = null,
    ): RemboursementAvoirClient {
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
                'Une session de caisse est requise : une partie du remboursement sort en espèces du tiroir.'
            );
        }

        return DB::transaction(function () use ($client, $auteur, $paiements, $montant, $session, $montantEspeces) {
            $remboursement = RemboursementAvoirClient::create([
                'client_id' => $client->id,
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

            // Lève AvoirClientInsuffisantException si le remboursement
            // dépasse l'avoir actuel, ce qui annule toute la transaction.
            $this->compteClientService->rembourserAvoir($client, $montant, $remboursement, $auteur);

            if ($montantEspeces > 0) {
                // Peut lever SoldeCaisseInsuffisantException si le tiroir
                // n'a théoriquement pas assez d'espèces — annule tout.
                $this->caisseMouvementService->enregistrer(
                    session: $session,
                    type: MouvementCaisseType::Sortie,
                    montant: $montantEspeces,
                    motif: "Remboursement avoir client {$client->nom}",
                    auteur: $auteur,
                    reference: $remboursement,
                );
            }

            return $remboursement->refresh();
        });
    }
}
