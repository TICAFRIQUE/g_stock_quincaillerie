<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\MotifMouvement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MotifMouvementController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = MotifMouvement::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('nom', 'like', "%{$recherche}%");
            });

        $motifs = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        return view('motifs-mouvement.index', ['motifs' => $motifs]);
    }

    public function create(): View
    {
        return view('motifs-mouvement.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        MotifMouvement::create($donnees);

        return redirect()->route('motifs-mouvement.index')->with('succes', 'Motif créé.');
    }

    public function edit(MotifMouvement $motifMouvement): View
    {
        return view('motifs-mouvement.edit', ['motif' => $motifMouvement]);
    }

    public function update(Request $request, MotifMouvement $motifMouvement): RedirectResponse
    {
        $donnees = $this->valider($request, $motifMouvement);

        $motifMouvement->update($donnees);

        return redirect()->route('motifs-mouvement.index')->with('succes', 'Motif mis à jour.');
    }

    public function destroy(MotifMouvement $motifMouvement): RedirectResponse
    {
        $motifMouvement->delete();

        return redirect()->route('motifs-mouvement.index')->with('succes', 'Motif supprimé.');
    }

    /**
     * Création à la volée depuis un formulaire de mouvement (caisse ou
     * trésorerie, voir sessions/show.blade.php et comptabilite/show.blade.php)
     * — le motif choisi/créé ici reste une chaîne libre stockée directement
     * sur MouvementCaisse/EcritureCompteTresorerie, jamais une clé étrangère
     * (règle 19) : ce référentiel ne fait qu'alimenter le <select>.
     */
    public function storeRapide(Request $request): JsonResponse
    {
        $validateur = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'max:255'],
        ]);

        if ($validateur->fails()) {
            return response()->json(['errors' => $validateur->errors()], 422);
        }

        $motif = MotifMouvement::firstOrCreate(
            ['nom' => $validateur->validated()['nom']],
            ['actif' => true]
        );

        return response()->json(['id' => $motif->id, 'nom' => $motif->nom], 201);
    }

    private function valider(Request $request, ?MotifMouvement $motifMouvement = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:motif_mouvements,nom,'.($motifMouvement?->id)],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
