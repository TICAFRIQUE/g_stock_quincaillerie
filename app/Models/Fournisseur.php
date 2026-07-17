<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nom', 'telephone', 'email', 'adresse', 'actif'])]
class Fournisseur extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    public function commandeAchats(): HasMany
    {
        return $this->hasMany(CommandeAchat::class);
    }
}
