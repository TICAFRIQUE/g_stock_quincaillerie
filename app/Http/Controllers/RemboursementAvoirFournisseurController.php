<?php

namespace App\Http\Controllers;

use App\Exceptions\AvoirFournisseurInsuffisantException;
use App\Exceptions\SoldeTresorerieInsuffisantException;
use App\Models\Fournisseur;
use App\Services\RemboursementAvoirFournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Règle un avoir fournisseur (solde négatif — le fournisseur nous doit de
 * l'argent) : symétrique de RemboursementAvoirClientController. Aucun
 * rattachement à une session de caisse : la part reçue en espèces entre
 * directement dans la Caisse Générale (voir CLAUDE.md, Trésorerie).
 */
class RemboursementAvoirFournisseurController extends Controller
{
    public function store(Request $request, Fournisseur $fournisseur, RemboursementAvoirFournisseurService $service): RedirectResponse
    {
        $donnees = $request->validate([
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $service->rembourser(
                fournisseur: $fournisseur,
                auteur: $request->user(),
                paiements: $donnees['paiements'],
            );
        } catch (AvoirFournisseurInsuffisantException|SoldeTresorerieInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Avoir remboursé.');
    }
}
