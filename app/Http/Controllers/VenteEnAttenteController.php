<?php

namespace App\Http\Controllers;

use App\Exceptions\SessionNonOuverteException;
use App\Models\MoyenPaiement;
use App\Models\SessionCaisse;
use App\Models\VenteEnAttente;
use App\Services\VenteEnAttenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class VenteEnAttenteController extends Controller
{
    public function index(SessionCaisse $session): View
    {
        $ventesEnAttente = $session->venteEnAttentes()
            ->with(['lignes.produit', 'lignes.uniteVente'])
            ->latest()
            ->get();

        return view('ventes-en-attente.index', [
            'session' => $session,
            'ventesEnAttente' => $ventesEnAttente,
        ]);
    }

    public function store(Request $request, SessionCaisse $session, VenteEnAttenteService $venteEnAttenteService): RedirectResponse
    {
        $this->nettoyerLignes($request);

        $donnees = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'exists:produits,id'],
            'lignes.*.unite_vente_id' => ['nullable', 'exists:unite_ventes,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'libelle' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $venteEnAttenteService->mettreEnAttente(
                $session,
                $request->user(),
                $donnees['lignes'],
                $donnees['libelle'] ?? null,
            );
        } catch (SessionNonOuverteException|InvalidArgumentException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('sessions.show', $session)->with('succes', 'Vente mise en attente.');
    }

    public function show(VenteEnAttente $venteEnAttente): View
    {
        $venteEnAttente->load(['lignes.produit', 'lignes.uniteVente', 'sessionCaisse']);

        $lignesAffichage = $venteEnAttente->lignes->map(function ($ligne) {
            $prixUnitaire = $ligne->uniteVente?->prix ?? $ligne->produit->prix_piece;

            return [
                'libelle' => $ligne->uniteVente
                    ? "{$ligne->produit->nom} — {$ligne->uniteVente->libelle}"
                    : $ligne->produit->nom,
                'quantite' => $ligne->quantite,
                'prix_unitaire' => $prixUnitaire,
                'total' => $prixUnitaire * $ligne->quantite,
            ];
        });

        return view('ventes-en-attente.reprendre', [
            'venteEnAttente' => $venteEnAttente,
            'lignesAffichage' => $lignesAffichage,
            'sousTotal' => $lignesAffichage->sum('total'),
            'moyensPaiement' => MoyenPaiement::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function reprendre(Request $request, VenteEnAttente $venteEnAttente, VenteEnAttenteService $venteEnAttenteService): RedirectResponse
    {
        $request->merge([
            'remise_totale_type' => $request->input('remise_totale_type') ?: null,
            'remise_totale_valeur' => $request->input('remise_totale_valeur') ?: null,
        ]);

        $donnees = $request->validate([
            'remise_totale_type' => ['nullable', 'in:montant,pourcentage'],
            'remise_totale_valeur' => ['nullable', 'integer', 'min:0'],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
        ]);

        try {
            $vente = $venteEnAttenteService->reprendre(
                $venteEnAttente,
                $donnees['paiements'],
                $donnees['remise_totale_type'] ?? null,
                $donnees['remise_totale_valeur'] ?? null,
            );
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('ventes.ticket', $vente)->with('succes', 'Vente finalisée.');
    }

    public function annuler(VenteEnAttente $venteEnAttente, VenteEnAttenteService $venteEnAttenteService): RedirectResponse
    {
        $session = $venteEnAttente->sessionCaisse;

        $venteEnAttenteService->annuler($venteEnAttente);

        return redirect()->route('sessions.show', $session)->with('succes', 'Vente en attente annulée.');
    }

    private function nettoyerLignes(Request $request): void
    {
        $lignes = collect($request->input('lignes', []))->map(function (array $ligne) {
            $ligne['unite_vente_id'] = $ligne['unite_vente_id'] ?: null;

            return $ligne;
        })->all();

        $request->merge(['lignes' => $lignes]);
    }
}
