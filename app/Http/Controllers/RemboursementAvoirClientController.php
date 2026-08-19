<?php

namespace App\Http\Controllers;

use App\Exceptions\AvoirClientInsuffisantException;
use App\Exceptions\SoldeCaisseInsuffisantException;
use App\Models\Client;
use App\Models\SessionCaisse;
use App\Services\RemboursementAvoirClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Règle un avoir client (solde négatif — voir CLAUDE.md, section Retours) :
 * action distincte du retour lui-même, qui ne rembourse jamais directement
 * en espèces. Une session de caisse n'est exigée que si une partie sort en
 * espèces (voir RemboursementAvoirClientService), même logique que
 * ReglementFournisseurController.
 */
class RemboursementAvoirClientController extends Controller
{
    public function store(Request $request, Client $client, RemboursementAvoirClientService $service): RedirectResponse
    {
        $donnees = $request->validate([
            'session_caisse_id' => ['nullable', Rule::exists('session_caisses', 'id')->whereNull('date_cloture')->whereNull('date_fermeture')],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        $session = $donnees['session_caisse_id'] ?? null
            ? SessionCaisse::find($donnees['session_caisse_id'])
            : null;

        try {
            $service->rembourser(
                client: $client,
                auteur: $request->user(),
                paiements: $donnees['paiements'],
                session: $session,
            );
        } catch (AvoirClientInsuffisantException|SoldeCaisseInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Avoir remboursé.');
    }
}
