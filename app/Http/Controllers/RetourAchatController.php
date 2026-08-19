<?php

namespace App\Http\Controllers;

use App\Exceptions\QuantiteRetourInvalideException;
use App\Exceptions\StockInsuffisantException;
use App\Models\CommandeAchat;
use App\Services\RetourAchatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Pas d'AutoriseMagasin ici : comme CommandeAchatController, une commande
 * d'achat n'a pas de magasin d'en-tête (destination par ligne).
 */
class RetourAchatController extends Controller
{
    public function store(Request $request, CommandeAchat $commandeAchat, RetourAchatService $retourService): RedirectResponse
    {
        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.ligne_commande_achat_id' => ['required', Rule::exists('ligne_commande_achats', 'id')->where('commande_achat_id', $commandeAchat->id)],
            'lignes.*.quantite_pieces' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $retourService->retourner($commandeAchat, $donnees['lignes'], $request->user(), $donnees['motif'] ?? null);
        } catch (QuantiteRetourInvalideException|StockInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('commande-achats.show', $commandeAchat)
            ->with('succes', 'Retour enregistré. Le stock et le compte fournisseur ont été mis à jour.');
    }
}
