<?php

namespace App\Http\Controllers;

use App\Exceptions\SessionNonOuverteException;
use App\Exceptions\SoldeClientInsuffisantException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Models\Client;
use App\Models\MoyenPaiement;
use App\Models\SessionCaisse;
use App\Models\Vente;
use App\Services\ReglementClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class ReglementClientController extends Controller
{
    use AutoriseMagasin;

    public function create(SessionCaisse $session): View
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        abort_if($session->date_cloture || $session->date_fermeture, 403, 'Cette session n\'est plus ouverte.');

        // Chaque facture encore ouverte (reste dû réel > 0) est chargée avec
        // ses paiements/règlements pour permettre un règlement ciblé par
        // facture directement depuis cet écran — sans ça, un règlement saisi
        // ici n'était imputé à aucune vente précise et son montant réglé/
        // reste dû par facture ne bougeait jamais (voir CLAUDE.md règle 14,
        // Vente::montantRegle()/soldeDuReel()).
        $clients = Client::actifsAvecDette();
        $clients->load(['ventes.paiements', 'ventes.reglementsClient']);
        $clients->each(function (Client $client) {
            $client->facturesOuvertes = $client->ventes
                ->filter(fn (Vente $v) => $v->soldeDuReel() > 0)
                ->sortBy('created_at')
                ->values();
        });

        return view('reglements.create', [
            'session' => $session,
            'clients' => $clients,
            'moyensPaiement' => MoyenPaiement::actifs(),
        ]);
    }

    public function store(Request $request, SessionCaisse $session, ReglementClientService $reglementService): RedirectResponse
    {
        $this->assurerMagasin($session->caisse->magasin_id);

        $donnees = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'vente_id' => ['nullable', Rule::exists('ventes', 'id')->where('client_id', $request->input('client_id'))],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        $client = Client::findOrFail($donnees['client_id']);
        $vente = $donnees['vente_id'] ?? null
            ? Vente::withTrashed()->find($donnees['vente_id'])
            : null;

        // Un règlement imputé à une vente précise ne peut pas dépasser le
        // reste dû de CETTE vente (indépendamment de la dette totale du
        // client, qui peut inclure d'autres ventes) — même principe que
        // ReglementFournisseurController::store().
        if ($vente) {
            $vente->loadMissing('paiements', 'reglementsClient');
            $montantTotal = array_sum(array_column($donnees['paiements'], 'montant'));
            if ($montantTotal > $vente->soldeDuReel()) {
                return back()->withInput()->with('erreur', 'Le montant dépasse le reste dû sur cette vente ('.number_format($vente->soldeDuReel(), 0, ',', ' ').' F).');
            }
        }

        try {
            $reglementService->encaisser(
                session: $session,
                caissier: $request->user(),
                client: $client,
                paiements: $donnees['paiements'],
                vente: $vente,
            );
        } catch (SoldeClientInsuffisantException|SessionNonOuverteException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        // back() plutôt qu'une route fixe : le formulaire est soumis depuis
        // la fiche client (modale) OU depuis le détail d'une vente précise
        // (carte règlement) — on reste sur la page d'origine (voir
        // ReglementFournisseurController::store()).
        return back()->with('succes', 'Règlement enregistré.');
    }
}
