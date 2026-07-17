<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['magasin_id', 'caisse_id', 'session_caisse_id', 'caissier_id', 'libelle'])]
class VenteEnAttente extends Model
{
    use HasFactory;

    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class);
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class);
    }

    public function sessionCaisse(): BelongsTo
    {
        return $this->belongsTo(SessionCaisse::class);
    }

    public function caissier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caissier_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVenteEnAttente::class);
    }
}
