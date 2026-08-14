<?php

namespace App\Http\Controllers;

use App\Exceptions\SoldeFournisseurInsuffisantException;
use App\Models\CommandeAchat;
use App\Models\Fournisseur;
use App\Services\ReglementFournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Contrairement à ReglementClientController, pas de rattachement à une
 * session de caisse : le règlement fournisseur est indépendant du tiroir
 * (voir CLAUDE.md, décision produit). Saisi via une modale directement
 * depuis la fiche fournisseur (pas d'écran dédié).
 */
class ReglementFournisseurController extends Controller
{
    public function store(Request $request, Fournisseur $fournisseur, ReglementFournisseurService $reglementService): RedirectResponse
    {
        $donnees = $request->validate([
            'commande_achat_id' => ['nullable', Rule::exists('commande_achats', 'id')->where('fournisseur_id', $fournisseur->id)],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        $commandeAchat = $donnees['commande_achat_id'] ?? null
            ? CommandeAchat::withTrashed()->find($donnees['commande_achat_id'])
            : null;

        // Un règlement imputé à un achat précis ne peut pas dépasser le
        // reste dû de CET achat (indépendamment de la dette totale du
        // fournisseur, qui peut inclure d'autres achats).
        if ($commandeAchat) {
            $montantTotal = array_sum(array_column($donnees['paiements'], 'montant'));
            if ($montantTotal > $commandeAchat->resteDu()) {
                return back()->withInput()->with('erreur', 'Le montant dépasse le reste dû sur cet achat ('.number_format($commandeAchat->resteDu(), 0, ',', ' ').' F).');
            }
        }

        try {
            $reglementService->encaisser(
                fournisseur: $fournisseur,
                auteur: $request->user(),
                paiements: $donnees['paiements'],
                commandeAchat: $commandeAchat,
            );
        } catch (SoldeFournisseurInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        // back() plutôt qu'une route fixe : le formulaire est soumis depuis
        // la fiche fournisseur (modale) OU depuis le détail d'un achat
        // précis (carte règlement) — on reste sur la page d'origine.
        return back()->with('succes', 'Règlement enregistré.');
    }
}
