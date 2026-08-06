<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\UniteVente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UniteVenteController extends Controller
{
    public function store(Request $request, Produit $produit): RedirectResponse
    {
        abort_unless($request->user()->can('produit.modifier'), 403);

        $donnees = $this->valider($request, $produit);

        $produit->uniteVentes()->create($donnees);

        return redirect()->route('produits.edit', $produit)->with('succes', 'Unité de vente ajoutée.');
    }

    public function update(Request $request, Produit $produit, UniteVente $uniteVente): RedirectResponse
    {
        abort_unless($request->user()->can('produit.modifier'), 403);
        abort_unless($uniteVente->produit_id === $produit->id, 404);

        $donnees = $this->valider($request, $produit, $uniteVente);

        $uniteVente->update($donnees);

        return redirect()->route('produits.edit', $produit)->with('succes', 'Unité de vente mise à jour.');
    }

    public function destroy(Request $request, Produit $produit, UniteVente $uniteVente): RedirectResponse
    {
        abort_unless($request->user()->can('produit.modifier'), 403);
        abort_unless($uniteVente->produit_id === $produit->id, 404);

        $uniteVente->delete();

        return redirect()->route('produits.edit', $produit)->with('succes', 'Unité de vente supprimée.');
    }

    private function valider(Request $request, Produit $produit, ?UniteVente $uniteVente = null): array
    {
        $donnees = $request->validate([
            'unite_id' => ['required', 'exists:unites,id'],
            'facteur' => [
                'required', 'integer', 'min:2',
                // Même unité + même facteur = deux variantes indiscernables
                // à la vente (ex. deux « Carton de 24 »).
                Rule::unique('unite_ventes', 'facteur')
                    ->where('produit_id', $produit->id)
                    ->where('unite_id', $request->input('unite_id'))
                    ->ignore($uniteVente?->id),
            ],
            'prix' => ['required', 'integer', 'min:0'],
        ]);

        $donnees['actif'] = true;

        return $donnees;
    }
}
