<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Bon de livraison : constat logistique de remise physique d'une partie (ou
 * de la totalité) des articles d'une vente au client — une vente peut être
 * livrée en plusieurs fois (voir CLAUDE.md, section Bons de livraison). Ne
 * mouvemente jamais le stock (déjà fait à la vente, règle 3) ni la caisse/le
 * compte client. Un bon de livraison "supprimé" (deleted_at) représente une
 * annulation, pas une perte de données — même logique que Vente::annuler().
 */
#[Fillable(['numero', 'vente_id', 'motif', 'created_by', 'motif_annulation', 'annulee_par'])]
class BonLivraison extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'deleted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable();
    }

    public function vente(): BelongsTo
    {
        return $this->belongsTo(Vente::class)->withTrashed();
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function annulateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulee_par');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneBonLivraison::class);
    }
}
