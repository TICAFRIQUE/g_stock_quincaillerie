<?php

namespace App\Http\Controllers;

use App\Enums\MouvementStockType;
use App\Exceptions\StockInsuffisantException;
use App\Models\Magasin;
use App\Models\MouvementStock;
use App\Models\Produit;
use App\Models\Stock;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMouvementController extends Controller
{
    public function index(Request $request): View
    {
        $debut = $request->filled('date_debut') ? Carbon::parse($request->string('date_debut')) : now()->startOfMonth();
        $fin = $request->filled('date_fin') ? Carbon::parse($request->string('date_fin')) : now()->endOfMonth();
        $type = MouvementStockType::tryFrom($request->string('type')->toString());

        $requeteMouvements = MouvementStock::query()
            ->with(['produit', 'magasin', 'auteur'])
            ->when($request->user()->magasin_id, fn ($q, $magasinId) => $q->where('magasin_id', $magasinId))
            ->whereBetween('created_at', [$debut->copy()->startOfDay(), $fin->copy()->endOfDay()])
            ->when($type, fn ($q) => $q->where('type', $type->value))
            ->orderByDesc('created_at');

        $mouvements = $request->boolean('tout')
            ? $requeteMouvements->get()
            : $requeteMouvements->paginate(30)->withQueryString();

        return view('stock.mouvements-index', [
            'mouvements' => $mouvements,
            'dateDebut' => $debut->toDateString(),
            'dateFin' => $fin->toDateString(),
            'type' => $type,
            'types' => MouvementStockType::cases(),
        ]);
    }

    public function create(Request $request): View
    {
        $magasinId = $request->integer('magasin_id') ?: null;

        $produits = Produit::where('actif', true)->orderBy('nom')->get(['id', 'sku', 'nom', 'libelle_distinctif']);

        if ($magasinId) {
            $stocksParProduit = Stock::where('magasin_id', $magasinId)->pluck('quantite', 'produit_id');
            $produits->each(fn (Produit $p) => $p->stock_magasin = $stocksParProduit[$p->id] ?? 0);
        }

        return view('stock.mouvement-create', [
            'produits' => $produits,
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
            'magasinId' => $magasinId,
        ]);
    }

    public function store(Request $request, StockService $stockService): RedirectResponse
    {
        $donnees = $request->validate([
            'type' => ['required', 'in:casse,ajustement'],
            'direction' => ['required_if:type,ajustement', 'in:entree,sortie'],
            'produit_id' => ['required', 'exists:produits,id'],
            'magasin_id' => ['required', 'exists:magasins,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'motif' => ['nullable', 'string', 'max:255'],
        ]);

        $estCasse = $donnees['type'] === 'casse';
        $type = $estCasse ? MouvementStockType::Casse : MouvementStockType::Ajustement;
        $signe = $estCasse || $donnees['direction'] === 'sortie' ? -1 : 1;

        try {
            $stockService->enregistrerMouvement(
                produit: Produit::findOrFail($donnees['produit_id']),
                magasin: Magasin::findOrFail($donnees['magasin_id']),
                quantite: $signe * $donnees['quantite'],
                type: $type,
                auteur: $request->user(),
                motif: $donnees['motif'] ?: ($estCasse ? 'Casse' : 'Ajustement manuel'),
            );
        } catch (StockInsuffisantException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('stock.index')->with('succes', 'Mouvement enregistré.');
    }
}
