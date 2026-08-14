<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['magasin_id', 'caisse_id', 'session_caisse_id', 'caissier_id', 'client_id', 'libelle'])]
class VenteEnAttente extends Model
{
    use HasFactory;

    /**
     * withTrashed() : évite un crash d'affichage si le magasin ou la caisse
     * ont été supprimés (soft delete) pendant qu'une vente reste en attente.
     */
    public function magasin(): BelongsTo
    {
        return $this->belongsTo(Magasin::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class)->withTrashed();
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
