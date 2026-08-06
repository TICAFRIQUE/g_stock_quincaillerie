<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\CommandeAchat;
use App\Models\Fournisseur;
use App\Models\Magasin;
use App\Models\Produit;
use App\Models\UniteVente;
use App\Services\AchatService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class CommandeAchatController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $debut = $request->filled('date_debut') ? Carbon::parse($request->string('date_debut')) : now()->startOfMonth();
        $fin = $request->filled('date_fin') ? Carbon::parse($request->string('date_fin')) : now()->endOfMonth();

        $query = CommandeAchat::query()
            ->with(['fournisseur', 'magasin'])
            ->whereBetween('date_commande', [$debut->toDateString(), $fin->toDateString()])
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('numero', 'like', "%{$recherche}%");
            })
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')))
            ->when($request->filled('magasin_id'), fn ($q) => $q->where('magasin_id', $request->integer('magasin_id')));

        $commandes = $this->appliquerTri($query, $request, ['numero', 'date_commande', 'statut'], 'created_at')
            ->paginate(20)
            ->withQueryString();

        return view('commande-achats.index', [
            'commandes' => $commandes,
            'dateDebut' => $debut->toDateString(),
            'dateFin' => $fin->toDateString(),
            'magasins' => Magasin::orderBy('nom')->get(),
        ]);
    }

    public function create(): View
    {
        $produits = Produit::where('actif', true)->orderBy('nom')
            ->with(['uniteBase', 'uniteVentes' => fn ($q) => $q->where('actif', true)->with('unite')])
            ->get(['id', 'sku', 'nom', 'libelle_distinctif', 'unite_base_id']);

        return view('commande-achats.create', [
            'fournisseurs' => Fournisseur::where('actif', true)->orderBy('nom')->get(),
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
            'produits' => $produits,
            // Unités disponibles par produit (base + variantes déjà définies
            // au catalogue de vente) : évite de ressaisir un facteur à
            // l'achat qui pourrait diverger de celui utilisé à la vente.
            'unitesParProduit' => $produits->mapWithKeys(fn (Produit $p) => [
                $p->id => [
                    'basePiece' => $p->unite_base_libelle,
                    'variantes' => $p->uniteVentes->map(fn (UniteVente $uv) => [
                        'id' => $uv->id,
                        'libelle' => $uv->libelle,
                        'facteur' => $uv->facteur,
                    ])->values(),
                ],
            ]),
            'peutValider' => request()->user()->can('achat.valider'),
        ]);
    }

    public function store(Request $request, AchatService $achatService): RedirectResponse
    {
        $donnees = $request->validate([
            'numero' => ['nullable', 'string', 'max:255', 'unique:commande_achats,numero'],
            'fournisseur_id' => ['required', 'exists:fournisseurs,id'],
            'magasin_id' => ['required', 'exists:magasins,id'],
            'date_commande' => ['required', 'date'],
            'action' => ['required', 'in:brouillon,valider'],
        ]);

        $lignes = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'distinct', 'exists:produits,id'],
            'lignes.*.unite_vente_id' => ['nullable', 'exists:unite_ventes,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_achat' => ['required', 'integer', 'min:0'],
        ])['lignes'];

        foreach ($lignes as &$ligne) {
            $ligne['unite_vente_id'] = $ligne['unite_vente_id'] ?: null;
        }
        unset($ligne);

        $validerImmediatement = $donnees['action'] === 'valider';
        abort_if($validerImmediatement && ! $request->user()->can('achat.valider'), 403);
        unset($donnees['action']);

        $numeroGenere = blank($donnees['numero']);
        if ($numeroGenere) {
            $donnees['numero'] = $this->genererNumero();
        }

        $commande = DB::transaction(function () use ($donnees, $lignes, $request) {
            $commande = CommandeAchat::create($donnees + [
                'statut' => 'brouillon',
                'created_by' => $request->user()->id,
            ]);

            foreach ($lignes as $ligne) {
                $commande->lignes()->create($ligne);
            }

            return $commande;
        });

        if ($validerImmediatement) {
            try {
                $achatService->valider($commande, $request->user());
            } catch (RuntimeException $e) {
                return redirect()->route('commande-achats.show', $commande)->with('erreur', $e->getMessage());
            }

            return redirect()->route('commande-achats.show', $commande)
                ->with('succes', 'Commande d\'achat créée et validée : le stock a été mis à jour.');
        }

        $message = 'Commande d\'achat créée.'.($numeroGenere ? " Numéro généré automatiquement : {$commande->numero}." : '');

        return redirect()->route('commande-achats.show', $commande)->with('succes', $message);
    }

    public function show(CommandeAchat $commandeAchat): View
    {
        $commandeAchat->load(['fournisseur', 'magasin', 'lignes.produit', 'lignes.uniteVente', 'auteur', 'validateur', 'annulateur']);

        return view('commande-achats.show', [
            'commande' => $commandeAchat,
            'peutValider' => request()->user()->can('achat.valider'),
            'peutAnnuler' => request()->user()->can('achat.annuler'),
        ]);
    }

    public function valider(Request $request, CommandeAchat $commandeAchat, AchatService $achatService): RedirectResponse
    {
        abort_unless($request->user()->can('achat.valider'), 403);

        try {
            $achatService->valider($commandeAchat, $request->user());
        } catch (RuntimeException $e) {
            return redirect()->route('commande-achats.show', $commandeAchat)->with('erreur', $e->getMessage());
        }

        return redirect()->route('commande-achats.show', $commandeAchat)->with('succes', 'Commande validée : le stock a été mis à jour.');
    }

    public function destroy(Request $request, CommandeAchat $commandeAchat): RedirectResponse
    {
        abort_unless($request->user()->can('achat.annuler'), 403);

        if ($commandeAchat->statut !== 'brouillon') {
            return redirect()->route('commande-achats.index')->with('erreur', 'Seule une commande en brouillon peut être supprimée : une commande validée a déjà mis à jour le stock, utilisez « Annuler l\'achat » à la place.');
        }

        $commandeAchat->delete();

        return redirect()->route('commande-achats.index')->with('succes', 'Commande supprimée.');
    }

    /**
     * Annule une commande validée (erreur de saisie, réception refusée…) —
     * pas une suppression : voir AchatService::annuler(). Le stock est
     * remis à jour automatiquement, échoue proprement si une partie a déjà
     * été consommée ailleurs.
     */
    public function annuler(Request $request, CommandeAchat $commandeAchat, AchatService $achatService): RedirectResponse
    {
        abort_unless($request->user()->can('achat.annuler'), 403);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ]);

        try {
            $achatService->annuler($commandeAchat, $request->user(), $donnees['motif']);
        } catch (RuntimeException $e) {
            return redirect()->route('commande-achats.show', $commandeAchat)->with('erreur', $e->getMessage());
        }

        return redirect()->route('commande-achats.index')->with('succes', "Commande annulée : le stock a été mis à jour.");
    }

    private function genererNumero(): string
    {
        do {
            $numero = 'BC-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (CommandeAchat::where('numero', $numero)->exists());

        return $numero;
    }
}
