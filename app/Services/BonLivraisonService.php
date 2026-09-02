<?php

namespace App\Services;

use App\Exceptions\QuantiteLivraisonInvalideException;
use App\Models\BonLivraison;
use App\Models\LigneBonLivraison;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Bon de livraison : toujours lié à une vente précise, ligne par ligne,
 * plafonné à la quantité vendue restant à livrer sur chaque ligne — même
 * principe de cumul que RetourVenteService, mais sans aucun impact
 * stock/caisse (le stock a déjà bougé à la vente, règle 3 du CLAUDE.md) et
 * annulable (contrairement à un retour), voir CLAUDE.md section Bons de
 * livraison.
 */
class BonLivraisonService
{
    /**
     * @param  array<int, array{ligne_vente_id:int, quantite_pieces:int|float}>  $lignes
     */
    public function livrer(Vente $vente, array $lignes, User $auteur, ?string $motif = null): BonLivraison
    {
        if ($vente->trashed()) {
            throw new InvalidArgumentException('Impossible d\'enregistrer une livraison sur une vente annulée.');
        }

        $lignes = array_values(array_filter($lignes, fn (array $l) => ($l['quantite_pieces'] ?? 0) > 0));

        if (empty($lignes)) {
            throw new InvalidArgumentException('Un bon de livraison doit comporter au moins une ligne avec une quantité positive.');
        }

        $vente->loadMissing('lignes.produit', 'lignes.magasinSource');

        // Une seule requête groupée pour les quantités déjà livrées de toutes
        // les lignes de la vente (zéro N+1 — règle Performance). whereHas()
        // applique déjà le scope global SoftDeletingScope de BonLivraison :
        // un BL annulé n'y compte pas, pas besoin de filtre deleted_at ici.
        $dejaLivre = LigneBonLivraison::whereIn('ligne_vente_id', $vente->lignes->pluck('id'))
            ->whereHas('bonLivraison')
            ->selectRaw('ligne_vente_id, SUM(quantite_pieces) as total')
            ->groupBy('ligne_vente_id')
            ->pluck('total', 'ligne_vente_id');

        return DB::transaction(function () use ($vente, $lignes, $auteur, $motif, $dejaLivre) {
            $bonLivraison = BonLivraison::create([
                'numero' => $this->genererNumero($vente),
                'vente_id' => $vente->id,
                'motif' => $motif,
                'created_by' => $auteur->id,
            ]);

            foreach ($lignes as $demande) {
                $ligneVente = $vente->lignes->firstWhere('id', $demande['ligne_vente_id']);

                if (! $ligneVente) {
                    throw new InvalidArgumentException('Ligne de vente introuvable sur cette facture.');
                }

                $quantiteAvant = (float) ($dejaLivre[$ligneVente->id] ?? 0);
                $quantiteRestante = (float) $ligneVente->quantite_pieces - $quantiteAvant;
                $quantiteLivree = (float) $demande['quantite_pieces'];

                if ($quantiteLivree > $quantiteRestante) {
                    throw new QuantiteLivraisonInvalideException($ligneVente, $quantiteLivree, $quantiteRestante);
                }

                $bonLivraison->lignes()->create([
                    'ligne_vente_id' => $ligneVente->id,
                    'produit_id' => $ligneVente->produit_id,
                    'magasin_id' => $ligneVente->magasin_source_id,
                    'quantite_pieces' => $quantiteLivree,
                ]);

                // Pour rester cohérent si le même formulaire référence deux
                // fois la même ligne (rejeu improbable mais pas impossible).
                $dejaLivre[$ligneVente->id] = $quantiteAvant + $quantiteLivree;
            }

            return $bonLivraison->fresh('lignes');
        });
    }

    public function annuler(BonLivraison $bonLivraison, User $auteur, string $motif): void
    {
        if ($bonLivraison->trashed()) {
            throw new InvalidArgumentException('Ce bon de livraison est déjà annulé.');
        }

        $bonLivraison->motif_annulation = $motif;
        $bonLivraison->annulee_par = $auteur->id;
        $bonLivraison->save();
        $bonLivraison->delete();
    }

    private function genererNumero(Vente $vente): string
    {
        // Compte les BL déjà créés pour cette vente, annulés inclus, pour ne
        // jamais réattribuer un numéro déjà pris (withTrashed()).
        $rang = $vente->bonsLivraison()->withTrashed()->count() + 1;

        return "{$vente->numero}-L{$rang}";
    }
}
