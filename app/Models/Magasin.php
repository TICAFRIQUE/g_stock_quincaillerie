<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['nom', 'type', 'adresse', 'telephone', 'actif'])]
class Magasin extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    const TYPE_MAGASIN = 'magasin';

    const TYPE_DEPOT = 'depot';

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty();
    }

    public function estDepot(): bool
    {
        return $this->type === self::TYPE_DEPOT;
    }

    public function scopeMagasins($query)
    {
        return $query->where('type', self::TYPE_MAGASIN);
    }

    public function scopeDepots($query)
    {
        return $query->where('type', self::TYPE_DEPOT);
    }

    public function caisses(): HasMany
    {
        return $this->hasMany(Caisse::class);
    }

    public function utilisateurs(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function mouvementStocks(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }

    public function ventes(): HasMany
    {
        return $this->hasMany(Vente::class);
    }

    public function lignesCommandeAchat(): HasMany
    {
        return $this->hasMany(LigneCommandeAchat::class, 'magasin_destination_id');
    }

    public function devis(): HasMany
    {
        return $this->hasMany(Devis::class);
    }
}
