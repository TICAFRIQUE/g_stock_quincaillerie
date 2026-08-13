<?php

namespace App\Http\Controllers;

use App\Exceptions\SoldeFournisseurInsuffisantException;
use App\Models\Fournisseur;
use App\Models\MoyenPaiement;
use App\Services\ReglementFournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Contrairement à ReglementClientController, pas de rattachement à une
 * session de caisse : le règlement fournisseur est indépendant du tiroir
 * (voir CLAUDE.md, décision produit).
 */
class ReglementFournisseurController extends Controller
{
    public function create(Fournisseur $fournisseur): View
    {
        return view('reglements-fournisseur.create', [
            'fournisseur' => $fournisseur,
            'solde' => $fournisseur->solde(),
            'moyensPaiement' => MoyenPaiement::actifs(),
        ]);
    }

    public function store(Request $request, Fournisseur $fournisseur, ReglementFournisseurService $reglementService): RedirectResponse
    {
        $donnees = $request->validate([
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $reglementService->encaisser(
                fournisseur: $fournisseur,
                auteur: $request->user(),
                paiements: $donnees['paiements'],
            );
        } catch (SoldeFournisseurInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('fournisseurs.show', $fournisseur)->with('succes', 'Règlement enregistré.');
    }
}
