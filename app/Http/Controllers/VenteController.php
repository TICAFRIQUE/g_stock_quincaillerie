<?php

namespace App\Http\Controllers;

use App\Exceptions\SessionNonOuverteException;
use App\Exceptions\StockInsuffisantException;
use App\Models\MoyenPaiement;
use App\Models\Paiement;
use App\Models\Produit;
use App\Models\SessionCaisse;
use App\Models\Stock;
use App\Models\Vente;
use App\Services\VenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class VenteController extends Controller
{
    public function create(SessionCaisse $session): View
    {
        abort_if($session->date_cloture || $session->date_fermeture, 403, 'Cette session n\'est plus ouverte.');

        $magasinId = $session->caisse->magasin_id;
        $stocksParProduit = Stock::where('magasin_id', $magasinId)->pluck('quantite', 'produit_id');

        $produits = Produit::where('actif', true)
            ->with(['uniteVentes' => fn ($q) => $q->where('actif', true)->orderBy('facteur')])
            ->orderBy('nom')
            ->get()
            ->map(fn (Produit $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'nom' => $p->nom,
                'libelle_distinctif' => $p->libelle_distinctif,
                'libelle_affichage' => $p->libelle_affichage,
                'prix_piece' => $p->prix_piece,
                'stock' => (int) ($stocksParProduit[$p->id] ?? 0),
                'unites' => $p->uniteVentes->map(fn ($u) => [
                    'id' => $u->id,
                    'libelle' => $u->libelle,
                    'facteur' => $u->facteur,
                    'prix' => $u->prix,
                ])->values(),
            ])
            ->values();

        return view('ventes.create', [
            'session' => $session,
            'produits' => $produits,
            'moyensPaiement' => MoyenPaiement::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request, SessionCaisse $session, VenteService $venteService): RedirectResponse
    {
        $this->nettoyerChampsOptionnels($request);

        $donnees = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'exists:produits,id'],
            'lignes.*.unite_vente_id' => ['nullable', 'exists:unite_ventes,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.remise_type' => ['nullable', 'in:montant,pourcentage'],
            'lignes.*.remise_valeur' => ['nullable', 'integer', 'min:0'],
            'remise_totale_type' => ['nullable', 'in:montant,pourcentage'],
            'remise_totale_valeur' => ['nullable', 'integer', 'min:0'],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $vente = $venteService->vendre(
                session: $session,
                caissier: $request->user(),
                lignes: $donnees['lignes'],
                paiements: $donnees['paiements'],
                remiseTotaleType: $donnees['remise_totale_type'] ?? null,
                remiseTotaleValeur: $donnees['remise_totale_valeur'] ?? null,
            );
        } catch (StockInsuffisantException|SessionNonOuverteException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('ventes.ticket', $vente)->with('succes', 'Vente enregistrée.');
    }

    public function ticket(Vente $vente): View
    {
        $vente->load(['lignes.produit', 'lignes.uniteVente', 'paiements.moyenPaiement', 'magasin', 'caissier', 'sessionCaisse.caisse']);

        return view('ventes.ticket', ['vente' => $vente]);
    }

    /**
     * Les champs optionnels arrivent en chaîne vide depuis les <select>/<input>
     * du panier JS ; on les normalise en null pour que les règles "nullable"
     * fonctionnent (Laravel ne traite pas "" comme null).
     */
    private function nettoyerChampsOptionnels(Request $request): void
    {
        $lignes = collect($request->input('lignes', []))->map(function (array $ligne) {
            $ligne['unite_vente_id'] = $ligne['unite_vente_id'] ?: null;
            $ligne['remise_type'] = $ligne['remise_type'] ?: null;
            $ligne['remise_valeur'] = $ligne['remise_valeur'] ?: null;

            return $ligne;
        })->all();

        $request->merge([
            'lignes' => $lignes,
            'remise_totale_type' => $request->input('remise_totale_type') ?: null,
            'remise_totale_valeur' => $request->input('remise_totale_valeur') ?: null,
        ]);
    }
}
