<?php

namespace App\Http\Controllers;

use App\Exceptions\QuantiteRetourInvalideException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Models\Vente;
use App\Services\RetourVenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class RetourVenteController extends Controller
{
    use AutoriseMagasin;

    public function store(Request $request, Vente $vente, RetourVenteService $retourService): RedirectResponse
    {
        $this->assurerMagasin($vente->magasin_id);

        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.ligne_vente_id' => ['required', Rule::exists('ligne_ventes', 'id')->where('vente_id', $vente->id)],
            'lignes.*.quantite_pieces' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $retourService->retourner($vente, $donnees['lignes'], $request->user(), $donnees['motif'] ?? null);
        } catch (QuantiteRetourInvalideException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('ventes.ticket', $vente)
            ->with('succes', 'Retour enregistré. Le stock et le compte client ont été mis à jour.');
    }
}
