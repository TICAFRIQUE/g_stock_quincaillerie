<?php

namespace App\Services;

use App\Enums\MouvementStockType;
use App\Models\Inventaire;
use App\Models\LigneInventaire;
use App\Models\Produit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventaireService
{
    public function __construct(private readonly StockService $stockService) {}

    /**
     * Snapshot du théorique au moment du comptage : l'écart ne se recalcule
     * jamais en live, pour que la fiche reste reproductible dans le temps.
     */
    public function saisirComptage(Inventaire $inventaire, Produit $produit, int $quantiteComptee): LigneInventaire
    {
        if ($inventaire->statut !== 'brouillon') {
            throw new RuntimeException("L'inventaire du {$inventaire->date->toDateString()} n'est plus modifiable.");
        }

        $quantiteTheorique = $this->stockService->quantiteDisponible($produit, $inventaire->magasin);

        return LigneInventaire::updateOrCreate(
            ['inventaire_id' => $inventaire->id, 'produit_id' => $produit->id],
            [
                'quantite_theorique' => $quantiteTheorique,
                'quantite_comptee' => $quantiteComptee,
                'ecart' => $quantiteComptee - $quantiteTheorique,
            ],
        );
    }

    public function valider(Inventaire $inventaire, User $auteur): Inventaire
    {
        if ($inventaire->statut !== 'brouillon') {
            throw new RuntimeException("L'inventaire du {$inventaire->date->toDateString()} est déjà validé.");
        }

        return DB::transaction(function () use ($inventaire, $auteur) {
            $inventaire->loadMissing('lignes.produit');

            foreach ($inventaire->lignes as $ligne) {
                if ($ligne->ecart === 0) {
                    continue;
                }

                $this->stockService->enregistrerMouvement(
                    produit: $ligne->produit,
                    magasin: $inventaire->magasin,
                    quantite: $ligne->ecart,
                    type: MouvementStockType::Ajustement,
                    auteur: $auteur,
                    reference: $ligne,
                    motif: 'Ajustement inventaire',
                );
            }

            $inventaire->update([
                'statut' => 'valide',
                'valide_by' => $auteur->id,
                'valide_at' => now(),
            ]);

            return $inventaire->refresh();
        });
    }
}
