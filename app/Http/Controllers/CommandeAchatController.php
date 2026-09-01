<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\CommandeAchat;
use App\Models\EcritureCompteFournisseur;
use App\Models\Fournisseur;
use App\Models\Magasin;
use App\Models\MoyenPaiement;
use App\Models\Parametre;
use App\Models\Produit;
use App\Models\Taxe;
use App\Models\UniteVente;
use App\Services\AchatService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommandeAchatController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $debut = $request->filled('date_debut') ? Carbon::parse($request->string('date_debut')) : now()->startOfMonth();
        $fin = $request->filled('date_fin') ? Carbon::parse($request->string('date_fin')) : now()->endOfMonth();

        $query = CommandeAchat::query()
            // lignes.taxe/paiements/reglementsFournisseur : nécessaires à
            // CommandeAchat::totalTtc()/montantRegle()/resteDu() (colonnes
            // Montant dû/Déjà réglé/Reste à régler), chargées ici pour
            // éviter un N+1 sur chaque ligne de la page.
            ->with(['fournisseur', 'lignes.taxe', 'paiements', 'reglementsFournisseur'])
            ->whereBetween('date_commande', [$debut->toDateString(), $fin->toDateString()])
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where('numero', 'like', "%{$recherche}%");
            })
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->string('statut')));

        $commandes = $this->appliquerTri($query, $request, ['numero', 'date_commande', 'statut'], 'created_at')
            ->paginate(20)
            ->withQueryString();

        return view('commande-achats.index', [
            'commandes' => $commandes,
            'dateDebut' => $debut->toDateString(),
            'dateFin' => $fin->toDateString(),
        ]);
    }

    public function create(): View
    {
        $produits = Produit::where('actif', true)->orderBy('nom')
            ->with(['uniteBase', 'uniteVentes' => fn ($q) => $q->where('actif', true)->with('unite')])
            ->get(['id', 'sku', 'nom', 'libelle_distinctif', 'unite_base_id']);

        return view('commande-achats.create', [
            'fournisseurs' => Fournisseur::where('actif', true)->orderBy('nom')->get(),
            // Solde en un seul agrégat (pas de N+1 par fournisseur) : un
            // solde négatif est un avoir, affiché à la saisie pour rappeler
            // qu'il se déduira automatiquement de la dette du nouvel achat.
            'fournisseurSoldes' => EcritureCompteFournisseur::selectRaw('fournisseur_id, SUM(montant) as solde')->groupBy('fournisseur_id')->pluck('solde', 'fournisseur_id'),
            'magasins' => Magasin::where('actif', true)->orderBy('nom')->get(),
            'taxes' => Taxe::where('actif', true)->orderBy('nom')->get(),
            'moyensPaiement' => MoyenPaiement::actifs(),
            'produits' => $produits,
            // Unités disponibles par produit (base + variantes déjà définies
            // au catalogue de vente) : évite de ressaisir un facteur à
            // l'achat qui pourrait diverger de celui utilisé à la vente.
            // Libellé affiché volontairement sans le facteur (nom complet +
            // abréviation entre parenthèses, ex. "Boîte (Bte)") — le facteur
            // reste utilisé en interne pour convertir en pièces, jamais
            // montré à la saisie.
            'unitesParProduit' => $produits->mapWithKeys(fn (Produit $p) => [
                $p->id => [
                    'basePiece' => $p->uniteBase?->nom_avec_abbreviation ?? 'pièce',
                    'variantes' => $p->uniteVentes->map(fn (UniteVente $uv) => [
                        'id' => $uv->id,
                        'libelle' => $uv->unite->nom_avec_abbreviation,
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
            'date_commande' => ['required', 'date'],
            'action' => ['required', 'in:brouillon,valider'],
        ]);

        // Un select laissé sur son option vide envoie "" (chaîne), pas
        // l'absence du champ : "nullable" ne l'exempte pas de la règle
        // "exists" (qui ne s'applique qu'à une valeur réellement null), donc
        // sans cette normalisation AVANT validate(), une unité/taxe non
        // choisie échouait avec "La valeur sélectionnée ... est invalide."
        $this->nettoyerLignes($request);

        // Pas de contrainte "distinct" sur produit_id seul : deux lignes du
        // même produit avec une unité de vente différente (pièce vs carton)
        // sont légitimes, seul le doublon (produit + unité) est bloqué côté
        // client (voir estDoublon dans commande-achats/create.blade.php),
        // même logique que le panier de vente.
        $lignes = $request->validate([
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.produit_id' => ['required', 'exists:produits,id'],
            'lignes.*.unite_vente_id' => ['nullable', 'exists:unite_ventes,id'],
            'lignes.*.taxe_id' => ['nullable', 'exists:taxes,id'],
            'lignes.*.magasin_destination_id' => ['required', 'exists:magasins,id'],
            'lignes.*.quantite' => ['required', 'integer', 'min:1'],
            'lignes.*.prix_achat' => ['required', 'integer', 'min:0'],
        ])['lignes'];

        $validerImmediatement = $donnees['action'] === 'valider';
        abort_if($validerImmediatement && ! $request->user()->can('achat.valider'), 403);
        unset($donnees['action']);

        $paiements = $validerImmediatement ? $request->validate([
            'paiements' => ['sometimes', 'array'],
            'paiements.*.moyen_paiement_id' => ['required_with:paiements', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required_with:paiements', 'integer', 'min:1'],
        ])['paiements'] ?? [] : [];

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
                $achatService->valider($commande, $request->user(), $paiements);
            } catch (RuntimeException|InvalidArgumentException $e) {
                return redirect()->route('commande-achats.show', $commande)->with('erreur', $e->getMessage());
            }

            return redirect()->route('commande-achats.show', $commande)
                ->with('succes', "Bon d'achat créé et validé : le stock a été mis à jour.");
        }

        $message = "Bon d'achat créé.".($numeroGenere ? " Numéro généré automatiquement : {$commande->numero}." : '');

        return redirect()->route('commande-achats.show', $commande)->with('succes', $message);
    }

    public function show(CommandeAchat $commandeAchat): View
    {
        $commandeAchat->load([
            'fournisseur', 'lignes.produit', 'lignes.uniteVente.unite', 'lignes.taxe', 'lignes.magasinDestination',
            'paiements.moyenPaiement', 'reglementsFournisseur.paiements.moyenPaiement', 'reglementsFournisseur.auteur',
            'retours.lignes.produit', 'retours.auteur',
            'auteur', 'validateur', 'annulateur',
        ]);

        // Reste retournable par ligne (quantite_pieces − déjà retourné) — une
        // seule passe sur les retours déjà chargés (voir RetourAchatService,
        // même logique de calcul, et VenteController::ticket()).
        $dejaRetourneParLigne = $commandeAchat->retours
            ->flatMap(fn ($retour) => $retour->lignes)
            ->groupBy('ligne_commande_achat_id')
            ->map(fn ($lignes) => $lignes->sum('quantite_pieces'));

        return view('commande-achats.show', [
            'commande' => $commandeAchat,
            'peutValider' => request()->user()->can('achat.valider'),
            'peutAnnuler' => request()->user()->can('achat.annuler'),
            'peutRegler' => request()->user()->can('fournisseur.reglement'),
            'peutRetourner' => request()->user()->can('achat.retour'),
            'dejaRetourneParLigne' => $dejaRetourneParLigne,
            'moyensPaiement' => MoyenPaiement::actifs(),
        ]);
    }

    public function facture(CommandeAchat $commandeAchat): View
    {
        return view('commande-achats.facture', $this->chargerDonneesFacture($commandeAchat));
    }

    public function pdf(Request $request, CommandeAchat $commandeAchat): Response
    {
        $pdf = Pdf::loadView('commande-achats.facture', $this->chargerDonneesFacture($commandeAchat) + ['pourPdf' => true]);

        // ?imprimer=1 (voir x-bouton-imprimer) : ouvre le PDF dans l'onglet
        // au lieu de forcer un téléchargement, pour que le bouton "Imprimer"
        // du détail du bon d'achat ne fasse jamais naviguer vers une autre
        // page (même mécanisme que ExporteListe::pdfDepuisListe()).
        $nomFichier = "bon-d-achat-{$commandeAchat->numero}.pdf";

        return $request->boolean('imprimer') ? $pdf->stream($nomFichier) : $pdf->download($nomFichier);
    }

    public function excel(CommandeAchat $commandeAchat): StreamedResponse
    {
        $commandeAchat->load(['fournisseur', 'lignes.produit', 'lignes.uniteVente.unite', 'lignes.taxe', 'lignes.magasinDestination', 'paiements.moyenPaiement', 'reglementsFournisseur']);

        $spreadsheet = new Spreadsheet();
        $feuille = $spreadsheet->getActiveSheet();
        $feuille->setTitle("Bon d'achat");

        $parametre = Parametre::actuel();
        $feuille->setCellValue('A1', $parametre->nom);
        $feuille->setCellValue('A2', $parametre->adresse);
        $feuille->setCellValue('A3', $parametre->numero ? 'Tél : '.$parametre->numero : '');

        $feuille->setCellValue('D1', "BON D'ACHAT");
        $feuille->setCellValue('D2', 'N° '.$commandeAchat->numero);
        $feuille->setCellValue('D3', 'Date : '.$commandeAchat->date_commande->format('d/m/Y'));

        $feuille->setCellValue('A6', 'Fournisseur');
        $feuille->setCellValue('A7', $commandeAchat->fournisseur->nom);
        $feuille->setCellValue('A8', $commandeAchat->fournisseur->telephone ?? '');
        $feuille->setCellValue('A9', $commandeAchat->fournisseur->adresse ?? '');

        $ligneEnTete = 11;
        foreach (['A' => 'Désignation', 'B' => 'Unité', 'C' => 'Destination', 'D' => 'Quantité', 'E' => 'Prix HT', 'F' => 'Taxe', 'G' => 'Total HT', 'H' => 'Total TTC'] as $colonne => $libelle) {
            $feuille->setCellValue("{$colonne}{$ligneEnTete}", $libelle);
        }

        $ligne = $ligneEnTete + 1;
        foreach ($commandeAchat->lignes as $ligneAchat) {
            $feuille->setCellValue("A{$ligne}", $ligneAchat->produit->libelle_affichage);
            $feuille->setCellValue("B{$ligne}", $ligneAchat->uniteVente->unite->nom_avec_abbreviation ?? $ligneAchat->produit->unite_base_libelle);
            $feuille->setCellValue("C{$ligne}", $ligneAchat->magasinDestination->nom);
            $feuille->setCellValue("D{$ligne}", $ligneAchat->quantite);
            $feuille->setCellValue("E{$ligne}", $ligneAchat->prix_achat);
            $feuille->setCellValue("F{$ligne}", $ligneAchat->taxe->nom ?? '—');
            $feuille->setCellValue("G{$ligne}", $ligneAchat->montantHt());
            $feuille->setCellValue("H{$ligne}", $ligneAchat->montantTtc());
            $ligne++;
        }

        $ligne++;
        $feuille->setCellValue("F{$ligne}", 'Total HT');
        $feuille->setCellValue("G{$ligne}", $commandeAchat->totalHt());
        $ligne++;
        $feuille->setCellValue("F{$ligne}", 'Total taxes');
        $feuille->setCellValue("G{$ligne}", $commandeAchat->totalTaxes());
        $ligne++;
        $feuille->setCellValue("F{$ligne}", 'Total TTC');
        $feuille->setCellValue("G{$ligne}", $commandeAchat->totalTtc());
        $ligne++;
        foreach ($commandeAchat->paiements as $paiement) {
            $feuille->setCellValue("F{$ligne}", $paiement->moyenPaiement->nom);
            $feuille->setCellValue("G{$ligne}", $paiement->montant);
            $ligne++;
        }
        if ($commandeAchat->montantRegle() > 0) {
            $feuille->setCellValue("F{$ligne}", 'Montant réglé');
            $feuille->setCellValue("G{$ligne}", $commandeAchat->montantRegle());
            $ligne++;
        }
        if ($commandeAchat->resteDu() > 0) {
            $feuille->setCellValue("F{$ligne}", 'Reste dû au fournisseur');
            $feuille->setCellValue("G{$ligne}", $commandeAchat->resteDu());
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $colonne) {
            $feuille->getColumnDimension($colonne)->setAutoSize(true);
        }

        $nomFichier = "bon-d-achat-{$commandeAchat->numero}.xlsx";
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nomFichier, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * Logo encodé en data URI (comme pour la vente/le devis) : dompdf ne
     * charge une image distante que si enable_remote est activé côté serveur.
     */
    private function chargerDonneesFacture(CommandeAchat $commandeAchat): array
    {
        $commandeAchat->load(['fournisseur', 'lignes.produit', 'lignes.uniteVente.unite', 'lignes.taxe', 'lignes.magasinDestination', 'paiements.moyenPaiement', 'reglementsFournisseur']);

        $parametre = Parametre::actuel();
        $logo = $parametre->getFirstMedia('logo');
        $logoDataUri = ($logo && is_file($logo->getPath()))
            ? 'data:'.$logo->mime_type.';base64,'.base64_encode(file_get_contents($logo->getPath()))
            : null;

        return [
            'commande' => $commandeAchat,
            'parametre' => $parametre,
            'logoDataUri' => $logoDataUri,
        ];
    }

    public function valider(Request $request, CommandeAchat $commandeAchat, AchatService $achatService): RedirectResponse
    {
        abort_unless($request->user()->can('achat.valider'), 403);

        $paiements = $request->validate([
            'paiements' => ['sometimes', 'array'],
            'paiements.*.moyen_paiement_id' => ['required_with:paiements', 'exists:moyen_paiements,id'],
            'paiements.*.montant' => ['required_with:paiements', 'integer', 'min:1'],
        ])['paiements'] ?? [];

        try {
            $achatService->valider($commandeAchat, $request->user(), $paiements);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return redirect()->route('commande-achats.show', $commandeAchat)->with('erreur', $e->getMessage());
        }

        return redirect()->route('commande-achats.show', $commandeAchat)->with('succes', 'Commande validée : le stock a été mis à jour.');
    }

    public function destroy(Request $request, CommandeAchat $commandeAchat): RedirectResponse
    {
        abort_unless($request->user()->can('achat.annuler'), 403);

        if ($commandeAchat->statut !== 'brouillon') {
            return redirect()->route('commande-achats.index')->with('erreur', 'Seule une commande en brouillon peut être supprimée : une commande validée a déjà mis à jour le stock, utilisez « Annuler le bon d\'achat » à la place.');
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

    private function nettoyerLignes(Request $request): void
    {
        $lignes = collect($request->input('lignes', []))->map(function (array $ligne) {
            $ligne['unite_vente_id'] = ($ligne['unite_vente_id'] ?? null) ?: null;
            $ligne['taxe_id'] = ($ligne['taxe_id'] ?? null) ?: null;

            return $ligne;
        })->all();

        $request->merge(['lignes' => $lignes]);
    }
}
