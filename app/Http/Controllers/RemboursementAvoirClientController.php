<?php

namespace App\Http\Controllers;

use App\Exceptions\AvoirClientInsuffisantException;
use App\Exceptions\SoldeTresorerieInsuffisantException;
use App\Models\Client;
use App\Services\RemboursementAvoirClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Règle un avoir client (solde négatif — voir CLAUDE.md, section Retours) :
 * action distincte du retour lui-même, qui ne rembourse jamais directement
 * en espèces. Aucun rattachement à une session de caisse : la part payée en
 * espèces sort de la Caisse Générale (voir CLAUDE.md, Trésorerie), pas du
 * tiroir d'un caissier.
 */
class RemboursementAvoirClientController extends Controller
{
    public function store(Request $request, Client $client, RemboursementAvoirClientService $service): RedirectResponse
    {
        $donnees = $request->validate([
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $service->rembourser(
                client: $client,
                auteur: $request->user(),
                paiements: $donnees['paiements'],
            );
        } catch (AvoirClientInsuffisantException|SoldeTresorerieInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Avoir remboursé.');
    }
}
