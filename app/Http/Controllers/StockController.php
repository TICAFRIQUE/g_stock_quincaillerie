<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Magasin;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $baseFiltree = Stock::query()
            ->join('produits', 'produits.id', '=', 'stocks.produit_id')
            ->join('magasins', 'magasins.id', '=', 'stocks.magasin_id')
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('stocks.magasin_id', $request->integer('magasin_id')));

        $kpis = [
            'totalPieces' => (clone $baseFiltree)->sum('stocks.quantite'),
            'valeurStock' => (clone $baseFiltree)->sum(DB::raw('stocks.quantite * stocks.cout_moyen_pondere')),
            'sousSeuil' => (clone $baseFiltree)->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte')->count(),
            'nbMagasins' => $request->filled('magasin_id') ? 1 : Magasin::where('actif', true)->count(),
        ];

        $query = (clone $baseFiltree)
            ->when($request->boolean('sous_seuil'), fn ($q) => $q->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte'))
            ->select('stocks.*');

        $stocks = $this->appliquerTri($query, $request, ['nom' => 'produits.nom', 'quantite' => 'stocks.quantite'], 'produits.nom')
            ->with(['produit', 'magasin'])
            ->paginate(20)
            ->withQueryString();

        return view('stock.index', [
            'stocks' => $stocks,
            'magasins' => Magasin::orderBy('nom')->get(),
            'kpis' => $kpis,
        ]);
    }
}
