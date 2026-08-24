<?php

namespace App\Services;

use App\Enums\EcritureCompteTresorerieType;
use App\Models\Client;
use App\Models\MoyenPaiement;
use App\Models\RemboursementAvoirClient;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Rembourse (tout ou partie) l'avoir d'un client — voir CLAUDE.md, section
 * Retours : un retour ne rembourse jamais directement en espèces, il crédite
 * un avoir ; ce service est l'action séparée qui vient régler cet avoir plus
 * tard. La part payée en espèces sort directement de la Caisse Générale
 * (voir CLAUDE.md, Trésorerie), jamais du tiroir d'un caissier — symétrique
 * de ReglementFournisseurService pour la part espèces d'un règlement.
 */
class RemboursementAvoirClientService
{
    public function __construct(
        private readonly CompteClientService $compteClientService,
        private readonly CompteTresorerieService $compteTresorerieService,
    ) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function rembourser(
        Client $client,
        User $auteur,
        array $paiements,
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

        return DB::transaction(function () use ($client, $auteur, $paiements, $montant, $montantEspeces) {
            $remboursement = RemboursementAvoirClient::create([
                'client_id' => $client->id,
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
                // Peut lever SoldeTresorerieInsuffisantException si la
                // Caisse Générale n'a pas assez d'espèces — annule tout.
                $this->compteTresorerieService->debiter(
                    $this->compteTresorerieService->caisseGenerale(),
                    $montantEspeces,
                    EcritureCompteTresorerieType::RemboursementAvoirClient,
                    $auteur,
                    reference: $remboursement,
                    motif: "Remboursement avoir client {$client->nom}",
                );
            }

            return $remboursement->refresh();
        });
    }
}
