<?php

namespace App\Services;

use App\Enums\DevisStatut;
use App\Exceptions\DevisNonModifiableException;
use App\Exceptions\DevisNonTransformableException;
use App\Models\Client;
use App\Models\Devis;
use App\Models\Parametre;
use App\Models\SessionCaisse;
use App\Models\User;
use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Devis : document commercial pré-vente, créé hors caisse (aucun mouvement
 * de stock ni d'argent, règle 15). Montants toujours indicatifs — jamais
 * figés sur les lignes (voir Devis::calculerMontants()). La transformation
 * en vente délègue entièrement à VenteService : mêmes règles qu'une vente
 * normale (session ouverte, stock, crédit).
 */
class DevisService
{
    public function __construct(private readonly VenteService $venteService) {}

    /**
     * @param  array<int, array{produit_id:int, unite_vente_id?:?int, quantite:int, remise_type?:?string, remise_valeur?:?int}>  $lignes
     */
    public function creer(Client $client, User $auteur, array $lignes, ?int $dureeJours = null): Devis
    {
        if (empty($lignes)) {
            throw new InvalidArgumentException('Un devis doit comporter au moins une ligne.');
        }

        return DB::transaction(function () use ($client, $auteur, $lignes, $dureeJours) {
            $duree = $dureeJours ?? Parametre::actuel()->duree_validite_devis_jours;

            $devis = Devis::create([
                'numero' => $this->genererNumero(),
                'client_id' => $client->id,
                'created_by' => $auteur->id,
                'statut' => DevisStatut::Brouillon,
                'date_validite' => now()->addDays($duree)->toDateString(),
            ]);

            $this->remplacerLignes($devis, $lignes);

            return $devis->refresh();
        });
    }

    /**
     * Numéro indépendant du magasin (comme CommandeAchat::genererNumero()) :
     * un numéro lié au magasin choisi dans le formulaire créait une
     * confusion pour l'utilisateur, qui n'a aucune raison d'associer ce
     * champ à la numérotation du document.
     */
    private function genererNumero(): string
    {
        do {
            $numero = 'DEV-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Devis::where('numero', $numero)->exists());

        return $numero;
    }

    /**
     * @param  array<int, array{produit_id:int, unite_vente_id?:?int, quantite:int, remise_type?:?string, remise_valeur?:?int}>  $lignes
     */
    public function modifier(Devis $devis, array $lignes): Devis
    {
        if (empty($lignes)) {
            throw new InvalidArgumentException('Un devis doit comporter au moins une ligne.');
        }

        if (! $devis->peutEtreModifie()) {
            throw new DevisNonModifiableException($devis);
        }

        return DB::transaction(function () use ($devis, $lignes) {
            $this->remplacerLignes($devis, $lignes);

            return $devis->refresh();
        });
    }

    /**
     * Couvre à la fois le refus (retour du client) et l'annulation interne
     * (abandon) — un devis transformé reste seul intouchable.
     */
    public function annuler(Devis $devis): Devis
    {
        if (! $devis->peutEtreAnnule()) {
            throw new DevisNonModifiableException($devis);
        }

        $devis->update(['statut' => DevisStatut::Refuse]);

        return $devis->refresh();
    }

    /**
     * Transformation totale uniquement (règle 15) : reprend les lignes
     * telles qu'éditées à l'écran de transformation (prix/remises courants,
     * jamais les montants indicatifs du devis) et délègue à VenteService, qui
     * applique les mêmes règles qu'une vente créée directement (session
     * ouverte, stock, crédit). Le client du devis est toujours celui de la
     * vente résultante.
     *
     * @param  array<int, array{produit_id:int, unite_vente_id?:?int, magasin_source_id?:?int, quantite:int, remise_type?:?string, remise_valeur?:?int}>  $lignes
     * @param  array<int, array{moyen_paiement_id:int, montant:int}>  $paiements
     */
    public function transformer(
        Devis $devis,
        SessionCaisse $session,
        User $caissier,
        array $lignes,
        array $paiements,
        ?string $remiseTotaleType = null,
        ?int $remiseTotaleValeur = null,
        ?int $montantRecu = null,
        bool $autoriserDepassementLimite = false,
    ): Vente {
        if (! $devis->peutEtreTransforme()) {
            throw new DevisNonTransformableException($devis);
        }

        $devis->loadMissing('client');

        return DB::transaction(function () use ($devis, $session, $caissier, $lignes, $paiements, $remiseTotaleType, $remiseTotaleValeur, $montantRecu, $autoriserDepassementLimite) {
            $vente = $this->venteService->vendre(
                session: $session,
                caissier: $caissier,
                lignes: $lignes,
                paiements: $paiements,
                remiseTotaleType: $remiseTotaleType,
                remiseTotaleValeur: $remiseTotaleValeur,
                montantRecu: $montantRecu,
                client: $devis->client,
                autoriserDepassementLimite: $autoriserDepassementLimite,
            );

            $devis->update([
                'statut' => DevisStatut::Transforme,
                'vente_id' => $vente->id,
                'transforme_at' => now(),
            ]);

            return $vente;
        });
    }

    /**
     * @param  array<int, array{produit_id:int, unite_vente_id?:?int, quantite:int, remise_type?:?string, remise_valeur?:?int}>  $lignes
     */
    private function remplacerLignes(Devis $devis, array $lignes): void
    {
        $devis->lignes()->delete();

        foreach ($lignes as $ligne) {
            $devis->lignes()->create([
                'produit_id' => $ligne['produit_id'],
                'unite_vente_id' => $ligne['unite_vente_id'] ?? null,
                'taxe_id' => $ligne['taxe_id'] ?? null,
                'quantite' => $ligne['quantite'],
                'remise_type' => $ligne['remise_type'] ?? null,
                'remise_valeur' => $ligne['remise_valeur'] ?? null,
            ]);
        }
    }
}
