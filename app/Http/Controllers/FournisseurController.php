<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Fournisseur;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FournisseurController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Fournisseur::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where(function ($sub) use ($recherche) {
                    $sub->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('telephone', 'like', "%{$recherche}%")
                        ->orWhere('email', 'like', "%{$recherche}%");
                });
            });

        $fournisseurs = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('fournisseurs.index', ['fournisseurs' => $fournisseurs]);
    }

    public function create(): View
    {
        return view('fournisseurs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        Fournisseur::create($donnees);

        return redirect()->route('fournisseurs.index')->with('succes', 'Fournisseur créé.');
    }

    public function edit(Fournisseur $fournisseur): View
    {
        return view('fournisseurs.edit', ['fournisseur' => $fournisseur]);
    }

    public function update(Request $request, Fournisseur $fournisseur): RedirectResponse
    {
        $donnees = $this->valider($request, $fournisseur);

        $fournisseur->update($donnees);

        return redirect()->route('fournisseurs.index')->with('succes', 'Fournisseur mis à jour.');
    }

    public function destroy(Fournisseur $fournisseur): RedirectResponse
    {
        if ($fournisseur->commandeAchats()->exists()) {
            return redirect()->route('fournisseurs.index')->with('erreur', 'Ce fournisseur a des commandes d\'achat, il ne peut pas être supprimé.');
        }

        $fournisseur->delete();

        return redirect()->route('fournisseurs.index')->with('succes', 'Fournisseur supprimé.');
    }

    private function valider(Request $request, ?Fournisseur $fournisseur = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'actif' => ['boolean'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
