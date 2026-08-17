<?php

namespace App\Http\Controllers;

use App\Enums\MouvementStockType;
use App\Http\Controllers\Concerns\ExporteListe;
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
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RapportController extends Controller
{
    use ExporteListe;

    public function index(): View
    {
        return view('rapports.index');
    }

    public function ventes(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $requeteVentes = $this->requeteVentesFiltree($request, $debut, $fin, $magasinId);

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

    public function ventesPdf(Request $request): Response
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->pdfDepuisListe(
            'Rapport des ventes',
            ['Numéro', 'Date', 'Magasin', 'Caisse', 'Caissier', 'Total net'],
            $this->requeteVentesFiltree($request, $debut, $fin, $magasinId)->get()->map(fn (Vente $v) => [
                $v->numero,
                $v->created_at->format('d/m/Y H:i'),
                $v->magasin->nom,
                $v->sessionCaisse->caisse->nom,
                $v->caissier->name,
                number_format($v->total_net, 0, ',', ' ').' F',
            ]),
            'rapport-ventes.pdf',
            'Du '.$debut->format('d/m/Y').' au '.$fin->format('d/m/Y'),
        );
    }

    public function ventesExcel(Request $request): StreamedResponse
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->excelDepuisListe(
            'Rapport des ventes',
            ['Numéro', 'Date', 'Magasin', 'Caisse', 'Caissier', 'Total net'],
            $this->requeteVentesFiltree($request, $debut, $fin, $magasinId)->get()->map(fn (Vente $v) => [
                $v->numero,
                $v->created_at->format('d/m/Y H:i'),
                $v->magasin->nom,
                $v->sessionCaisse->caisse->nom,
                $v->caissier->name,
                $v->total_net,
            ]),
            'rapport-ventes.xlsx',
        );
    }

    private function requeteVentesFiltree(Request $request, Carbon $debut, Carbon $fin, ?int $magasinId): \Illuminate\Database\Eloquent\Builder
    {
        return Vente::query()
            ->with(['magasin', 'caissier', 'sessionCaisse.caisse'])
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->when($request->filled('caissier_id'), fn ($q) => $q->where('caissier_id', $request->integer('caissier_id')))
            ->when($request->filled('caisse_id'), fn ($q) => $q->whereHas('sessionCaisse', fn ($sc) => $sc->where('caisse_id', $request->integer('caisse_id'))))
            ->whereBetween('created_at', [$debut, $fin])
            ->orderByDesc('created_at');
    }

    public function marge(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $lignes = $this->ligneMargeFiltree($request, $debut, $fin, $magasinId);

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

    public function margePdf(Request $request): Response
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->pdfDepuisListe(
            'Rapport de marge',
            ['Produit', 'SKU', 'Pièces vendues', 'Ventes', 'Coût', 'Marge'],
            $this->ligneMargeFiltree($request, $debut, $fin, $magasinId)->map(fn ($l) => [
                $l->nom, $l->sku, $l->pieces,
                number_format($l->ventes_total, 0, ',', ' ').' F',
                number_format($l->cout_total, 0, ',', ' ').' F',
                number_format($l->marge, 0, ',', ' ').' F',
            ]),
            'rapport-marge.pdf',
            'Du '.$debut->format('d/m/Y').' au '.$fin->format('d/m/Y'),
        );
    }

    public function margeExcel(Request $request): StreamedResponse
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->excelDepuisListe(
            'Rapport de marge',
            ['Produit', 'SKU', 'Pièces vendues', 'Ventes', 'Coût', 'Marge'],
            $this->ligneMargeFiltree($request, $debut, $fin, $magasinId)->map(fn ($l) => [
                $l->nom, $l->sku, $l->pieces, $l->ventes_total, $l->cout_total, $l->marge,
            ]),
            'rapport-marge.xlsx',
        );
    }

    private function ligneMargeFiltree(Request $request, Carbon $debut, Carbon $fin, ?int $magasinId): \Illuminate\Support\Collection
    {
        return \App\Models\LigneVente::query()
            ->join('ventes', 'ventes.id', '=', 'ligne_ventes.vente_id')
            ->join('produits', 'produits.id', '=', 'ligne_ventes.produit_id')
            ->when($magasinId, fn ($q) => $q->where('ventes.magasin_id', $magasinId))
            ->whereBetween('ventes.created_at', [$debut, $fin])
            ->whereNull('ventes.deleted_at')
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
    }

    public function stock(Request $request): View
    {
        $parMagasin = $this->parMagasinFiltre($request);

        return view('rapports.stock', [
            'parMagasin' => $parMagasin,
            'valeurGlobale' => (int) $parMagasin->sum('valeur_totale'),
        ]);
    }

    public function stockPdf(Request $request): Response
    {
        return $this->pdfDepuisListe(
            'Valeur du stock',
            ['Destination', 'Quantité (pièces)', 'Valeur (CMP)'],
            $this->parMagasinFiltre($request)->map(fn ($l) => [
                $l->magasin_nom, $l->quantite_totale, number_format($l->valeur_totale, 0, ',', ' ').' F',
            ]),
            'rapport-stock.pdf',
        );
    }

    public function stockExcel(Request $request): StreamedResponse
    {
        return $this->excelDepuisListe(
            'Valeur du stock',
            ['Destination', 'Quantité (pièces)', 'Valeur (CMP)'],
            $this->parMagasinFiltre($request)->map(fn ($l) => [
                $l->magasin_nom, $l->quantite_totale, $l->valeur_totale,
            ]),
            'rapport-stock.xlsx',
        );
    }

    private function parMagasinFiltre(Request $request): \Illuminate\Support\Collection
    {
        $magasinId = $this->resoudreMagasinId($request);

        return Stock::query()
            ->join('magasins', 'magasins.id', '=', 'stocks.magasin_id')
            ->when($magasinId, fn ($q) => $q->where('stocks.magasin_id', $magasinId))
            ->selectRaw('magasins.id as magasin_id, magasins.nom as magasin_nom,
                SUM(stocks.quantite) as quantite_totale,
                SUM(stocks.quantite * stocks.cout_moyen_pondere) as valeur_totale')
            ->groupBy('magasins.id', 'magasins.nom')
            ->orderBy('magasins.nom')
            ->get();
    }

    public function ecartsCaisse(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $requeteSessions = $this->requeteSessionsFiltree($request, $debut, $fin, $magasinId);

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

    public function ecartsCaissePdf(Request $request): Response
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->pdfDepuisListe(
            'Écarts de caisse',
            ['Caisse', 'Magasin', 'Caissier', 'Clôturée le', 'Écart'],
            $this->requeteSessionsFiltree($request, $debut, $fin, $magasinId)->get()->map(fn (SessionCaisse $s) => [
                $s->caisse->nom,
                $s->caisse->magasin->nom,
                $s->caissier->name,
                $s->date_cloture->format('d/m/Y H:i'),
                number_format($s->ecart ?? 0, 0, ',', ' ').' F',
            ]),
            'rapport-ecarts-caisse.pdf',
            'Du '.$debut->format('d/m/Y').' au '.$fin->format('d/m/Y'),
        );
    }

    public function ecartsCaisseExcel(Request $request): StreamedResponse
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->excelDepuisListe(
            'Écarts de caisse',
            ['Caisse', 'Magasin', 'Caissier', 'Clôturée le', 'Écart'],
            $this->requeteSessionsFiltree($request, $debut, $fin, $magasinId)->get()->map(fn (SessionCaisse $s) => [
                $s->caisse->nom,
                $s->caisse->magasin->nom,
                $s->caissier->name,
                $s->date_cloture->format('d/m/Y H:i'),
                $s->ecart ?? 0,
            ]),
            'rapport-ecarts-caisse.xlsx',
        );
    }

    private function requeteSessionsFiltree(Request $request, Carbon $debut, Carbon $fin, ?int $magasinId): \Illuminate\Database\Eloquent\Builder
    {
        return SessionCaisse::query()
            ->with(['caisse.magasin', 'caissier'])
            ->whereNotNull('date_cloture')
            ->when($magasinId, fn ($q) => $q->whereHas('caisse', fn ($c) => $c->where('magasin_id', $magasinId)))
            ->whereBetween('date_cloture', [$debut, $fin])
            ->orderByDesc('date_cloture');
    }

    public function inventaires(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $requeteInventaires = $this->requeteInventairesFiltree($request, $debut, $fin, $magasinId);

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

    public function inventairesPdf(Request $request): Response
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->pdfDepuisListe(
            'Rapport des inventaires',
            ['Date', 'Destination', 'Statut', 'Lignes', 'Écart total'],
            $this->requeteInventairesFiltree($request, $debut, $fin, $magasinId)->get()->map(fn (Inventaire $i) => [
                $i->date->format('d/m/Y'),
                $i->magasin->nom,
                $i->statut === 'valide' ? 'Validé' : 'Brouillon',
                $i->lignes_count,
                $i->ecart_total ?? 0,
            ]),
            'rapport-inventaires.pdf',
            'Du '.$debut->format('d/m/Y').' au '.$fin->format('d/m/Y'),
        );
    }

    public function inventairesExcel(Request $request): StreamedResponse
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->excelDepuisListe(
            'Rapport des inventaires',
            ['Date', 'Destination', 'Statut', 'Lignes', 'Écart total'],
            $this->requeteInventairesFiltree($request, $debut, $fin, $magasinId)->get()->map(fn (Inventaire $i) => [
                $i->date->format('d/m/Y'),
                $i->magasin->nom,
                $i->statut === 'valide' ? 'Validé' : 'Brouillon',
                $i->lignes_count,
                $i->ecart_total ?? 0,
            ]),
            'rapport-inventaires.xlsx',
        );
    }

    private function requeteInventairesFiltree(Request $request, Carbon $debut, Carbon $fin, ?int $magasinId): \Illuminate\Database\Eloquent\Builder
    {
        return Inventaire::query()
            ->with(['magasin', 'auteur', 'validateur'])
            ->withCount('lignes')
            ->withSum('lignes as ecart_total', 'ecart')
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->orderByDesc('date');
    }

    public function casse(Request $request): View
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        $lignes = $this->ligneCasseFiltree($request, $debut, $fin, $magasinId);

        return view('rapports.casse', [
            'lignes' => $lignes,
            'totalPieces' => (int) $lignes->sum('pieces_perdues'),
            'magasins' => Magasin::orderBy('nom')->get(),
            'debut' => $debut,
            'fin' => $fin,
        ]);
    }

    public function cassePdf(Request $request): Response
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->pdfDepuisListe(
            'Casse / pertes',
            ['Produit', 'SKU', 'Magasin', 'Pièces perdues'],
            $this->ligneCasseFiltree($request, $debut, $fin, $magasinId)->map(fn ($l) => [
                $l->nom, $l->sku, $l->magasin_nom, $l->pieces_perdues,
            ]),
            'rapport-casse.pdf',
            'Du '.$debut->format('d/m/Y').' au '.$fin->format('d/m/Y'),
        );
    }

    public function casseExcel(Request $request): StreamedResponse
    {
        [$debut, $fin] = $this->periode($request);
        $magasinId = $this->resoudreMagasinId($request);

        return $this->excelDepuisListe(
            'Casse / pertes',
            ['Produit', 'SKU', 'Magasin', 'Pièces perdues'],
            $this->ligneCasseFiltree($request, $debut, $fin, $magasinId)->map(fn ($l) => [
                $l->nom, $l->sku, $l->magasin_nom, $l->pieces_perdues,
            ]),
            'rapport-casse.xlsx',
        );
    }

    private function ligneCasseFiltree(Request $request, Carbon $debut, Carbon $fin, ?int $magasinId): \Illuminate\Support\Collection
    {
        return MouvementStock::query()
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
