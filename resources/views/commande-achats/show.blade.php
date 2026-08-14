@extends('layouts.app')

@section('title', "Bon de commande {$commande->numero}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Bon de commande <code>{{ $commande->numero }}</code></h2>
            @if ($commande->trashed())
                <span class="badge text-bg-danger">Annulée</span>
            @elseif ($commande->statut === 'validee')
                <span class="badge text-bg-success">Validée le {{ $commande->valide_at->format('d/m/Y à H:i') }} par {{ $commande->validateur->name }}</span>
            @else
                <span class="badge text-bg-secondary">Brouillon</span>
            @endif
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('commande-achats.facture', $commande) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                <i class="bi bi-file-earmark-text me-1"></i>Voir le bon de commande
            </a>
            @if (! $commande->trashed() && $commande->statut === 'brouillon' && $peutAnnuler)
                <x-delete-button :action="route('commande-achats.destroy', $commande)" :label="'la commande « '.$commande->numero.' »'" />
            @endif
        </div>
    </div>

    @if (! $commande->trashed() && $commande->statut === 'brouillon' && $peutValider)
        <div class="row g-3 mb-3">
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 shadow-sm" x-data="{ ouvert: false, paiements: [{ moyen_paiement_id: '', montant: '' }],
                        get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                        get resteDu() { return Math.max({{ $commande->totalTtc() }} - this.totalPaiements, 0); },
                        ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: '' }); },
                        retirerPaiement(index) { this.paiements.splice(index, 1); } }">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                            <div>
                                <div class="d-flex align-items-center gap-2 text-success fw-semibold">
                                    <i class="bi bi-check-circle fs-5"></i>Valider le bon de commande
                                </div>
                                <div class="small text-secondary mt-1">
                                    Total TTC à régler : <strong>{{ number_format($commande->totalTtc(), 0, ',', ' ') }} F</strong>
                                </div>
                            </div>
                            <button type="button" class="btn btn-success btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                                <i class="bi bi-check-circle me-1"></i>Valider
                            </button>
                        </div>

                        <form id="formValiderAchat" method="POST" action="{{ route('commande-achats.valider', $commande) }}" x-show="ouvert" x-cloak class="mt-2">
                            @csrf
                            <p class="small text-secondary mb-2">
                                Laissez vide pour ne rien encaisser maintenant (dette fournisseur intégrale).
                            </p>
                            <template x-for="(paiement, index) in paiements" :key="index">
                                <div class="row g-1 align-items-center mb-2">
                                    <div class="col-6 col-md-5">
                                        <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm">
                                            <option value="">Moyen…</option>
                                            @foreach ($moyensPaiement as $moyen)
                                                <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="1" class="form-control form-control-sm" placeholder="Montant">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-success mb-2" @click="ajouterPaiement()">
                                <i class="bi bi-plus-lg"></i> Ajouter un paiement
                            </button>
                            <div class="mb-2 small text-secondary">
                                Reste dû après validation : <span class="fw-semibold" x-text="resteDu.toLocaleString('fr-FR') + ' F'"></span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-success btn-sm flex-fill"
                                        data-bs-toggle="modal" data-bs-target="#confirmActionModal"
                                        data-form-id="formValiderAchat"
                                        data-message="Valider ce bon de commande maintenant ? Le stock sera mis à jour immédiatement et cette action est irréversible."
                                        data-button-label="Valider le bon de commande" data-button-class="btn-success">
                                    Valider le bon de commande
                                </button>
                                <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($commande->trashed())
        <div class="alert alert-danger mb-3">
            <i class="bi bi-x-circle-fill me-1"></i>
            <strong>Commande annulée</strong> par {{ $commande->annulateur?->name ?? 'un utilisateur supprimé' }}
            le {{ $commande->deleted_at->format('d/m/Y H:i') }}.
            <div class="mt-1 fst-italic">{{ $commande->motif_annulation }}</div>
        </div>
    @endif

    @php
        $afficheRegler = ! $commande->trashed() && $commande->statut === 'validee' && $peutRegler && $commande->resteDu() > 0;
        $afficheAnnuler = ! $commande->trashed() && $commande->statut === 'validee' && $peutAnnuler;
    @endphp

    @if ($afficheRegler || $afficheAnnuler)
        <div class="row g-3 mb-3">
            @if ($afficheRegler)
                <div class="{{ $afficheAnnuler ? 'col-md-6' : 'col-md-6 col-lg-5' }}">
                    <div class="card h-100 shadow-sm" x-data="{ ouvert: false, paiements: [{ moyen_paiement_id: '', montant: {{ $commande->resteDu() }} }],
                            get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                            ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: '' }); },
                            retirerPaiement(index) { this.paiements.splice(index, 1); } }">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <div>
                                    <div class="d-flex align-items-center gap-2 text-reglement fw-semibold">
                                        <i class="bi bi-cash-coin fs-5"></i>Règlement fournisseur
                                    </div>
                                    <div class="small text-secondary mt-1">
                                        Reste dû sur ce bon de commande : <strong>{{ number_format($commande->resteDu(), 0, ',', ' ') }} F</strong>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-reglement btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                                    <i class="bi bi-plus-lg me-1"></i>Régler
                                </button>
                            </div>

                            <form id="formReglerFournisseur" method="POST" action="{{ route('reglements-fournisseur.store', $commande->fournisseur) }}" x-show="ouvert" x-cloak class="mt-2">
                                @csrf
                                <input type="hidden" name="commande_achat_id" value="{{ $commande->id }}">
                                <p class="small text-secondary mb-2">
                                    Solde total du fournisseur (toutes commandes confondues) : {{ number_format($commande->fournisseur->solde(), 0, ',', ' ') }} F
                                </p>
                                <template x-for="(paiement, index) in paiements" :key="index">
                                    <div class="row g-1 align-items-center mb-2">
                                        <div class="col-6 col-md-5">
                                            <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm" required>
                                                <option value="">Moyen…</option>
                                                @foreach ($moyensPaiement as $moyen)
                                                    <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-4">
                                            <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="1" class="form-control form-control-sm" placeholder="Montant" required>
                                        </div>
                                        <div class="col-2">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)" x-show="paiements.length > 1">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                                <button type="button" class="btn btn-sm btn-outline-reglement mb-2" @click="ajouterPaiement()">
                                    <i class="bi bi-plus-lg"></i> Ajouter un paiement
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-reglement btn-sm flex-fill" :disabled="totalPaiements <= 0">
                                        Enregistrer le règlement
                                    </button>
                                    <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if ($afficheAnnuler)
                <div class="{{ $afficheRegler ? 'col-md-6' : 'col-md-6 col-lg-5' }}">
                    <div class="card h-100 shadow-sm" x-data="{ ouvert: false }">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <div>
                                    <div class="d-flex align-items-center gap-2 text-danger fw-semibold">
                                        <i class="bi bi-x-circle fs-5"></i>Annuler le bon de commande
                                    </div>
                                    <div class="small text-secondary mt-1">Le stock sera remis à jour automatiquement.</div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                                    <i class="bi bi-x-circle me-1"></i>Annuler
                                </button>
                            </div>

                            <form id="formAnnulerAchat" method="POST" action="{{ route('commande-achats.annuler', $commande) }}" x-show="ouvert" x-cloak class="mt-2">
                                @csrf
                                <label for="motif-annulation" class="form-label small">
                                    Motif de l'annulation (obligatoire)
                                </label>
                                <textarea name="motif" id="motif-annulation" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-danger btn-sm flex-fill"
                                            data-bs-toggle="modal" data-bs-target="#confirmActionModal"
                                            data-form-id="formAnnulerAchat"
                                            data-message="Annuler ce bon de commande ? Le stock sera décrémenté en conséquence (échoue si une partie a déjà été vendue ou transférée ailleurs). Cette action est irréversible."
                                            data-button-label="Annuler le bon de commande" data-button-class="btn-danger">
                                        Confirmer l'annulation
                                    </button>
                                    <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Fournisseur</div>
                    <div class="fw-medium">{{ $commande->fournisseur->nom }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-secondary small">Date</div>
                    <div class="fw-medium">{{ $commande->date_commande->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Produit</th>
                        <th>Quantité achetée</th>
                        <th>Qté en pièces (stock)</th>
                        <th>Destination</th>
                        <th>Prix d'achat HT</th>
                        <th>Taxe</th>
                        <th>Total HT</th>
                        <th>Total TTC</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($commande->lignes as $ligne)
                        <tr>
                            <td><code>{{ $ligne->produit->sku }}</code></td>
                            <td>{{ $ligne->produit->libelle_affichage }}</td>
                            <td>
                                {{ $ligne->quantite }} × {{ $ligne->uniteVente->unite->nom_avec_abbreviation ?? $ligne->produit->unite_base_libelle }}
                            </td>
                            <td>{{ $ligne->quantite_pieces }} {{ $ligne->produit->unite_base_libelle }}</td>
                            <td>
                                {{ $ligne->magasinDestination->nom }}
                                @if ($ligne->magasinDestination->estDepot())
                                    <span class="badge text-bg-info">Dépôt</span>
                                @endif
                            </td>
                            <td>
                                {{ number_format($ligne->prix_achat, 0, ',', ' ') }} F
                                @if ($ligne->uniteVente)
                                    <div class="text-secondary small">soit {{ number_format($ligne->prixAchatParPiece(), 0, ',', ' ') }} F / {{ $ligne->produit->unite_base_libelle }}</div>
                                @endif
                            </td>
                            <td>{{ $ligne->taxe->nom ?? '—' }}</td>
                            <td>{{ number_format($ligne->montantHt(), 0, ',', ' ') }} F</td>
                            <td>{{ number_format($ligne->montantTtc(), 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="6" class="text-end">Total</th>
                        <th>{{ number_format($commande->totalTaxes(), 0, ',', ' ') }} F</th>
                        <th>{{ number_format($commande->totalHt(), 0, ',', ' ') }} F</th>
                        <th>{{ number_format($commande->totalTtc(), 0, ',', ' ') }} F</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if ($commande->statut === 'validee')
        <div class="card mt-3">
            <div class="card-body">
                <h3 class="h6">Règlement</h3>
                @forelse ($commande->paiements as $paiement)
                    <div class="d-flex justify-content-between small border-bottom py-1">
                        <span>{{ $paiement->moyenPaiement->nom }}</span>
                        <span>{{ number_format($paiement->montant, 0, ',', ' ') }} F</span>
                    </div>
                @empty
                    <p class="text-secondary small mb-0">Aucun paiement encaissé à la validation.</p>
                @endforelse
                @foreach ($commande->reglementsFournisseur as $reglement)
                    <div class="small border-bottom py-1 text-success">
                        <div class="d-flex justify-content-between">
                            <span>
                                <i class="bi bi-cash-coin me-1"></i>Règlement du {{ $reglement->created_at->format('d/m/Y') }}
                                par {{ $reglement->auteur?->name ?? 'utilisateur supprimé' }}
                            </span>
                            <span class="fw-medium">{{ number_format($reglement->montant, 0, ',', ' ') }} F</span>
                        </div>
                        @foreach ($reglement->paiements as $paiementReglement)
                            <div class="d-flex justify-content-between text-secondary ps-4">
                                <span>{{ $paiementReglement->moyenPaiement->nom }}</span>
                                <span>{{ number_format($paiementReglement->montant, 0, ',', ' ') }} F</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
                <div class="d-flex justify-content-between fw-medium mt-2">
                    <span>Total réglé</span>
                    <span>{{ number_format($commande->montantRegle(), 0, ',', ' ') }} F</span>
                </div>
                <div class="d-flex justify-content-between fw-semibold {{ $commande->resteDu() > 0 ? 'text-danger' : '' }}">
                    <span>Reste dû au fournisseur</span>
                    <span>{{ number_format($commande->resteDu(), 0, ',', ' ') }} F</span>
                </div>
            </div>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('commande-achats.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>
@endsection
