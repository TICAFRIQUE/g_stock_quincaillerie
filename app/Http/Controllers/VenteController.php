<?php

namespace App\Http\Controllers;

use App\Enums\MouvementStockType;
use App\Exceptions\SessionNonOuverteException;
use App\Exceptions\StockInsuffisantException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Http\Controllers\Concerns\AutoriseVenteEnAttente;
use App\Http\Controllers\Concerns\ValideRemises;
use App\Models\MoyenPaiement;
use App\Models\Paiement;
use App\Models\Produit;
use App\Models\SessionCaisse;
use App\Models\Stock;
use App\Models\User;
use App\Models\Vente;
use App\Models\VenteEnAttente;
use App\Notifications\VenteSignalee;
use App\Services\StockService;
use App\Services\VenteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;

class VenteController extends Controller
{
    use ValideRemises, AutoriseVenteEnAttente, AutoriseMagasin;

    public function create(SessionCaisse $session): View
    {
        return $this->formulaire($session);
    }

    /**
     * Reprendre une vente en attente n'est pas un flux à part : c'est le même
     * écran de création de vente, avec le panier déjà rempli des lignes
     * existantes — comme si on rouvrait la vente pour la modifier avant de la
     * finaliser (prix courant, pas de figeage à la mise en attente).
     */
    public function reprendre(VenteEnAttente $venteEnAttente): View
    {
        $this->assurerProprietaireOuGerant($venteEnAttente);

        $venteEnAttente->load(['lignes.produit', 'lignes.uniteVente', 'sessionCaisse']);

        return $this->formulaire($venteEnAttente->sessionCaisse, $venteEnAttente);
    }

    private function formulaire(SessionCaisse $session, ?VenteEnAttente $venteEnAttente = null): View
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        abort_if($session->date_cloture || $session->date_fermeture, 403, 'Cette session n\'est plus ouverte.');

        $magasinId = $session->caisse->magasin_id;
        $stocksParProduit = Stock::where('magasin_id', $magasinId)->pluck('quantite', 'produit_id');

        // Le catalogue (données de référence) est mis en cache ; le stock,
        // dérivé des mouvements et donc jamais figé, est toujours recalculé
        // en direct puis fusionné à l'affichage.
        $produits = Produit::catalogueVente()
            ->map(fn (array $p) => [
                ...$p,
                'stock' => (int) ($stocksParProduit[$p['id']] ?? 0),
            ])
            ->values();

        $panierInitial = $venteEnAttente
            ? $venteEnAttente->lignes->map(fn ($ligne) => [
                'produit_id' => $ligne->produit_id,
                'unite_vente_id' => $ligne->unite_vente_id,
                'produitLibelle' => $ligne->produit->libelle_affichage,
                'uniteLibelle' => $ligne->uniteVente?->libelle,
                'facteur' => $ligne->uniteVente?->facteur ?? 1,
                'quantite' => $ligne->quantite,
                'prixUnitaire' => $ligne->uniteVente?->prix ?? $ligne->produit->prix_piece,
                'remise_type' => '',
                'remise_valeur' => null,
            ])->values()
            : collect();

        // Un caissier ne voit (et ne finalise) que ses propres ventes en
        // attente : le badge doit refléter ce même périmètre, pas le total
        // de la session (qui peut inclure des paniers d'un autre caissier
        // ou du gérant sur la même caisse).
        $venteEnAttentesCount = $session->venteEnAttentes()
            ->when(! request()->user()->can('caisse.gerer'), fn ($q) => $q->where('caissier_id', request()->user()->id))
            ->count();

