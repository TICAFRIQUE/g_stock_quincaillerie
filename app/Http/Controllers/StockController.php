<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Magasin;
use App\Models\Produit;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $baseFiltree = $this->requeteFiltree($request);

        $kpis = [
            'totalPieces' => (clone $baseFiltree)->sum('stocks.quantite'),
            'valeurStock' => (clone $baseFiltree)->sum(DB::raw('stocks.quantite * stocks.cout_moyen_pondere')),
            'sousSeuil' => (clone $baseFiltree)->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte')->count(),
            'nbMagasins' => $request->filled('magasin_id') ? 1 : Magasin::where('actif', true)->count(),
        ];

        $query = (clone $baseFiltree)
            ->when($request->boolean('sous_seuil'), fn ($q) => $q->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte'))
            ->select('stocks.*');

        // Ordre alphabétique par défaut (le tri par défaut du trait est
        // habituellement décroissant, pensé pour "created_at" — ici on veut
        // explicitement A→Z tant que l'utilisateur n'a pas cliqué une colonne.
        $query = $this->appliquerTri($query, $request, ['nom' => 'produits.nom', 'quantite' => 'stocks.quantite'], 'produits.nom', 'asc')
            ->with(['produit', 'magasin']);

        // L'impression (voir x-bouton-imprimer) couvre tout le résultat
        // filtré, pas seulement la page affichée à l'écran.
        $stocks = $request->boolean('tout') ? $query->get() : $query->paginate(20)->withQueryString();

        return view('stock.index', [
            'stocks' => $stocks,
            'magasins' => Magasin::orderBy('nom')->get(),
            'produits' => Produit::where('actif', true)->orderBy('nom')->get(['id', 'sku', 'nom', 'libelle_distinctif']),
            'kpis' => $kpis,
        ]);
    }

    public function pdf(Request $request): Response
    {
        $stocks = $this->ligneExport($request);

        $pdf = Pdf::loadView('stock.pdf', ['stocks' => $stocks, 'filtres' => $this->libellesFiltres($request)]);

        return $pdf->download('etat-du-stock.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        $stocks = $this->ligneExport($request);

        $spreadsheet = new Spreadsheet();
        $feuille = $spreadsheet->getActiveSheet();
        $feuille->setTitle('État du stock');
        $feuille->fromArray(['Produit', 'SKU', 'Destination', 'Quantité', 'Seuil d\'alerte', 'Coût moyen pondéré'], null, 'A1');

        $ligne = 2;
        foreach ($stocks as $stock) {
            $feuille->setCellValue("A{$ligne}", $stock->produit->libelle_affichage);
            $feuille->setCellValue("B{$ligne}", $stock->produit->sku);
            $feuille->setCellValue("C{$ligne}", $stock->magasin->nom);
            $feuille->setCellValue("D{$ligne}", $stock->quantite);
            $feuille->setCellValue("E{$ligne}", $stock->produit->seuil_alerte);
            $feuille->setCellValue("F{$ligne}", $stock->cout_moyen_pondere);
            $ligne++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $colonne) {
            $feuille->getColumnDimension($colonne)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'etat-du-stock.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function requeteFiltree(Request $request): Builder
    {
        return Stock::query()
            ->join('produits', 'produits.id', '=', 'stocks.produit_id')
            ->join('magasins', 'magasins.id', '=', 'stocks.magasin_id')
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('stocks.magasin_id', $request->integer('magasin_id')))
            ->when($request->filled('produit_id'), fn ($q) => $q->where('stocks.produit_id', $request->integer('produit_id')));
    }

    /**
     * Même filtre que l'écran (magasin, produit, sous seuil), mais sans
     * pagination et toujours par ordre alphabétique — utilisé par les deux
     * exports (règle CLAUDE.md : les rapports ne dupliquent pas la logique
     * métier, ils réutilisent la même requête filtrée que l'écran).
     */
    private function ligneExport(Request $request)
    {
        return $this->requeteFiltree($request)
            ->when($request->boolean('sous_seuil'), fn ($q) => $q->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte'))
            ->select('stocks.*')
            ->orderBy('produits.nom')
            ->with(['produit', 'magasin'])
            ->get();
    }

    private function libellesFiltres(Request $request): array
    {
        return [
            'magasin' => $request->filled('magasin_id') ? Magasin::find($request->integer('magasin_id'))?->nom : null,
            'produit' => $request->filled('produit_id') ? Produit::find($request->integer('produit_id'))?->libelle_affichage : null,
            'sousSeuil' => $request->boolean('sous_seuil'),
        ];
    }
}
