<?php

namespace App\Http\Controllers;

use App\Exceptions\SessionNonOuverteException;
use App\Exceptions\SoldeClientInsuffisantException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Models\Client;
use App\Models\MoyenPaiement;
use App\Models\SessionCaisse;
use App\Services\ReglementClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class ReglementClientController extends Controller
{
    use AutoriseMagasin;

    public function create(SessionCaisse $session): View
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        abort_if($session->date_cloture || $session->date_fermeture, 403, 'Cette session n\'est plus ouverte.');

        return view('reglements.create', [
            'session' => $session,
            'clients' => Client::actifsAvecDette(),
            'moyensPaiement' => MoyenPaiement::actifs(),
        ]);
    }

    public function store(Request $request, SessionCaisse $session, ReglementClientService $reglementService): RedirectResponse
    {
        $this->assurerMagasin($session->caisse->magasin_id);

        $donnees = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        $client = Client::findOrFail($donnees['client_id']);

        try {
            $reglementService->encaisser(
                session: $session,
                caissier: $request->user(),
                client: $client,
                paiements: $donnees['paiements'],
            );
        } catch (SoldeClientInsuffisantException|SessionNonOuverteException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.show', $session)->with('succes', 'Règlement enregistré.');
    }
}
