<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Devise;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DeviseController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Devise::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where(function ($sub) use ($recherche) {
                    $sub->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('abreviation', 'like', "%{$recherche}%");
                });
            });

        $devises = $this->appliquerTri($query, $request, ['nom', 'abreviation', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('devises.index', ['devises' => $devises]);
    }

    public function create(): View
    {
        return view('devises.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        Devise::create($donnees);

        return redirect()->route('devises.index')->with('succes', 'Devise créée.');
    }

    public function edit(Devise $devise): View
    {
        return view('devises.edit', ['devise' => $devise]);
    }

    public function update(Request $request, Devise $devise): RedirectResponse
    {
        $donnees = $this->valider($request, $devise);

        $devise->update($donnees);

        return redirect()->route('devises.index')->with('succes', 'Devise mise à jour.');
    }

    public function destroy(Devise $devise): RedirectResponse
    {
        if (\App\Models\Parametre::actuel()->devise_id === $devise->id) {
            return redirect()->route('devises.index')->with('erreur', "C'est la devise actuellement utilisée, choisissez-en une autre dans Paramètres avant de la supprimer.");
        }

        $devise->delete();

        return redirect()->route('devises.index')->with('succes', 'Devise supprimée.');
    }

    private function valider(Request $request, ?Devise $devise = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:devises,nom,'.($devise?->id)],
            'abreviation' => ['required', 'string', 'max:20'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
