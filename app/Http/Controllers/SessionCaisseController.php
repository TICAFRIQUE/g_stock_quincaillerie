<?php

namespace App\Http\Controllers;

use App\Exceptions\CaisseNonLibreException;
use App\Exceptions\VentesEnAttentePresentesException;
use App\Models\Caisse;
use App\Models\SessionCaisse;
use App\Services\CaisseSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SessionCaisseController extends Controller
{
    public function index(Request $request): View
    {
        $caisses = Caisse::query()
            ->where('actif', true)
            ->when($request->user()->magasin_id, fn ($q, $magasinId) => $q->where('magasin_id', $magasinId))
            ->with(['magasin', 'sessionCaisses' => fn ($q) => $q->whereNull('date_fermeture')->with('caissier')])
            ->orderBy('nom')
            ->get();

        [$caissesOccupees, $caissesLibres] = $caisses->partition(fn (Caisse $caisse) => $caisse->sessionCaisses->isNotEmpty());

        return view('sessions.index', [
            'caissesOccupees' => $caissesOccupees,
            'caissesLibres' => $caissesLibres,
        ]);
    }

    public function create(Caisse $caisse): View
    {
        return view('sessions.create', ['caisse' => $caisse]);
    }

    public function store(Request $request, Caisse $caisse, CaisseSessionService $caisseSessionService): RedirectResponse
    {
        $donnees = $request->validate([
            'fond_de_caisse' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $session = $caisseSessionService->ouvrir($caisse, $request->user(), $donnees['fond_de_caisse']);
        } catch (CaisseNonLibreException $e) {
            return redirect()->route('sessions.index')->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.show', $session)->with('succes', 'Session ouverte.');
    }

    public function show(SessionCaisse $session): View
    {
        $session->load(['caisse.magasin', 'caissier']);
        $session->loadCount(['ventes', 'venteEnAttentes']);
        $session->load(['ventes' => fn ($q) => $q->latest('created_at')->limit(10)]);

        $totalVentes = $session->ventes()->sum('total_net');
        $totalEspeces = \App\Models\Paiement::query()
            ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
            ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
            ->sum('montant');

        return view('sessions.show', [
            'session' => $session,
            'totalVentes' => (int) $totalVentes,
            'totalEspeces' => (int) $totalEspeces,
        ]);
    }

    public function cloturerForm(SessionCaisse $session): View
    {
        $totalEspeces = \App\Models\Paiement::query()
            ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
            ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
            ->sum('montant');

        return view('sessions.cloturer', [
            'session' => $session,
            'theorique' => $session->fond_de_caisse + (int) $totalEspeces,
        ]);
    }

    public function cloturer(Request $request, SessionCaisse $session, CaisseSessionService $caisseSessionService): RedirectResponse
    {
        $donnees = $request->validate([
            'montant_compte' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $caisseSessionService->cloturer($session, $donnees['montant_compte'], $request->user());
        } catch (VentesEnAttentePresentesException|RuntimeException $e) {
            return redirect()->route('sessions.show', $session)->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.show', $session)->with('succes', 'Session clôturée.');
    }

    public function fermer(Request $request, SessionCaisse $session, CaisseSessionService $caisseSessionService): RedirectResponse
    {
        try {
            $caisseSessionService->fermer($session);
        } catch (VentesEnAttentePresentesException|RuntimeException $e) {
            return redirect()->route('sessions.show', $session)->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.index')->with('succes', 'Session fermée.');
    }
}
