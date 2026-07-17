<?php

namespace App\Http\Controllers;

use App\Exceptions\StockInsuffisantException;
use App\Models\Magasin;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\Transfert;
use App\Services\TransfertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransfertController extends Controller
{
    public function index(): View
    {
        $transferts = Transfert::query()
            ->with(['produit', 'magasinSource', 'magasinDestination', 'auteur'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('transferts.index', ['transferts' => $transferts]);
    }

    public function create(Request $request): View
    {
        $magasinSourceId = $request->integer('magasin_source_id') ?: null;

        $produits = collect();

        if ($magasinSourceId) {
            $stocksParProduit = Stock::where('magasin_id', $magasinSourceId)->where('quantite', '>', 0)->pluck('quantite', 'produit_id');

            $produits = Produit::where('actif', true)
                ->whereIn('id', $stocksParProduit->keys())
                ->orderBy('nom')
                ->get(['id', 'sku', 'nom', 'libelle_distinctif'])
                ->each(fn (Produit $p) => $p->stock_magasin = $stocksParProduit[$p->id]);
        }

        return view('transferts.create', [
            'produits' => $produits,
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
            'magasinSourceId' => $magasinSourceId,
        ]);
    }

    public function store(Request $request, TransfertService $transfertService): RedirectResponse
    {
        $donnees = $request->validate([
            'produit_id' => ['required', 'exists:produits,id'],
            'magasin_source_id' => ['required', 'exists:magasins,id', 'different:magasin_destination_id'],
            'magasin_destination_id' => ['required', 'exists:magasins,id'],
            'quantite' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $transfertService->transferer(
                Produit::findOrFail($donnees['produit_id']),
                Magasin::findOrFail($donnees['magasin_source_id']),
                Magasin::findOrFail($donnees['magasin_destination_id']),
                $donnees['quantite'],
                $request->user(),
            );
        } catch (StockInsuffisantException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('transferts.index')->with('succes', 'Transfert effectué.');
    }
}
