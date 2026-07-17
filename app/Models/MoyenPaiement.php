<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nom', 'est_espece', 'actif'])]
class MoyenPaiement extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'est_espece' => 'boolean',
            'actif' => 'boolean',
        ];
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class);
    }
}
