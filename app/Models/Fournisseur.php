<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['nom', 'telephone', 'email', 'adresse', 'actif'])]
class Fournisseur extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function commandeAchats(): HasMany
    {
        return $this->hasMany(CommandeAchat::class);
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(EcritureCompteFournisseur::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementFournisseur::class);
    }

    /**
     * Solde dérivé, jamais stocké (même principe que Client::solde()) :
     * somme des écritures du compte. Positif = on doit de l'argent au
     * fournisseur.
     */
    public function solde(): int
    {
        return $this->ecritures()->sum('montant');
    }

    /**
     * Fournisseurs actifs avec une dette en cours, pour l'écran de
     * règlement — solde calculé en une seule requête groupée, jamais
     * fournisseur par fournisseur (voir Client::actifsAvecDette()).
     *
     * @return Collection<int, self>
     */
    public static function actifsAvecDette(): Collection
    {
        return static::query()
            ->where('actif', true)
            ->withSum('ecritures as solde', 'montant')
            ->having('solde', '>', 0)
            ->orderBy('nom')
            ->get();
    }
}
