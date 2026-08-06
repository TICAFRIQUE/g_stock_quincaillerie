<?php

namespace App\Http\Controllers;

use App\Models\Unite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UniteController extends Controller
{
    public function index(): View
    {
        $unites = Unite::withCount(['produits', 'uniteVentes'])->orderBy('nom')->get();

        return view('unites.index', ['unites' => $unites]);
    }

    public function create(): View
    {
        return view('unites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100', 'unique:unites,nom'],
            'abbreviation' => ['nullable', 'string', 'max:10'],
        ]);

        Unite::create($donnees + ['actif' => true]);

        return redirect()->route('unites.index')->with('succes', 'Unité créée.');
    }

    public function edit(Unite $unite): View
    {
        return view('unites.edit', ['unite' => $unite]);
    }

    public function update(Request $request, Unite $unite): RedirectResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:100', 'unique:unites,nom,'.$unite->id],
            'abbreviation' => ['nullable', 'string', 'max:10'],
            'actif' => ['boolean'],
        ]);
        $donnees['actif'] = $request->boolean('actif');

        $unite->update($donnees);

        return redirect()->route('unites.index')->with('succes', 'Unité mise à jour.');
    }

    public function destroy(Unite $unite): RedirectResponse
    {
        if ($unite->produits()->exists() || $unite->uniteVentes()->exists()) {
            return redirect()->route('unites.index')->with('erreur', 'Cette unité est utilisée par au moins un produit, elle ne peut pas être supprimée. Désactivez-la à la place.');
        }

        $unite->delete();

        return redirect()->route('unites.index')->with('succes', 'Unité supprimée.');
    }
}
