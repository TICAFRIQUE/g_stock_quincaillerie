<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Categorie;
use App\Models\Produit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProduitController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Produit::query()
            ->with('categorie')
            ->when($request->filled('recherche'), function ($query) use ($request) {
                $recherche = $request->string('recherche');
                $query->where(function ($q) use ($recherche) {
                    $q->where('sku', 'like', "%{$recherche}%")
                        ->orWhere('nom', 'like', "%{$recherche}%")
                        ->orWhere('code_barre', 'like', "%{$recherche}%");
                });
            });

        $produits = $this->appliquerTri($query, $request, ['nom', 'sku', 'prix_piece', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('produits.index', ['produits' => $produits]);
    }

    public function create(): View
    {
        abort_unless($this->peut('produit.creer'), 403);

        return view('produits.create', [
            'categories' => Categorie::orderBy('nom')->get(),
            'nomsExistants' => Produit::where('actif', true)->pluck('nom'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->peut('produit.creer'), 403);

        $donnees = $this->valider($request);
        $skuGenere = blank($donnees['sku']);

        if ($skuGenere) {
            $donnees['sku'] = $this->genererSku();
        }

        $lots = $this->validerLots($request);

        $produit = DB::transaction(function () use ($donnees, $lots) {
            $produit = Produit::create($donnees);

            foreach ($lots as $lot) {
                $produit->uniteVentes()->create($lot + ['actif' => true]);
            }

            return $produit;
        });

        if ($request->hasFile('image')) {
            $produit->addMediaFromRequest('image')->toMediaCollection('image');
        }

        $message = 'Produit créé.'.($skuGenere ? " SKU généré automatiquement : {$produit->sku}." : '');
        if (count($lots) > 0) {
            $message .= ' '.count($lots).' unité(s) de vente ajoutée(s).';
        }

        return redirect()->route('produits.index')->with('succes', $message);
    }

    public function edit(Produit $produit): View
    {
        $produit->load(['categorie', 'uniteVentes' => fn ($q) => $q->orderBy('libelle')]);

        return view('produits.edit', [
            'produit' => $produit,
            'categories' => Categorie::orderBy('nom')->get(),
            'nomsExistants' => Produit::where('actif', true)->where('id', '!=', $produit->id)->pluck('nom'),
            'peutModifier' => $this->peut('produit.modifier'),
            'peutSupprimer' => $this->peut('produit.supprimer'),
        ]);
    }

    public function update(Request $request, Produit $produit): RedirectResponse
    {
        abort_unless($this->peut('produit.modifier'), 403);

        $donnees = $this->valider($request, $produit);
        $skuGenere = blank($donnees['sku']);

        if ($skuGenere) {
            $donnees['sku'] = $this->genererSku();
        }

        $produit->update($donnees);

        if ($request->hasFile('image')) {
            $produit->addMediaFromRequest('image')->toMediaCollection('image');
        }

        $message = 'Produit mis à jour.'.($skuGenere ? " SKU généré automatiquement : {$produit->sku}." : '');

        return redirect()->route('produits.edit', $produit)->with('succes', $message);
    }

    public function destroy(Produit $produit): RedirectResponse
    {
        abort_unless($this->peut('produit.supprimer'), 403);

        $produit->delete();

        return redirect()->route('produits.index')->with('succes', 'Produit supprimé.');
    }

    private function peut(string $permission): bool
    {
        return request()->user()->can($permission);
    }

    private function genererSku(): string
    {
        do {
            $sku = 'PRD-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Produit::withTrashed()->where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * @return array<int, array{libelle: string, facteur: int, prix: int}>
     */
    private function validerLots(Request $request): array
    {
        $donnees = $request->validate([
            'unites_vente' => ['nullable', 'array'],
            'unites_vente.*.facteur' => ['required', 'integer', 'min:2', 'distinct'],
            'unites_vente.*.prix' => ['required', 'integer', 'min:0'],
        ]);

        return collect($donnees['unites_vente'] ?? [])
            ->map(fn (array $lot) => $lot + ['libelle' => "Lot de {$lot['facteur']}"])
            ->all();
    }

    private function valider(Request $request, ?Produit $produit = null): array
    {
        // Deux produits peuvent porter le même nom (CLAUDE.md — le SKU
        // identifie le produit) ; dans ce cas le libellé distinctif devient
        // obligatoire pour ne pas les confondre à la caisse.
        $nomEnDouble = Produit::where('actif', true)
            ->when($produit, fn ($q) => $q->where('id', '!=', $produit->id))
            ->where('nom', $request->input('nom'))
            ->exists();

        $donnees = $request->validate([
            'sku' => ['nullable', 'string', 'max:255', 'unique:produits,sku,'.($produit?->id)],
            'nom' => ['required', 'string', 'max:255'],
            'libelle_distinctif' => [Rule::requiredIf($nomEnDouble), 'nullable', 'string', 'max:255'],
            'code_barre' => ['nullable', 'string', 'max:255', 'unique:produits,code_barre,'.($produit?->id)],
            'categorie_id' => ['required', 'exists:categories,id'],
            'prix_piece' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['required', 'integer', 'min:0'],
            'actif' => ['boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);
        unset($donnees['image']);

        return $donnees;
    }
}
