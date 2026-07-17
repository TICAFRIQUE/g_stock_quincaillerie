<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\UniteVente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
            'facteur' => ['required', 'integer', 'min:2', 'unique:unite_ventes,facteur,'.($uniteVente?->id).',id,produit_id,'.$produit->id],
            'prix' => ['required', 'integer', 'min:0'],
        ]);

        $donnees['libelle'] = $this->genererLibelle($donnees['facteur']);
        $donnees['actif'] = true;

        return $donnees;
    }

    private function genererLibelle(int $facteur): string
    {
        return "Lot de {$facteur}";
    }
}