        return view('ventes.create', [
            'session' => $session,
            'produits' => $produits,
            'moyensPaiement' => MoyenPaiement::actifs(),
            'venteEnAttentesCount' => $venteEnAttentesCount,
            'venteEnAttente' => $venteEnAttente,
            'panierInitial' => $panierInitial,
        ]);
    }

    public function store(Request $request, SessionCaisse $session, VenteService $venteService): RedirectResponse
    {
        $this->assurerMagasin($session->caisse->magasin_id);
        $this->nettoyerChampsOptionnels($request);
        $this->bloquerRemiseSansPermission($request);

        $donnees = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'exists:produits,id'],
            'lignes.*.unite_vente_id' => ['nullable', 'exists:unite_ventes,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.remise_type' => ['nullable', 'in:montant,pourcentage'],
            'lignes.*.remise_valeur' => ['nullable', 'integer', 'min:0', $this->remisePourcentageMax()],
            'remise_totale_type' => ['nullable', 'in:montant,pourcentage'],
            'remise_totale_valeur' => ['nullable', 'integer', 'min:0', $this->remisePourcentageMax()],
            'paiements' => ['required', 'array', 'min:1'],
            'paiements.*.moyen_paiement_id' => ['required', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required', 'integer', 'min:1'],
            'montant_recu' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $vente = $venteService->vendre(
                session: $session,
                caissier: $request->user(),
                lignes: $donnees['lignes'],
                paiements: $donnees['paiements'],
                remiseTotaleType: $donnees['remise_totale_type'] ?? null,
                remiseTotaleValeur: $donnees['remise_totale_valeur'] ?? null,
                montantRecu: $donnees['montant_recu'] ?? null,
            );
        } catch (StockInsuffisantException|SessionNonOuverteException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('ventes.ticket', $vente)->with('succes', 'Vente enregistrée.');
    }

    public function ticket(Vente $vente): View
    {
        $this->assurerMagasin($vente->magasin_id);

        $vente->load(['lignes.produit', 'lignes.uniteVente', 'paiements.moyenPaiement', 'magasin', 'caissier', 'sessionCaisse.caisse', 'annulateur']);

        $signalements = $vente->activities()
            ->where('description', 'like', '%» signalée :%')
            ->with('causer')
            ->latest()
            ->get();

        return view('ventes.ticket', ['vente' => $vente, 'signalements' => $signalements]);
    }

    /**
     * Ne fait que tracer un signalement (ex. doublon de saisie) dans le
     * journal d'activité et prévenir les gérants — ne touche ni au stock ni
     * aux montants. Charge au titulaire de la permission vente.annuler de
     * décider s'il y a lieu d'annuler (voir annuler() ci-dessous).
     */
    public function signaler(Request $request, Vente $vente): RedirectResponse
    {
        $this->assurerMagasin($vente->magasin_id);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ]);

        activity()
            ->causedBy($request->user())
            ->performedOn($vente)
            ->withProperties(['motif' => $donnees['motif']])
            ->log("Vente « {$vente->numero} » signalée : {$donnees['motif']}");

        User::gerantsEtSuperadmins($vente->magasin_id)->each(
            fn (User $destinataire) => $destinataire->notify(new VenteSignalee($vente, $donnees['motif']))
        );

        return redirect()->route('ventes.ticket', $vente)
            ->with('succes', 'Vente signalée. Le gérant peut ajuster la caisse ou le stock manuellement si besoin.');
    }

    /**
     * Annule une vente (erreur de saisie — pas un retour client, voir
     * discussion CLAUDE.md). La vente n'est ni supprimée ni modifiée dans
     * son contenu : elle est marquée annulée (soft delete + motif + auteur),
     * reste consultable dans l'historique, et un mouvement de stock inverse
     * (immuable) restitue exactement ce qui avait été décrémenté par la
     * vente. Exclue automatiquement des CA/rapports par le scope par défaut
     * du modèle Vente (soft deleted).
     */
    public function annuler(Request $request, Vente $vente, StockService $stockService): RedirectResponse
    {
        $this->assurerMagasin($vente->magasin_id);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ]);

        $vente->load('lignes.produit', 'magasin');

        DB::transaction(function () use ($vente, $donnees, $request, $stockService) {
            foreach ($vente->lignes as $ligne) {
                $stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $vente->magasin,
                    quantite: $ligne->quantite_pieces,
                    type: MouvementStockType::Annulation,
                    auteur: $request->user(),
                    reference: $vente,
                    motif: $donnees['motif'],
                );
            }

            $vente->motif_annulation = $donnees['motif'];
            $vente->annulee_par = $request->user()->id;
            $vente->save();
            $vente->delete();
        });

        activity()
            ->causedBy($request->user())
            ->performedOn($vente)
            ->withProperties(['motif' => $donnees['motif']])
            ->log("Vente « {$vente->numero} » annulée : {$donnees['motif']}");

        return redirect()->route('ventes.ticket', $vente)
            ->with('succes', 'Vente annulée. Le stock a été remis à jour.');
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
