<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ExporteListe;
use App\Http\Controllers\Concerns\TrieListe;
use App\Models\CommandeAchat;
use App\Models\EcritureCompteFournisseur;
use App\Models\Fournisseur;
use App\Models\MoyenPaiement;
use App\Models\ReglementFournisseur;
use App\Models\RemboursementAvoirFournisseur;
use App\Models\RetourAchat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurController extends Controller
{
    use TrieListe, ExporteListe;

    public function index(Request $request): View
    {
        $query = Fournisseur::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where(function ($sub) use ($recherche) {
                    $sub->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('telephone', 'like', "%{$recherche}%")
                        ->orWhere('email', 'like', "%{$recherche}%");
                });
            });

        $query = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at']);

        // L'impression (voir x-bouton-imprimer) couvre tout le résultat
        // filtré, pas seulement la page affichée à l'écran.
        $fournisseurs = $request->boolean('tout') ? $query->get() : $query->paginate(20)->withQueryString();

        // Le solde est dérivé (mirroring ClientController::index()) : calculé
        // en une seule requête groupée plutôt qu'une somme par ligne.
        $soldes = EcritureCompteFournisseur::whereIn('fournisseur_id', $fournisseurs->pluck('id'))
            ->selectRaw('fournisseur_id, SUM(montant) as solde')
            ->groupBy('fournisseur_id')
            ->pluck('solde', 'fournisseur_id');

        return view('fournisseurs.index', ['fournisseurs' => $fournisseurs, 'soldes' => $soldes]);
    }

    public function pdf(Request $request): Response
    {
        return $this->pdfDepuisListe(
            'Fournisseurs',
            ['Code', 'Nom', 'Téléphone', 'E-mail', 'Solde dû', 'Statut'],
            $this->lignesExport($request),
            'fournisseurs.pdf',
        );
    }

    public function excel(Request $request): StreamedResponse
    {
        return $this->excelDepuisListe(
            'Fournisseurs',
            ['Code', 'Nom', 'Téléphone', 'E-mail', 'Solde dû', 'Statut'],
            $this->lignesExport($request),
            'fournisseurs.xlsx',
        );
    }

    private function lignesExport(Request $request): \Illuminate\Support\Collection
    {
        $query = Fournisseur::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where(function ($sub) use ($recherche) {
                    $sub->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('telephone', 'like', "%{$recherche}%")
                        ->orWhere('email', 'like', "%{$recherche}%");
                });
            });

        $fournisseurs = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at'], 'nom', 'asc')->get();

        $soldes = EcritureCompteFournisseur::whereIn('fournisseur_id', $fournisseurs->pluck('id'))
            ->selectRaw('fournisseur_id, SUM(montant) as solde')
            ->groupBy('fournisseur_id')
            ->pluck('solde', 'fournisseur_id');

        return $fournisseurs->map(fn (Fournisseur $f) => [
            $f->code,
            $f->nom,
            $f->telephone ?? '—',
            $f->email ?? '—',
            number_format($soldes[$f->id] ?? 0, 0, ',', ' ').' F',
            $f->actif ? 'Actif' : 'Inactif',
        ]);
    }

    public function show(Fournisseur $fournisseur): View
    {
        $fournisseur->loadCount('commandeAchats');

        // Colonne "Référence" : une écriture achat_credit référence directement
        // une CommandeAchat ; une écriture reglement référence un
        // ReglementFournisseur, dont on a besoin de la commande éventuellement
        // imputée pour l'afficher (voir CommandeAchat::resteDu() pour le
        // premier cas — évite un N+1).
        $ecritures = $fournisseur->ecritures()
            ->with(['auteur', 'reference' => function ($morphTo) {
                $morphTo->morphWith([
                    CommandeAchat::class => ['lignes', 'paiements', 'reglementsFournisseur'],
                    ReglementFournisseur::class => ['commandeAchat'],
                    RetourAchat::class => ['lignes.produit'],
                    RemboursementAvoirFournisseur::class => ['paiements'],
                ]);
            }])
            ->latest('created_at')
            ->paginate(15, ['*'], 'ecritures_page');

        $commandes = $fournisseur->commandeAchats()
            ->withTrashed()
            ->with(['lignes.taxe', 'lignes.magasinDestination', 'paiements', 'reglementsFournisseur'])
            ->latest('created_at')
            ->paginate(10, ['*'], 'commandes_page');

        return view('fournisseurs.show', [
            'fournisseur' => $fournisseur,
            'solde' => $fournisseur->solde(),
            'totalAchats' => $fournisseur->totalAchats(),
            'nombreAchats' => $fournisseur->nombreAchats(),
            'totalRegle' => $fournisseur->totalRegle(),
            'ecritures' => $ecritures,
            'commandes' => $commandes,
            'moyensPaiement' => MoyenPaiement::actifs(),
        ]);
    }

    public function create(): View
    {
        return view('fournisseurs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);
        $codeGenere = blank($donnees['code']);

        if ($codeGenere) {
            $donnees['code'] = $this->genererCode();
        }

        $fournisseur = Fournisseur::create($donnees);

        $message = 'Fournisseur créé.'.($codeGenere ? " Code généré automatiquement : {$fournisseur->code}." : '');

        return redirect()->route('fournisseurs.index')->with('succes', $message);
    }

    public function edit(Fournisseur $fournisseur): View
    {
        return view('fournisseurs.edit', ['fournisseur' => $fournisseur]);
    }

    public function update(Request $request, Fournisseur $fournisseur): RedirectResponse
    {
        $donnees = $this->valider($request, $fournisseur);
        $codeGenere = blank($donnees['code']);

        if ($codeGenere) {
            $donnees['code'] = $this->genererCode();
        }

        $fournisseur->update($donnees);

        $message = 'Fournisseur mis à jour.'.($codeGenere ? " Code généré automatiquement : {$fournisseur->code}." : '');

        return redirect()->route('fournisseurs.index')->with('succes', $message);
    }

    public function destroy(Fournisseur $fournisseur): RedirectResponse
    {
        if ($fournisseur->commandeAchats()->exists()) {
            return redirect()->route('fournisseurs.index')->with('erreur', 'Ce fournisseur a des commandes d\'achat, il ne peut pas être supprimé.');
        }

        $fournisseur->delete();

        return redirect()->route('fournisseurs.index')->with('succes', 'Fournisseur supprimé.');
    }

    private function valider(Request $request, ?Fournisseur $fournisseur = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('fournisseurs', 'code')->ignore($fournisseur?->id)],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'actif' => ['boolean'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }

    private function genererCode(): string
    {
        do {
            $code = 'FRN-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Fournisseur::withTrashed()->where('code', $code)->exists());

        return $code;
    }
}
