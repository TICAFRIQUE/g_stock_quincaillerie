<?php

namespace App\Http\Controllers;

use App\Exceptions\QuantiteReceptionInvalideException;
use App\Models\CommandeAchat;
use App\Services\ReceptionAchatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Pas d'AutoriseMagasin ici : comme CommandeAchatController/RetourAchatController,
 * une commande d'achat n'a pas de magasin d'en-tête (destination par ligne,
 * et la destination de la réception est un choix éditable — voir
 * ReceptionAchatService).
 */
class ReceptionAchatController extends Controller
{
    public function store(Request $request, CommandeAchat $commandeAchat, ReceptionAchatService $service): RedirectResponse
    {
        abort_unless($request->user()->can('achat.receptionner'), 403);

        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
            'numero_facture_fournisseur' => ['nullable', 'string', 'max:255'],
            'numero_bon_livraison_fournisseur' => ['nullable', 'string', 'max:255'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.ligne_commande_achat_id' => ['required', Rule::exists('ligne_commande_achats', 'id')->where('commande_achat_id', $commandeAchat->id)],
            'lignes.*.magasin_id' => ['required', 'exists:magasins,id'],
            'lignes.*.quantite_pieces' => ['required', 'numeric', 'min:0'],
            'lignes.*.prix_achat_reel' => ['required', 'integer', 'min:0'],
            'paiements' => ['sometimes', 'array'],
            'paiements.*.moyen_paiement_id' => ['required_with:paiements', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required_with:paiements', 'integer', 'min:1'],
        ]);

        try {
            $reception = $service->receptionner(
                $commandeAchat,
                $donnees['lignes'],
                $request->user(),
                $donnees['paiements'] ?? [],
                $donnees['motif'] ?? null,
                $donnees['numero_facture_fournisseur'] ?? null,
                $donnees['numero_bon_livraison_fournisseur'] ?? null,
            );
        } catch (QuantiteReceptionInvalideException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('commande-achats.show', $commandeAchat)
            ->with('succes', "Réception {$reception->numero} enregistrée : le stock et le compte fournisseur ont été mis à jour.");
    }
}
