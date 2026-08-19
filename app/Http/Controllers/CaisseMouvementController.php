<?php

namespace App\Http\Controllers;

use App\Enums\MouvementCaisseType;
use App\Exceptions\SoldeCaisseInsuffisantException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Http\Controllers\Concerns\JournalCaisse;
use App\Models\MouvementCaisse;
use App\Models\Paiement;
use App\Models\ReglementPaiement;
use App\Models\SessionCaisse;
use App\Models\Vente;
use App\Services\CaisseMouvementService;
use App\Services\CaisseSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

/**
 * Onglet "Caisse", séparé de la vente (voir CLAUDE.md, Mouvements de caisse) :
 * gérer les entrées/sorties d'un tiroir est une tâche distincte de vendre,
 * même si les deux s'appuient sur la même session ouverte. On "se connecte"
 * à une caisse ouverte ici (index → show), sans jamais pouvoir en ouvrir une
 * nouvelle depuis cet onglet — l'ouverture reste un geste de vente (règle 6).
 *
 * Un caissier ne voit et ne gère QUE sa propre session ouverte, jamais celle
 * d'un collègue — même principe que les ventes en attente (voir
 * AutoriseVenteEnAttente) : la permission caisse.gerer (que le Gérant/
 * Superadmin possède toujours) est seule habilitée à voir/agir sur toutes
 * les caisses du périmètre.
 */
class CaisseMouvementController extends Controller
{
    use AutoriseMagasin, JournalCaisse;

    public function index(Request $request, CaisseSessionService $caisseSessionService): View
    {
        $magasinId = $request->user()->magasin_id;
        $voitTout = $request->user()->can('caisse.gerer');

        $sessions = SessionCaisse::whereNull('date_fermeture')
            ->whereNull('date_cloture')
            ->when($magasinId, fn ($q) => $q->whereHas('caisse', fn ($qc) => $qc->where('magasin_id', $magasinId)))
            ->when(! $voitTout, fn ($q) => $q->where('caissier_id', $request->user()->id))
            ->with('caisse.magasin', 'caissier')
            ->get();

        // Solde théorique par caisse ouverte, calculé en direct (pas de
        // colonne stockée tant que la session n'est pas clôturée) — même
        // formule que la clôture, voir CaisseSessionService::calculerTheorique().
        $soldesTheoriques = $sessions->mapWithKeys(
            fn (SessionCaisse $session) => [$session->id => $caisseSessionService->calculerTheorique($session)['theorique']]
        );

        // Entrées/sorties du jour — scopées aux MÊMES caisses que la liste
        // ci-dessus (magasin pour caisse.gerer, sa propre caisse sinon) :
        // jamais un chiffre agrégé qui fuiterait l'activité des collègues
        // d'un caissier qui n'a pas le droit de les voir. pluck() sur le
        // Builder reste en requête SQL brute, jamais hydraté en modèle/enum
        // (voir DashboardController, même piège déjà rencontré).
        $mouvementsAujourdhui = MouvementCaisse::query()
            ->join('session_caisses', 'session_caisses.id', '=', 'mouvement_caisses.session_caisse_id')
            ->join('caisses', 'caisses.id', '=', 'session_caisses.caisse_id')
            ->when($magasinId, fn ($q) => $q->where('caisses.magasin_id', $magasinId))
            ->when(! $voitTout, fn ($q) => $q->where('session_caisses.caissier_id', $request->user()->id))
            ->whereDate('mouvement_caisses.created_at', now()->toDateString())
            ->selectRaw('mouvement_caisses.type as type, SUM(mouvement_caisses.montant) as total')
            ->groupBy('mouvement_caisses.type')
            ->pluck('total', 'type');

        return view('caisse.index', [
            'sessions' => $sessions,
            'voitTout' => $voitTout,
            'soldesTheoriques' => $soldesTheoriques,
            'soldeTotal' => $soldesTheoriques->sum(),
            'totalEntrees' => (int) ($mouvementsAujourdhui['entree'] ?? 0),
            'totalSorties' => (int) ($mouvementsAujourdhui['sortie'] ?? 0),
        ] + $this->venteKpisAujourdhui($magasinId, $voitTout, $request->user()->id));
    }

