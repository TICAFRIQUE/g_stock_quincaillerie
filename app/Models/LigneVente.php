<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'vente_id', 'produit_id', 'unite_vente_id', 'magasin_source_id', 'quantite', 'quantite_pieces',
    'prix_unitaire_applique', 'cout_applique', 'sous_total_ligne',
    'remise_ligne_type', 'remise_ligne_valeur', 'remise_ligne_montant', 'prix_personnalise', 'total_ligne',
])]
class LigneVente extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'quantite' => 'integer',
            'quantite_pieces' => 'integer',
            'prix_unitaire_applique' => 'integer',
            'cout_applique' => 'integer',
            'sous_total_ligne' => 'integer',
            'remise_ligne_valeur' => 'integer',
            'remise_ligne_montant' => 'integer',
            'prix_personnalise' => 'boolean',
            'total_ligne' => 'integer',
        ];
    }

    /**
     * Prix affiché sur le ticket/la facture : le prix personnalisé saisi par
     * le caissier (remise "montant" auto-calculée, jamais stockée comme un
     * vrai changement de prix — voir migration 2026_09_01_100002) au lieu du
     * prix catalogue, uniquement pour l'affichage. Rien d'autre (marge,
     * total_ligne, activitylog) ne dépend de cette valeur.
     */
    public function prixUnitaireEffectif(): int
    {
        if (! $this->prix_personnalise || $this->quantite === 0) {
            return $this->prix_unitaire_applique;
        }

        return $this->prix_unitaire_applique - intdiv($this->remise_ligne_montant, $this->quantite);
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class)->withTrashed();
    }

    /**
     * withTrashed() : une ligne de vente est immuable et doit rester
     * affichable (ticket, rapport de marge) même si le produit a été
     * supprimé (soft delete) depuis.
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class)->withTrashed();
    }

    public function uniteVente(): BelongsTo
    {
        return $this->belongsTo(UniteVente::class)->withTrashed();
    }

    /**
     * withTrashed() : ligne historique, doit rester affichable même si le
     * magasin/dépôt source a été supprimé depuis.
     */
    public function magasinSource(): BelongsTo
    {
        return $this->belongsTo(Magasin::class, 'magasin_source_id')->withTrashed();
    }

    public function retours(): HasMany
    {
        return $this->hasMany(LigneRetourVente::class);
    }
}
