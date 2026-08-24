<?php

namespace App\Services;

use App\Enums\EcritureCompteTresorerieType;
use App\Exceptions\SoldeTresorerieInsuffisantException;
use App\Models\CompteTresorerie;
use App\Models\EcritureCompteTresorerie;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Point de passage unique pour toute variation d'un compte de trésorerie
 * (Caisse Générale ou compte bancaire/autre) — mirroring CompteFournisseurService.
 * Le solde est dérivé, jamais écrasé : chaque appel insère une écriture
 * immuable.
 */
class CompteTresorerieService
{
    public function solde(CompteTresorerie $compte): int
    {
        return EcritureCompteTresorerie::where('compte_tresorerie_id', $compte->id)->sum('montant');
    }

    /**
     * La Caisse Générale est un singleton (voir CompteTresorerieSeeder) :
     * point d'accès unique pour ne jamais coder son id en dur ailleurs.
     */
    public function caisseGenerale(): CompteTresorerie
    {
        return CompteTresorerie::where('type', 'caisse_generale')->firstOrFail();
    }

    public function crediter(
        CompteTresorerie $compte,
        int $montant,
        EcritureCompteTresorerieType $type,
        User $auteur,
        ?Model $reference = null,
        ?string $motif = null,
    ): EcritureCompteTresorerie {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant doit être positif.');
        }

        $compte = CompteTresorerie::whereKey($compte->id)->lockForUpdate()->firstOrFail();

        return EcritureCompteTresorerie::create([
            'compte_tresorerie_id' => $compte->id,
            'type' => $type,
            'montant' => $montant,
            'motif' => $motif,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'created_by' => $auteur->id,
        ]);
    }

    /**
     * Une sortie ne peut jamais dépasser le solde actuel du compte — on ne
     * peut pas sortir plus d'argent qu'il n'y en a dans le coffre/compte.
     */
    public function debiter(
        CompteTresorerie $compte,
        int $montant,
        EcritureCompteTresorerieType $type,
        User $auteur,
        ?Model $reference = null,
        ?string $motif = null,
    ): EcritureCompteTresorerie {
        if ($montant <= 0) {
            throw new InvalidArgumentException('Le montant doit être positif.');
        }

        $compte = CompteTresorerie::whereKey($compte->id)->lockForUpdate()->firstOrFail();
        $soldeActuel = $this->solde($compte);

        if ($montant > $soldeActuel) {
            throw new SoldeTresorerieInsuffisantException($compte, $montant, $soldeActuel);
        }

        return EcritureCompteTresorerie::create([
            'compte_tresorerie_id' => $compte->id,
            'type' => $type,
            'montant' => -$montant,
            'motif' => $motif,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'created_by' => $auteur->id,
        ]);
    }

    /**
     * Virement interne entre deux comptes de trésorerie : sortie d'un côté,
     * entrée de l'autre, sans valorisation supplémentaire — même principe
     * qu'un transfert de stock inter-magasin (voir CLAUDE.md).
     */
    public function virer(CompteTresorerie $source, CompteTresorerie $destination, int $montant, User $auteur, ?string $motif = null): void
    {
        if ($source->is($destination)) {
            throw new InvalidArgumentException('La source et la destination du virement doivent être différentes.');
        }

        DB::transaction(function () use ($source, $destination, $montant, $auteur, $motif) {
            $this->debiter($source, $montant, EcritureCompteTresorerieType::VirementSortant, $auteur, reference: $destination, motif: $motif ?? "Virement vers {$destination->nom}");
            $this->crediter($destination, $montant, EcritureCompteTresorerieType::VirementEntrant, $auteur, reference: $source, motif: $motif ?? "Virement depuis {$source->nom}");
        });
    }
}
