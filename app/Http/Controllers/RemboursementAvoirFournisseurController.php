<?php

namespace App\Http\Controllers;

use App\Exceptions\AvoirFournisseurInsuffisantException;
use App\Exceptions\SoldeCaisseInsuffisantException;
use App\Models\Fournisseur;
use App\Models\SessionCaisse;
use App\Services\RemboursementAvoirFournisseurService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Règle un avoir fournisseur (solde négatif — le fournisseur nous doit de
 * l'argent) : symétrique de RemboursementAvoirClientController. Une session
 * de caisse n'est exigée que si une partie est reçue en espèces (voir
 * RemboursementAvoirFournisseurService).
 */
class RemboursementAvoirFournisseurController extends Controller
{
    public function store(Request $request, Fournisseur $fournisseur, RemboursementAvoirFournisseurService $service): RedirectResponse
    {
        $donnees = $request->validate([
            'session_caisse_id' => ['nullable', Rule::exists('session_caisses', 'id')->whereNull('date_cloture')->whereNull('date_fermeture')],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        $session = $donnees['session_caisse_id'] ?? null
            ? SessionCaisse::find($donnees['session_caisse_id'])
            : null;

        try {
            $service->rembourser(
                fournisseur: $fournisseur,
                auteur: $request->user(),
                paiements: $donnees['paiements'],
                session: $session,
            );
        } catch (AvoirFournisseurInsuffisantException|SoldeCaisseInsuffisantException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return back()->with('succes', 'Avoir remboursé.');
    }
}
