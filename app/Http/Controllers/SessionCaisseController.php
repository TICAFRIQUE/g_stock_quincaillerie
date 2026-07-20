<?php

namespace App\Http\Controllers;

use App\Exceptions\CaisseNonLibreException;
use App\Exceptions\CaissierDejaEnSessionException;
use App\Exceptions\VentesEnAttentePresentesException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Caisse;
use App\Models\Paiement;
use App\Models\SessionCaisse;
use App\Services\CaisseSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SessionCaisseController extends Controller
{
    use TrieListe, AutoriseMagasin;

    public function index(Request $request): View
    {
        $caisses = Caisse::query()
            ->where('actif', true)
            ->when($request->user()->magasin_id, fn ($q, $magasinId) => $q->where('magasin_id', $magasinId))
            ->with(['magasin', 'sessionCaisses' => fn ($q) => $q->whereNull('date_fermeture')->with('caissier')])
            ->withCount(['sessionCaisses as sessions_aujourdhui_count' => fn ($q) => $q->whereDate('date_ouverture', now()->toDateString())])
            ->orderBy('nom')
            ->get();

        [$caissesOccupees, $caissesLibres] = $caisses->partition(fn (Caisse $caisse) => $caisse->sessionCaisses->isNotEmpty());

        $sessionCaissierOuverte = SessionCaisse::where('caissier_id', $request->user()->id)
            ->whereNull('date_fermeture')
            ->with('caisse')
            ->first();

        return view('sessions.index', [
            'caissesOccupees' => $caissesOccupees,
            'caissesLibres' => $caissesLibres,
            'sessionCaissierOuverte' => $sessionCaissierOuverte,
        ]);
    }

    public function create(Caisse $caisse): View
    {
        $this->assurerMagasin($caisse->magasin_id);

        return view('sessions.create', ['caisse' => $caisse]);
    }

    public function store(Request $request, Caisse $caisse, CaisseSessionService $caisseSessionService): RedirectResponse
    {
        $this->assurerMagasin($caisse->magasin_id);

        $donnees = $request->validate([
            'fond_de_caisse' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $session = $caisseSessionService->ouvrir($caisse, $request->user(), $donnees['fond_de_caisse']);
        } catch (CaisseNonLibreException|CaissierDejaEnSessionException $e) {
            return redirect()->route('sessions.index')->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.show', $session)->with('succes', 'Session ouverte.');
    }

    public function show(Request $request, SessionCaisse $session): View
    {
        $this->assurerMagasin($session->caisse->magasin_id);

        $session->load(['caisse.magasin', 'caissier']);
        $session->loadCount(['ventes', 'venteEnAttentes']);

        $totalVentes = (int) $session->ventes()->sum('total_net');
        $paiementsParMoyen = $this->paiementsParMoyen($session);

        $query = $session->ventes()->getQuery()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('numero', 'like', "%{$recherche}%");
            });

        $ventes = $this->appliquerTri($query, $request, ['numero', 'created_at', 'total_net'], 'created_at')
            ->paginate(20)
            ->withQueryString();

        // Signale qu'une même caisse a été rouverte plusieurs fois le même
        // jour — la clôture reste calculée par session (jamais par jour),
        // ceci n'est qu'un lien vers le rapport pour la vue d'ensemble.
        $sessionsAujourdhui = SessionCaisse::where('caisse_id', $session->caisse_id)
            ->whereDate('date_ouverture', $session->date_ouverture->toDateString())
            ->where('id', '!=', $session->id)
            ->count();

        return view('sessions.show', [
            'session' => $session,
            'totalVentes' => $totalVentes,
            'paiementsParMoyen' => $paiementsParMoyen,
            'ventes' => $ventes,
            'sessionsAujourdhui' => $sessionsAujourdhui,
        ]);
    }

    public function cloturerForm(SessionCaisse $session): View|RedirectResponse
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        abort_if($session->date_cloture, 403, 'Cette session est déjà clôturée.');

        // Filet de sécurité côté UI : la règle est déjà appliquée par le
        // service à la validation, mais on évite de laisser le caissier
        // remplir un formulaire qui sera de toute façon rejeté.
        $nombreEnAttente = $session->venteEnAttentes()->count();
        if ($nombreEnAttente > 0) {
            return redirect()->route('sessions.show', $session)->with(
                'erreur',
                "Impossible de clôturer : {$nombreEnAttente} vente(s) en attente sur cette caisse. Finalisez-les ou annulez-les d'abord."
            );
        }

        $totalVentes = (int) $session->ventes()->sum('total_net');

        $totalEspeces = (int) Paiement::query()
            ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
            ->whereHas('moyenPaiement', fn ($q) => $q->where('est_espece', true))
            ->sum('montant');

        $paiementsParMoyen = $this->paiementsParMoyen($session);

        return view('sessions.cloturer', [
            'session' => $session,
            'theorique' => $session->fond_de_caisse + $totalEspeces,
            'totalVentes' => $totalVentes,
            'paiementsParMoyen' => $paiementsParMoyen,
        ]);
    }

    public function cloturer(Request $request, SessionCaisse $session, CaisseSessionService $caisseSessionService): RedirectResponse
    {
        $this->assurerMagasin($session->caisse->magasin_id);

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
        $this->assurerMagasin($session->caisse->magasin_id);

        try {
            $caisseSessionService->fermer($session);
        } catch (VentesEnAttentePresentesException|RuntimeException $e) {
            return redirect()->route('sessions.show', $session)->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.index')->with('succes', 'Session fermée.');
    }

    public function rapport(SessionCaisse $session): View
    {
        $this->assurerMagasin($session->caisse->magasin_id);

        $session->load(['caisse.magasin', 'caissier', 'clotureePar']);

        return view('sessions.rapport', [
            'session' => $session,
            'totalVentes' => (int) $session->ventes()->sum('total_net'),
            'paiementsParMoyen' => $this->paiementsParMoyen($session),
            'ventes' => $session->ventes()->orderBy('created_at')->get(),
        ]);
    }

    /**
     * Répartition du total encaissé par moyen de paiement (espèces, mobile
     * money…) — factorisé pour ne pas dupliquer cette agrégation entre la
     * page de session, la clôture et le rapport de caisse.
     */
    private function paiementsParMoyen(SessionCaisse $session)
    {
        return Paiement::query()
            ->whereHas('vente', fn ($q) => $q->where('session_caisse_id', $session->id))
            ->selectRaw('moyen_paiement_id, sum(montant) as total')
            ->groupBy('moyen_paiement_id')
            ->with('moyenPaiement')
            ->get();
    }
}