    /**
     * Ventes du jour, dans le même périmètre que les mouvements de caisse
     * ci-dessus (magasin pour caisse.gerer, sa propre caisse sinon) — quatre
     * chiffres volontairement séparés du bloc "mouvements de caisse" : le
     * total ventes/dû/avoir n'entrent jamais dans le comptage du tiroir
     * (règle 10), seul le dernier (espèces) l'alimente réellement.
     */
    private function venteKpisAujourdhui(?int $magasinId, bool $voitTout, int $utilisateurId): array
    {
        $ventesAujourdhui = Vente::query()
            ->with('paiements', 'reglementsClient')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->when(! $voitTout, fn ($q) => $q->whereHas('sessionCaisse', fn ($sc) => $sc->where('caissier_id', $utilisateurId)))
            ->whereDate('created_at', now()->toDateString())
            ->get();

        $especesVente = (int) Paiement::query()
            ->join('ventes', 'ventes.id', '=', 'paiements.vente_id')
            ->join('moyen_paiements', 'moyen_paiements.id', '=', 'paiements.moyen_paiement_id')
            ->join('session_caisses', 'session_caisses.id', '=', 'ventes.session_caisse_id')
            ->where('moyen_paiements.est_espece', true)
            ->when($magasinId, fn ($q) => $q->where('ventes.magasin_id', $magasinId))
            ->when(! $voitTout, fn ($q) => $q->where('session_caisses.caissier_id', $utilisateurId))
            ->whereDate('ventes.created_at', now()->toDateString())
            ->whereNull('ventes.deleted_at')
            ->sum('paiements.montant');

        $especesReglement = (int) ReglementPaiement::query()
            ->join('reglement_clients', 'reglement_clients.id', '=', 'reglement_paiements.reglement_client_id')
            ->join('session_caisses', 'session_caisses.id', '=', 'reglement_clients.session_caisse_id')
            ->join('caisses', 'caisses.id', '=', 'session_caisses.caisse_id')
            ->join('moyen_paiements', 'moyen_paiements.id', '=', 'reglement_paiements.moyen_paiement_id')
            ->where('moyen_paiements.est_espece', true)
            ->when($magasinId, fn ($q) => $q->where('caisses.magasin_id', $magasinId))
            ->when(! $voitTout, fn ($q) => $q->where('session_caisses.caissier_id', $utilisateurId))
            ->whereDate('reglement_clients.created_at', now()->toDateString())
            ->sum('reglement_paiements.montant');

        return [
            'totalVentesAujourdhui' => (int) $ventesAujourdhui->sum('total_net'),
            'totalDuAujourdhui' => (int) $ventesAujourdhui->sum(fn (Vente $v) => $v->soldeDuReel()),
            'avoirAppliqueAujourdhui' => (int) $ventesAujourdhui->sum('avoir_applique'),
            'totalEspecesAujourdhui' => $especesVente + $especesReglement,
        ];
    }

    public function show(Request $request, SessionCaisse $session, CaisseSessionService $caisseSessionService): View
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        $this->assurerProprietaireOuGerant($session);

        $session->load(['caisse.magasin', 'caissier']);

        $detail = $session->date_cloture === null ? $caisseSessionService->calculerTheorique($session) : null;

        // Journal de cette caisse : mouvements manuels/générés ET ventes
        // encaissées en espèces (voir JournalCaisse) — une vente n'est pas
        // un MouvementCaisse (règle 19), mais reste une entrée d'argent que
        // le caissier doit voir ici pour comprendre son tiroir.
        $journal = $this->requeteJournalCaisse(
            $session->date_ouverture,
            $session->date_cloture ?? $session->date_fermeture ?? now(),
            sessionCaisseId: $session->id,
        )->get()->map(fn ($l) => $this->decorerLigneJournal($l));

        return view('caisse.show', [
            'session' => $session,
            'journal' => $journal,
            'soldeTheorique' => $detail['theorique'] ?? null,
            'totalEntrees' => $detail['entrees'] ?? $session->total_entrees_especes,
            'totalSorties' => $detail['sorties'] ?? $session->total_sorties_especes,
        ]);
    }

    public function store(Request $request, SessionCaisse $session, CaisseMouvementService $mouvementService): RedirectResponse
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        $this->assurerProprietaireOuGerant($session);

        $donnees = $request->validate([
            'type' => ['required', Rule::in(['entree', 'sortie'])],
            'montant' => ['required', 'integer', 'min:1'],
            'motif' => ['required', 'string', 'max:255'],
        ]);

        try {
            $mouvementService->enregistrer(
                session: $session,
                type: MouvementCaisseType::from($donnees['type']),
                montant: $donnees['montant'],
                motif: $donnees['motif'],
                auteur: $request->user(),
            );
        } catch (SoldeCaisseInsuffisantException|InvalidArgumentException|RuntimeException $e) {
            return redirect()->route('caisse.show', $session)->with('erreur', $e->getMessage());
        }

        return redirect()->route('caisse.show', $session)->with('succes', 'Mouvement de caisse enregistré.');
    }

    /**
     * Bloque explicitement l'accès direct par URL à la session d'un autre
     * caissier : sans ce garde-fou, filtrer la liste de index() ne suffit
     * pas (rien n'empêcherait de deviner/rejouer l'URL d'une autre session).
     * Même principe que AutoriseVenteEnAttente::assurerProprietaireOuGerant().
     */
    private function assurerProprietaireOuGerant(SessionCaisse $session): void
    {
        abort_if(
            $session->caissier_id !== request()->user()->id && ! request()->user()->can('caisse.gerer'),
            403,
            'Cette caisse est tenue par un autre caissier.'
        );
    }
}
