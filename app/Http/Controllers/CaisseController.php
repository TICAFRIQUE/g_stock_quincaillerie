<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Caisse;
use App\Models\Magasin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaisseController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Caisse::query()
            ->with(['magasin', 'sessionCaisses' => fn ($q) => $q->whereNull('date_fermeture')])
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('nom', 'like', "%{$recherche}%");
            });

        $caisses = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('caisses.index', ['caisses' => $caisses]);
    }

    public function create(): View
    {
        return view('caisses.create', ['magasins' => Magasin::magasins()->where('actif', true)->orderBy('nom')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        Caisse::create($donnees);

        return redirect()->route('caisses.index')->with('succes', 'Caisse créée.');
    }

    public function edit(Caisse $caisse): View
    {
        return view('caisses.edit', [
            'caisse' => $caisse,
            'magasins' => Magasin::magasins()->where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function update(Request $request, Caisse $caisse): RedirectResponse
    {
        $donnees = $this->valider($request, $caisse);

        $caisse->update($donnees);

        return redirect()->route('caisses.index')->with('succes', 'Caisse mise à jour.');
    }

    public function destroy(Caisse $caisse): RedirectResponse
    {
        if ($caisse->sessionCaisses()->whereNull('date_fermeture')->exists()) {
            return redirect()->route('caisses.index')->with('erreur', 'Cette caisse a une session ouverte, elle ne peut pas être supprimée.');
        }

        $caisse->delete();

        return redirect()->route('caisses.index')->with('succes', 'Caisse supprimée.');
    }

    private function valider(Request $request, ?Caisse $caisse = null): array
    {
        $donnees = $request->validate([
            'magasin_id' => ['required', 'exists:magasins,id'],
            'nom' => ['required', 'string', 'max:255'],
            'actif' => ['boolean'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
