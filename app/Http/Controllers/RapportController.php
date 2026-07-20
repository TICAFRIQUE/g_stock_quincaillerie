<?php

namespace App\Http\Controllers;

use App\Enums\MouvementStockType;
use App\Models\Caisse;
use App\Models\Inventaire;
use App\Models\Magasin;
use App\Models\MouvementStock;
use App\Models\SessionCaisse;
use App\Models\Stock;
use App\Models\User;
use App\Models\Vente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RapportController extends Controller
{
    public function index(): View
    {
        return view('rapports.index');
    }

    public function ventes(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $requeteVentes = Vente::query()
            ->with(['magasin', 'caissier', 'sessionCaisse.caisse'])
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->when($request->filled('caissier_id'), fn ($q) => $q->where('caissier_id', $request->integer('caissier_id')))
            ->when($request->filled('caisse_id'), fn ($q) => $q->whereHas('sessionCaisse', fn ($sc) => $sc->where('caisse_id', $request->integer('caisse_id'))))
            ->whereBetween('created_at', [$debut, $fin])
            ->orderByDesc('created_at');

        // L'impression doit couvrir tout le résultat filtré, pas seulement la
        // page affichée à l'écran — reste borné par la période, jamais un
        // scan de table complet (voir CLAUDE.md, pagination).
        $ventes = $request->boolean('tout')
            ? $requeteVentes->get()
            : $requeteVentes->paginate(25)->withQueryString();

        $requeteTotaux = Vente::query()
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->when($request->filled('caissier_id'), fn ($q) => $q->where('caissier_id', $request->integer('caissier_id')))
            ->when($request->filled('caisse_id'), fn ($q) => $q->whereHas('sessionCaisse', fn ($sc) => $sc->where('caisse_id', $request->integer('caisse_id'))))
            ->whereBetween('created_at', [$debut, $fin]);

        return view('rapports.ventes', [
            'ventes' => $ventes,
            'totalNet' => (int) (clone $requeteTotaux)->sum('total_net'),
            'nombre' => (clone $requeteTotaux)->count(),
            'magasins' => Magasin::orderBy('nom')->get(),
            'caissiers' => User::when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))->orderBy('name')->get(),
            'caisses' => Caisse::when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))->orderBy('nom')->get(),
            'debut' => $debut,
            'fin' => $fin,
        ]);
    }

    public function marge(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $lignes = \App\Models\LigneVente::query()
            ->join('ventes', 'ventes.id', '=', 'ligne_ventes.vente_id')
            ->join('produits', 'produits.id', '=', 'ligne_ventes.produit_id')
            ->when($magasinId, fn ($q) => $q->where('ventes.magasin_id', $magasinId))
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->selectRaw('produits.nom as nom, produits.sku as sku,
                SUM(ligne_ventes.quantite_pieces) as pieces,
                SUM(ligne_ventes.total_ligne) as ventes_total,
                SUM(ligne_ventes.cout_applique * ligne_ventes.quantite_pieces) as cout_total')
            ->groupBy('produits.id', 'produits.nom', 'produits.sku')
            ->orderByDesc('ventes_total')
            ->get()
            ->map(function ($ligne) {
                $ligne->marge = $ligne->ventes_total - $ligne->cout_total;

                return $ligne;
            });

        return view('rapports.marge', [
            'lignes' => $lignes,
            'totalVentes' => (int) $lignes->sum('ventes_total'),
            'totalCout' => (int) $lignes->sum('cout_total'),
            'totalMarge' => (int) $lignes->sum('marge'),
            'magasins' => Magasin::orderBy('nom')->get(),
            'debut' => $debut,
            'fin' => $fin,
        ]);
    }

    public function stock(Request $request): View
    {
        $magasinId = $this->resoudreMagasinId($request);

        $parMagasin = Stock::query()
            ->join('magasins', 'magasins.id', '=', 'stocks.magasin_id')
            ->when($magasinId, fn ($q) => $q->where('stocks.magasin_id', $magasinId))
            ->selectRaw('magasins.id as magasin_id, magasins.nom as magasin_nom,
                SUM(stocks.quantite) as quantite_totale,
                SUM(stocks.quantite * stocks.cout_moyen_pondere) as valeur_totale')
            ->groupBy('magasins.id', 'magasins.nom')
            ->orderBy('magasins.nom')
            ->get();

        return view('rapports.stock', [
            'parMagasin' => $parMagasin,
            'valeurGlobale' => (int) $parMagasin->sum('valeur_totale'),
        ]);
    }

    public function ecartsCaisse(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $requeteSessions = SessionCaisse::query()
            ->with(['caisse.magasin', 'caissier'])
            ->whereNotNull('date_cloture')
            ->when($magasinId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('magasin_id', $magasinId)))
            ->whereBetween('date_cloture', [$debut, $fin])
            ->orderByDesc('date_cloture');

        $sessions = $request->boolean('tout')
            ? $requeteSessions->get()
            : $requeteSessions->paginate(25)->withQueryString();

        return view('rapports.ecarts-caisse', [
            'sessions' => $sessions,
            'magasins' => Magasin::orderBy('nom')->get(),
            'debut' => $debut,
            'fin' => $fin,
        ]);
    }

    public function inventaires(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $requeteInventaires = Inventaire::query()
            ->with(['magasin', 'auteur', 'validateur'])
            ->withCount('lignes')
            ->withSum('lignes as ecart_total', 'ecart')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->orderByDesc('date');

        $inventaires = $request->boolean('tout')
            ? $requeteInventaires->get()
            : $requeteInventaires->paginate(25)->withQueryString();

        return view('rapports.inventaires', [
            'inventaires' => $inventaires,
            'magasins' => Magasin::orderBy('nom')->get(),
            'debut' => $debut,
            'fin' => $fin,
        ]);
    }

    public function casse(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $lignes = MouvementStock::query()
            ->join('produits', 'produits.id', '=', 'mouvement_stocks.produit_id')
            ->join('magasins', 'magasins.id', '=', 'mouvement_stocks.magasin_id')
            ->where('mouvement_stocks.type', MouvementStockType::Casse->value)
            ->when($magasinId, fn ($q) => $q->where('mouvement_stocks.magasin_id', $magasinId))
            ->whereBetween('mouvement_stocks.created_at', [$debut, $fin])
            ->selectRaw('produits.nom as nom, produits.sku as sku, magasins.nom as magasin_nom,
                SUM(-mouvement_stocks.quantite) as pieces_perdues')
            ->groupBy('produits.id', 'produits.nom', 'produits.sku', 'magasins.id', 'magasins.nom')
            ->orderByDesc('pieces_perdues')
            ->get();

        return view('rapports.casse', [
            'lignes' => $lignes,
            'totalPieces' => (int) $lignes->sum('pieces_perdues'),
            'magasins' => Magasin::orderBy('nom')->get(),
            'debut' => $debut,
            'fin' => $fin,
        ]);
    }

    /**
     * Un utilisateur rattaché à un magasin (gérant, caissier) ne doit voir
     * que les données de son propre magasin dans les rapports, même en
     * manipulant le filtre — contrairement au tableau de bord (déjà scopé),
     * les rapports laissaient jusqu'ici n'importe quel magasin_id passer.
     * Un superadmin (sans magasin_id) garde le filtre libre, "Tous les
     * magasins" compris.
     */
    private function resoudreMagasinId(Request $request): ?int
    {
        return $request->user()->magasin_id ?: ($request->integer('magasin_id') ?: null);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periode(Request $request): array
    {
        $debut = $request->filled('debut')
            ? Carbon::parse($request->string('debut'))->startOfDay()
            : Carbon::now()->startOfMonth();

        $fin = $request->filled('fin')
            ? Carbon::parse($request->string('fin'))->endOfDay()
            : Carbon::now()->endOfDay();

        return [$debut, $fin];
    }
}
