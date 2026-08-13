<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Taxe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxeController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Taxe::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('nom', 'like', "%{$recherche}%");
            });

        $taxes = $this->appliquerTri($query, $request, ['nom', 'taux', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('taxes.index', ['taxes' => $taxes]);
    }

    public function create(): View
    {
        return view('taxes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        Taxe::create($donnees);

        return redirect()->route('taxes.index')->with('succes', 'Taxe créée.');
    }

    public function edit(Taxe $taxe): View
    {
        return view('taxes.edit', ['taxe' => $taxe]);
    }

    public function update(Request $request, Taxe $taxe): RedirectResponse
    {
        $donnees = $this->valider($request, $taxe);

        $taxe->update($donnees);

        return redirect()->route('taxes.index')->with('succes', 'Taxe mise à jour.');
    }

    public function destroy(Taxe $taxe): RedirectResponse
    {
        if ($taxe->lignesCommandeAchat()->exists()) {
            return redirect()->route('taxes.index')->with('erreur', 'Cette taxe est utilisée sur des lignes d\'achat, elle ne peut pas être supprimée.');
        }

        $taxe->delete();

        return redirect()->route('taxes.index')->with('succes', 'Taxe supprimée.');
    }

    private function valider(Request $request, ?Taxe $taxe = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:taxes,nom,'.($taxe?->id)],
            'taux' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
