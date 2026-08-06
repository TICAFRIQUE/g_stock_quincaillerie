<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\TrieListe;
use App\Models\Client;
use App\Models\EcritureCompteClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientController extends Controller
{
    use TrieListe;

    public function index(Request $request): View
    {
        $query = Client::query()
            ->when($request->filled('recherche'), function ($q) use ($request) {
                $recherche = $request->string('recherche');
                $q->where(function ($sub) use ($recherche) {
                    $sub->where('nom', 'like', "%{$recherche}%")
                        ->orWhere('telephone', 'like', "%{$recherche}%");
                });
            });

        $clients = $this->appliquerTri($query, $request, ['nom', 'actif', 'created_at'])
            ->paginate(20)
            ->withQueryString();

        // Le solde est dérivé (règle 12) : calculé en une seule requête
        // groupée plutôt qu'une somme par ligne (évite un N+1 sur la page).
        $soldes = EcritureCompteClient::whereIn('client_id', $clients->pluck('id'))
            ->selectRaw('client_id, SUM(montant) as solde')
            ->groupBy('client_id')
            ->pluck('solde', 'client_id');

        return view('clients.index', ['clients' => $clients, 'soldes' => $soldes]);
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->valider($request);

        Client::create($donnees);

        return redirect()->route('clients.index')->with('succes', 'Client créé.');
    }

    /**
     * Création rapide depuis l'écran de vente ou de devis : juste le nom
     * (obligatoire) et le téléphone, pour ne pas interrompre la saisie en
     * cours. Le client reste modifiable ensuite depuis sa fiche complète.
     *
     * Valide manuellement (plutôt que $request->validate()) : cet endpoint
     * n'est appelé qu'en AJAX et doit toujours répondre en JSON, y compris
     * en cas d'échec — la détection automatique du format par Laravel
     * dépend de l'en-tête Accept envoyé par l'appelant, un point de
     * fragilité qu'on préfère éviter ici.
     */
    public function storeRapide(Request $request): JsonResponse
    {
        $validateur = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validateur->fails()) {
            return response()->json(['errors' => $validateur->errors()], 422);
        }

        $client = Client::create($validateur->validated() + ['actif' => true]);

        return response()->json([
            'id' => $client->id,
            'nom' => $client->nom,
            'telephone' => $client->telephone,
        ], 201);
    }

    public function show(Client $client): View
    {
        $client->loadCount('ventes');

        $ecritures = $client->ecritures()
            ->with('auteur')
            ->latest('created_at')
            ->paginate(15, ['*'], 'ecritures_page');

        // withTrashed() : une vente annulée reste visible dans l'historique
        // du client (avec badge), jamais masquée silencieusement — même
        // logique que le ticket de vente.
        $ventes = $client->ventes()
            ->withTrashed()
            ->with('magasin')
            ->latest('created_at')
            ->paginate(10, ['*'], 'ventes_page');

        $devis = $client->devis()
            ->with('magasin')
            ->latest('created_at')
            ->paginate(10, ['*'], 'devis_page');

        return view('clients.show', [
            'client' => $client,
            'solde' => $client->solde(),
            'ecritures' => $ecritures,
            'ventes' => $ventes,
            'devis' => $devis,
        ]);
    }

    public function exporterVentes(Client $client): StreamedResponse
    {
        $ventes = $client->ventes()->withTrashed()->with('magasin')->orderBy('created_at')->get();

        $spreadsheet = new Spreadsheet();
        $feuille = $spreadsheet->getActiveSheet();
        $feuille->setTitle('Ventes');
        $feuille->fromArray(['Numéro', 'Date', 'Magasin', 'Total net', 'Statut'], null, 'A1');

        $ligne = 2;
        foreach ($ventes as $vente) {
            $feuille->setCellValue("A{$ligne}", $vente->numero);
            $feuille->setCellValue("B{$ligne}", $vente->created_at->format('d/m/Y H:i'));
            $feuille->setCellValue("C{$ligne}", $vente->magasin->nom);
            $feuille->setCellValue("D{$ligne}", $vente->total_net);
            $feuille->setCellValue("E{$ligne}", $vente->trashed() ? 'Annulée' : 'Validée');
            $ligne++;
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $colonne) {
            $feuille->getColumnDimension($colonne)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $nomFichier = 'ventes-'.Str::slug($client->nom).'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nomFichier, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function exporterDevis(Client $client): StreamedResponse
    {
        $devis = $client->devis()->with('magasin')->orderBy('created_at')->get();

        $spreadsheet = new Spreadsheet();
        $feuille = $spreadsheet->getActiveSheet();
        $feuille->setTitle('Devis');
        $feuille->fromArray(['Numéro', 'Date', 'Magasin', 'Statut', 'Valide jusqu\'au'], null, 'A1');

        $ligne = 2;
        foreach ($devis as $unDevis) {
            $feuille->setCellValue("A{$ligne}", $unDevis->numero);
            $feuille->setCellValue("B{$ligne}", $unDevis->created_at->format('d/m/Y H:i'));
            $feuille->setCellValue("C{$ligne}", $unDevis->magasin->nom);
            $feuille->setCellValue("D{$ligne}", $unDevis->statutEffectif()->libelle());
            $feuille->setCellValue("E{$ligne}", $unDevis->date_validite->format('d/m/Y'));
            $ligne++;
        }

        foreach (['A', 'B', 'C', 'D', 'E'] as $colonne) {
            $feuille->getColumnDimension($colonne)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $nomFichier = 'devis-'.Str::slug($client->nom).'.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $nomFichier, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', ['client' => $client]);
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $donnees = $this->valider($request, $client);

        $client->update($donnees);

        return redirect()->route('clients.index')->with('succes', 'Client mis à jour.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->ventes()->exists()) {
            return redirect()->route('clients.index')->with('erreur', 'Ce client a des ventes, il ne peut pas être supprimé.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('succes', 'Client supprimé.');
    }

    private function valider(Request $request, ?Client $client = null): array
    {
        $donnees = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'limite_credit' => ['nullable', 'integer', 'min:0'],
            'actif' => ['boolean'],
        ]);

        $donnees['actif'] = $request->boolean('actif', true);

        return $donnees;
    }
}
