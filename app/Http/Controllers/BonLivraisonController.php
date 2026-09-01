<?php

namespace App\Http\Controllers;

use App\Exceptions\QuantiteLivraisonInvalideException;
use App\Http\Controllers\Concerns\AutoriseMagasin;
use App\Models\BonLivraison;
use App\Models\Parametre;
use App\Models\Vente;
use App\Services\BonLivraisonService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class BonLivraisonController extends Controller
{
    use AutoriseMagasin;

    public function store(Request $request, Vente $vente, BonLivraisonService $service): RedirectResponse
    {
        $this->assurerMagasin($vente->magasin_id);

        $donnees = $request->validate([
            'motif' => ['nullable', 'string', 'max:500'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.ligne_vente_id' => ['required', Rule::exists('ligne_ventes', 'id')->where('vente_id', $vente->id)],
            'lignes.*.quantite_pieces' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $bonLivraison = $service->livrer($vente, $donnees['lignes'], $request->user(), $donnees['motif'] ?? null);
        } catch (QuantiteLivraisonInvalideException|InvalidArgumentException $e) {
            return back()->withInput()->with('erreur', $e->getMessage());
        }

        return redirect()->route('ventes.ticket', $vente)
            ->with('succes', "Bon de livraison {$bonLivraison->numero} enregistré.");
    }

    public function annuler(Request $request, BonLivraison $bonLivraison, BonLivraisonService $service): RedirectResponse
    {
        $this->assurerMagasin($bonLivraison->vente->magasin_id);

        $donnees = $request->validate([
            'motif' => ['required', 'string', 'max:500'],
        ]);

        try {
            $service->annuler($bonLivraison, $request->user(), $donnees['motif']);
        } catch (InvalidArgumentException $e) {
            return back()->with('erreur', $e->getMessage());
        }

        return redirect()->route('ventes.ticket', $bonLivraison->vente)
            ->with('succes', 'Bon de livraison annulé.');
    }

    public function imprimer(BonLivraison $bonLivraison): View
    {
        $this->assurerMagasin($bonLivraison->vente->magasin_id);

        return view('bons-livraison.imprimer', $this->chargerDonnees($bonLivraison));
    }

    public function pdf(BonLivraison $bonLivraison): Response
    {
        $this->assurerMagasin($bonLivraison->vente->magasin_id);

        $pdf = Pdf::loadView('bons-livraison.imprimer', $this->chargerDonnees($bonLivraison) + ['pourPdf' => true]);

        $nomFichier = "bon-livraison-{$bonLivraison->numero}.pdf";

        return request()->boolean('imprimer') ? $pdf->stream($nomFichier) : $pdf->download($nomFichier);
    }

    private function chargerDonnees(BonLivraison $bonLivraison): array
    {
        $bonLivraison->load(['vente.client', 'vente.magasin', 'vente.caissier', 'lignes.produit', 'lignes.magasin', 'auteur']);

        $parametre = Parametre::actuel();
        $logo = $parametre->getFirstMedia('logo');
        $logoDataUri = ($logo && is_file($logo->getPath()))
            ? 'data:'.$logo->mime_type.';base64,'.base64_encode(file_get_contents($logo->getPath()))
            : null;

        return [
            'bonLivraison' => $bonLivraison,
            'parametre' => $parametre,
            'logoDataUri' => $logoDataUri,
        ];
    }
}
