<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Magasin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MagasinController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Magasin::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('nom', 'like', "%{$recherche}%");
            });

        $magasins = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('magasins.index', ['magasins' => $magasins]);
    }

    public function create(): View
    {
        return view('magasins.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        Magasin::create($donnees);

        return redirect()->route('magasins.index')->with('succes', 'Magasin créé.');
    }

    public function edit(Magasin $magasin): View
    {
        return view('magasins.edit', ['magasin' => $magasin]);
    }

    public function update(Request $request, Magasin $magasin): RedirectResponse
    {
        $donnees = $this->valider($request, $magasin);

        $magasin->update($donnees);

        return redirect()->route('magasins.index')->with('succes', 'Magasin mis à jour.');
    }

    public function destroy(Magasin $magasin): RedirectResponse
    {
        $magasin->delete();

        return redirect()->route('magasins.index')->with('succes', 'Magasin supprimé.');
    }

    private function valider(Request $request, ?Magasin $magasin = null): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:magasins,nom,'.($magasin?->id)],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'actif' => ['boolean'],
        ]) + ['actif' => $request->boolean('actif', true)];
    }
}
