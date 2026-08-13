<?php

namespace App\Http\Controllers;

use App\Models\Caisse;
use App\Models\EcritureCompteFournisseur;
use App\Models\Magasin;
use App\Models\Paiement;
use App\Models\SessionCaisse;
use App\Models\Stock;
use App\Models\User;
use App\Models\Vente;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $utilisateur = $request->user();

        if ($utilisateur->hasRole('Caissier')) {
            return view('dashboard.caissier', ['utilisateur' => $utilisateur] + $this->donneesCaissier($utilisateur));
        }

        if ($utilisateur->magasin_id) {
            return view('dashboard.gerant', ['utilisateur' => $utilisateur] + $this->donneesMagasin($utilisateur->magasin_id));
        }

        return view('dashboard.superadmin', ['utilisateur' => $utilisateur]
            + $this->donneesMagasin(null)
            + ['comparatifMagasins' => $this->comparatifMagasins()]);
    }

    private function donneesCaissier(User $utilisateur): array
    {
        $session = SessionCaisse::where('caissier_id', $utilisateur->id)
            ->whereNull('date_fermeture')
            ->with('caisse.magasin')
            ->first();

        if (! $session) {
            return ['session' => null];
        }

        $nombreVentes = $session->ventes()->count();
        $totalVentes = (int) $session->ventes()->sum('total_net');

        $parMoyen = Paiement::query()
            ->join('ventes', 'ventes.id', '=', 'paiements.vente_id')
            ->join('moyen_paiements', 'moyen_paiements.id', '=', 'paiements.moyen_paiement_id')
            ->where('ventes.session_caisse_id', $session->id)
            ->whereNull('ventes.deleted_at')
            ->selectRaw('moyen_paiements.nom as nom, SUM(paiements.montant) as total')
            ->groupBy('moyen_paiements.id', 'moyen_paiements.nom')
            ->get();

        return [
            'session' => $session,
            'nombreVentes' => $nombreVentes,
            'totalVentes' => $totalVentes,
            'panierMoyen' => $nombreVentes > 0 ? (int) round($totalVentes / $nombreVentes) : 0,
            'parMoyen' => $parMoyen,
            // Le tableau de bord du caissier ne montre que ses propres
            // ventes en attente, jamais celles d'un autre caissier sur la
            // même caisse.
            'venteEnAttenteCount' => $session->venteEnAttentes()->where('caissier_id', $utilisateur->id)->count(),
        ];
    }

    private function donneesMagasin(?int $magasinId): array
    {
        $debutJour = Carbon::today();
        $debutSemaine = Carbon::now()->startOfWeek();
        $debutMois = Carbon::now()->startOfMonth();

        $caVentes = fn (Carbon $depuis) => (int) Vente::query()
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->where('created_at', '>=', $depuis)
            ->sum('total_net');

        $ventesMois = Vente::query()
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->where('created_at', '>=', $debutMois);

        $nombreVentesMois = (clone $ventesMois)->count();

        return [
            'caJour' => $caVentes($debutJour),
            'caSemaine' => $caVentes($debutSemaine),
            'caMois' => $caVentes($debutMois),
            'panierMoyenMois' => $nombreVentesMois > 0 ? (int) round((clone $ventesMois)->sum('total_net') / $nombreVentesMois) : 0,
            'topProduits' => $this->topProduits($magasinId, $debutMois),
            'produitsSousSeuil' => $this->produitsSousSeuil($magasinId),
            'produitsSousSeuilCount' => $this->produitsSousSeuilCount($magasinId),
            'valeurStock' => $this->valeurStock($magasinId),
            // Le fournisseur n'est pas rattaché à un magasin (référentiel
            // central, comme le catalogue) : la dette totale est la même
            // pour un gérant que pour le superadmin, jamais filtrée.
            'detteFournisseurs' => $this->detteFournisseurs(),
            'ecartsCaisse' => $this->ecartsCaisseRecents($magasinId),
            'caissesOuvertes' => $this->caissesOuvertes($magasinId),
            'evolutionVentes' => $this->evolutionVentes($magasinId),
            'repartitionMoyens' => $this->repartitionMoyensPaiement($magasinId, $debutMois),
        ];
    }

    private function topProduits(?int $magasinId, Carbon $depuis, int $limite = 5)
    {
        return DB::table('ligne_ventes')
            ->join('ventes', 'ventes.id', '=', 'ligne_ventes.vente_id')
            ->join('produits', 'produits.id', '=', 'ligne_ventes.produit_id')
            ->when($magasinId, fn ($q) => $q->where('ventes.magasin_id', $magasinId))
            ->where('ventes.created_at', '>=', $depuis)
            ->whereNull('ventes.deleted_at')
            ->selectRaw('produits.nom as nom, produits.libelle_distinctif as libelle_distinctif, produits.sku as sku, SUM(ligne_ventes.quantite_pieces) as pieces_vendues, SUM(ligne_ventes.total_ligne) as total')
            ->groupBy('produits.id', 'produits.nom', 'produits.libelle_distinctif', 'produits.sku')
            ->orderByDesc('total')
            ->limit($limite)
            ->get();
    }

    private function produitsSousSeuil(?int $magasinId, int $limite = 5)
    {
        return Stock::query()
            ->join('produits', 'produits.id', '=', 'stocks.produit_id')
            ->when($magasinId, fn ($q) => $q->where('stocks.magasin_id', $magasinId))
            ->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte')
            ->select('stocks.*')
            ->with(['produit', 'magasin'])
            ->orderBy('stocks.quantite')
            ->limit($limite)
            ->get();
    }

    private function produitsSousSeuilCount(?int $magasinId): int
    {
        return Stock::query()
            ->join('produits', 'produits.id', '=', 'stocks.produit_id')
            ->when($magasinId, fn ($q) => $q->where('stocks.magasin_id', $magasinId))
            ->whereColumn('stocks.quantite', '<=', 'produits.seuil_alerte')
            ->count();
    }

    private function valeurStock(?int $magasinId): int
    {
        return (int) Stock::query()
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->selectRaw('COALESCE(SUM(quantite * cout_moyen_pondere), 0) as valeur')
            ->value('valeur');
    }

    /**
     * Somme des écritures de tous les comptes fournisseurs (solde dérivé,
     * jamais stocké — même principe que le solde d'un compte client).
     */
    private function detteFournisseurs(): int
    {
        return (int) EcritureCompteFournisseur::sum('montant');
    }

    private function ecartsCaisseRecents(?int $magasinId, int $limite = 5)
    {
        return SessionCaisse::query()
            ->join('caisses', 'caisses.id', '=', 'session_caisses.caisse_id')
            ->when($magasinId, fn ($q) => $q->where('caisses.magasin_id', $magasinId))
            ->whereNotNull('session_caisses.date_cloture')
            ->where('session_caisses.ecart', '<>', 0)
            ->select('session_caisses.*')
            ->with(['caisse', 'caissier'])
            ->orderByDesc('session_caisses.date_cloture')
            ->limit($limite)
            ->get();
    }

    private function caissesOuvertes(?int $magasinId)
    {
        return Caisse::query()
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->whereHas('sessionCaisses', fn ($q) => $q->whereNull('date_fermeture'))
            ->with(['magasin', 'sessionCaisses' => fn ($q) => $q->whereNull('date_fermeture')->with('caissier')])
            ->get();
    }

    private function evolutionVentes(?int $magasinId, int $jours = 14): array
    {
        $depuis = Carbon::today()->subDays($jours - 1);

        $parJour = Vente::query()
            ->when($magasinId, fn ($q) => $q->where('magasin_id', $magasinId))
            ->where('created_at', '>=', $depuis)
            ->selectRaw('DATE(created_at) as jour, SUM(total_net) as total')
            ->groupBy('jour')
            ->pluck('total', 'jour');

        $labels = [];
        $valeurs = [];

        for ($i = 0; $i < $jours; $i++) {
            $date = $depuis->copy()->addDays($i);
            $labels[] = $date->format('d/m');
            $valeurs[] = (int) ($parJour[$date->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'valeurs' => $valeurs];
    }

    private function repartitionMoyensPaiement(?int $magasinId, Carbon $depuis)
    {
        return Paiement::query()
            ->join('ventes', 'ventes.id', '=', 'paiements.vente_id')
            ->join('moyen_paiements', 'moyen_paiements.id', '=', 'paiements.moyen_paiement_id')
            ->when($magasinId, fn ($q) => $q->where('ventes.magasin_id', $magasinId))
            ->where('ventes.created_at', '>=', $depuis)
            ->whereNull('ventes.deleted_at')
            ->selectRaw('moyen_paiements.nom as nom, SUM(paiements.montant) as total')
            ->groupBy('moyen_paiements.id', 'moyen_paiements.nom')
            ->get();
    }

    private function comparatifMagasins()
    {
        $debutMois = Carbon::now()->startOfMonth();

        return Magasin::query()
            ->withSum(['ventes' => fn ($q) => $q->where('created_at', '>=', $debutMois)], 'total_net')
            ->orderByDesc('ventes_sum_total_net')
            ->get();
    }
}
