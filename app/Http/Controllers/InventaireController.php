<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Inventaire;
use App\Models\Magasin;
use App\Models\Produit;
use App\Services\InventaireService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class InventaireController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $debut = $request->filled('date_debut') ? Carbon::parse($request->string('date_debut')) : now()->startOfMonth();
        $fin = $request->filled('date_fin') ? Carbon::parse($request->string('date_fin')) : now()->endOfMonth();

        $query = Inventaire::query()
            ->with('magasin')
            ->whereBetween('date', [$debut->toDateString(), $fin->toDateString()])
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('magasin_id', $request->integer('magasin_id')));

        $inventaires = $this->appliquerTri($query, $request, ['date', 'statut'], 'date')
            ->paginate(20)
            ->withQueryString();

        return view('inventaires.index', [
            'inventaires' => $inventaires,
            'dateDebut' => $debut->toDateString(),
            'dateFin' => $fin->toDateString(),
            'magasins' => Magasin::orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        abort_unless(request()->user()->can('inventaire.realiser'), 403);

        return view('inventaires.create', [
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('inventaire.realiser'), 403);

        $donnees = $request->validate([
            'magasin_id' => ['required', 'exists:magasins,id'],
            'date' => ['required', 'date'],
        ]);

        $inventaire = Inventaire::create($donnees + [
            'statut' => 'brouillon',
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('inventaires.show', $inventaire)->with('succes', 'Fiche d\'inventaire créée. Saisissez les quantités comptées.');
    }

    public function show(Inventaire $inventaire): View
    {
        $inventaire->load(['magasin', 'lignes.produit', 'auteur', 'validateur']);

        return view('inventaires.show', [
            'inventaire' => $inventaire,
            'produits' => Produit::where('actif', true)->orderBy('nom')->get(['id', 'sku', 'nom', 'libelle_distinctif']),
            'produitsComptesIds' => $inventaire->lignes->pluck('produit_id')->all(),
            'peutRealiser' => request()->user()->can('inventaire.realiser'),
            'peutValider' => request()->user()->can('inventaire.valider'),
        ]);
    }

    public function saisir(Request $request, Inventaire $inventaire, InventaireService $inventaireService): RedirectResponse
    {
        abort_unless($request->user()->can('inventaire.realiser'), 403);

        $donnees = $request->validate([
            'produit_id' => ['required', 'exists:produits,id'],
            'quantite_comptee' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $inventaireService->saisirComptage(
                $inventaire,
                Produit::findOrFail($donnees['produit_id']),
                $donnees['quantite_comptee'],
            );
        } catch (RuntimeException $e) {
            return redirect()->route('inventaires.show', $inventaire)->with('erreur', $e->getMessage());
        }

        return redirect()->route('inventaires.show', $inventaire)->with('succes', 'Comptage enregistré.');
    }

    public function valider(Request $request, Inventaire $inventaire, InventaireService $inventaireService): RedirectResponse
    {
        abort_unless($request->user()->can('inventaire.valider'), 403);

        try {
            $inventaireService->valider($inventaire, $request->user());
        } catch (RuntimeException $e) {
            return redirect()->route('inventaires.show', $inventaire)->with('erreur', $e->getMessage());
        }

        return redirect()->route('inventaires.show', $inventaire)->with('succes', 'Inventaire validé : les écarts ont été appliqués au stock.');
    }
}
