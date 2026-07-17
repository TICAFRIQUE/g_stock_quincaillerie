<?php

namespace App\Http\Controllers;

use App\Models\MoyenPaiement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MoyenPaiementController extends Controller
{
    public function index(): View
    {
        $moyensPaiement = MoyenPaiement::query()->orderBy('nom')->get();

        return view('moyens-paiement.index', ['moyensPaiement' => $moyensPaiement]);
    }

    public function create(): View
    {
        return view('moyens-paiement.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:moyen_paiements,nom'],
        ]);

        // est_espece n'est jamais modifiable depuis l'UI : un seul moyen de paiement
        // (« Espèces », seedé) alimente le comptage du tiroir.
        MoyenPaiement::create($donnees + ['actif' => true, 'est_espece' => false]);

        return redirect()->route('moyens-paiement.index')->with('succes', 'Moyen de paiement créé.');
    }

    public function edit(MoyenPaiement $moyenPaiement): View
    {
        return view('moyens-paiement.edit', ['moyenPaiement' => $moyenPaiement]);
    }

    public function update(Request $request, MoyenPaiement $moyenPaiement): RedirectResponse
    {
        if ($moyenPaiement->est_espece) {
            return redirect()->route('moyens-paiement.index')->with('erreur', 'Le moyen de paiement espèces ne peut pas être modifié.');
        }

        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:moyen_paiements,nom,'.$moyenPaiement->id],
            'actif' => ['boolean'],
        ]);
        $donnees['actif'] = $request->boolean('actif');

        $moyenPaiement->update($donnees);

        return redirect()->route('moyens-paiement.index')->with('succes', 'Moyen de paiement mis à jour.');
    }

    public function destroy(MoyenPaiement $moyenPaiement): RedirectResponse
    {
        if ($moyenPaiement->est_espece) {
            return redirect()->route('moyens-paiement.index')->with('erreur', 'Le moyen de paiement espèces ne peut pas être supprimé.');
        }

        $moyenPaiement->delete();

        return redirect()->route('moyens-paiement.index')->with('succes', 'Moyen de paiement supprimé.');
    }
}
