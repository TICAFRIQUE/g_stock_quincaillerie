<?php

namespace App\Services;

use App\Enums\EcritureCompteClientType;
use App\Exceptions\LimiteCreditDepasseeException;
use App\Exceptions\SoldeClientInsuffisantException;
use App\Models\Client;
use App\Models\EcritureCompteClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Point de passage unique pour toute variation du compte d'un client. Le
 * solde est dérivé, jamais écrasé : chaque appel insère une écriture
 * immuable — même principe que StockService pour le stock (règle 12).
 */
class CompteClientService
{
    public function solde(Client $client): int
    {
        return EcritureCompteClient::where('client_id', $client->id)->sum('montant');
    }

    /**
     * Enregistre la dette d'une vente à crédit. Verrouille le client pour
     * empêcher deux ventes à crédit concurrentes de dépasser ensemble la
     * limite (à appeler à l'intérieur de la transaction de la vente).
     */
    public function crediterDette(
        Client $client,
        int $montant,
        Model $reference,
        User $auteur,
        bool $autoriserDepassement,
    ): EcritureCompteClient {
        $client = Client::whereKey($client->id)->lockForUpdate()->firstOrFail();
        $soldeApres = $this->solde($client) + $montant;

        if ($client->limite_credit !== null && $soldeApres > $client->limite_credit && ! $autoriserDepassement) {
            throw new LimiteCreditDepasseeException($client, $soldeApres, $client->limite_credit);
        }

        return EcritureCompteClient::create([
            'client_id' => $client->id,
            'type' => EcritureCompteClientType::VenteCredit,
            'montant' => $montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }

    /**
     * Enregistre un règlement (encaissement total ou partiel d'une dette).
     * Ne peut jamais dépasser la dette actuelle du client (pas de solde
     * créditeur dans le MVP).
     */
    public function enregistrerReglement(Client $client, int $montant, Model $reference, User $auteur): EcritureCompteClient
    {
        $client = Client::whereKey($client->id)->lockForUpdate()->firstOrFail();
        $soldeActuel = $this->solde($client);

        if ($montant > $soldeActuel) {
            throw new SoldeClientInsuffisantException($client, $montant, $soldeActuel);
        }

        return EcritureCompteClient::create([
            'client_id' => $client->id,
            'type' => EcritureCompteClientType::Reglement,
            'montant' => -$montant,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $auteur->id,
        ]);
    }
}
