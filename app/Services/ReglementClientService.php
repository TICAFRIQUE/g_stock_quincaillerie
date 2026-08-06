<?php

namespace App\Services;

use App\Exceptions\SessionNonOuverteException;
use App\Models\Client;
use App\Models\ReglementClient;
use App\Models\SessionCaisse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Encaissement d'une dette client, immuable comme une vente, dans une
 * session de caisse ouverte (règle 14) : les espèces reçues comptent dans le
 * tiroir au même titre qu'une vente (voir CaisseSessionService::cloturer).
 */
class ReglementClientService
{
    public function __construct(private readonly CompteClientService $compteClientService) {}

    /**
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function encaisser(
        SessionCaisse $session,
        User $caissier,
        Client $client,
        array $paiements,
    ): ReglementClient {
        if (empty($paiements)) {
            throw new InvalidArgumentException('Un règlement doit comporter au moins un paiement.');
        }

        $session = $session->fresh();
        if ($session->date_cloture !== null || $session->date_fermeture !== null) {
            throw new SessionNonOuverteException();
        }

        $montant = array_sum(array_column($paiements, 'montant'));
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant du règlement doit être positif.');
        }

        return DB::transaction(function () use ($session, $caissier, $client, $paiements, $montant) {
            $reglement = ReglementClient::create([
                'client_id' => $client->id,
                'session_caisse_id' => $session->id,
                'caissier_id' => $caissier->id,
                'montant' => $montant,
            ]);

            foreach ($paiements as $p) {
                $reglement->paiements()->create([
                    'moyen_paiement_id' => $p['moyen_paiement_id'],
                    'montant' => $p['montant'],
                ]);
            }

            // Lève SoldeClientInsuffisantException si le règlement dépasse la
            // dette actuelle, ce qui annule toute la transaction.
            $this->compteClientService->enregistrerReglement($client, $montant, $reglement, $caissier);

            return $reglement->refresh();
        });
    }
}
