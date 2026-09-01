@extends('layouts.app')

@section('title', "Bon d'achat {$commande->numero}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Bon d'achat <code>{{ $commande->numero }}</code></h2>
            <div class="text-secondary small">
                Fournisseur : <a href="{{ route('fournisseurs.show', $commande->fournisseur) }}">{{ $commande->fournisseur->nom }}</a>
            </div>
            <div class="text-secondary small">
                @if ($commande->trashed())
                    <span class="text-danger">Annulée</span>
                @elseif ($commande->statut === 'validee')
                    <span class="text-success">Validée le {{ $commande->valide_at->format('d/m/Y à H:i') }} par {{ $commande->validateur->name }}</span>
                @else
                    Date : {{ $commande->date_commande->format('d/m/Y') }} · Brouillon
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <x-bouton-imprimer :pdf-route="route('commande-achats.pdf', $commande)" />
            <a href="{{ route('commande-achats.pdf', $commande) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            @if (! $commande->trashed() && $commande->statut === 'brouillon' && $peutAnnuler)
                <x-delete-button :action="route('commande-achats.destroy', $commande)" :label="'la commande « '.$commande->numero.' »'" />
            @endif
        </div>
    </div>

    @if ($commande->trashed())
        <div class="alert alert-danger mb-3">
            <i class="bi bi-x-circle-fill me-1"></i>
            <strong>Commande annulée</strong> par {{ $commande->annulateur?->name ?? 'un utilisateur supprimé' }}
            le {{ $commande->deleted_at->format('d/m/Y H:i') }}.
            <div class="mt-1 fst-italic">{{ $commande->motif_annulation }}</div>
        </div>
    @endif

    @php
        $afficheValider = ! $commande->trashed() && $commande->statut === 'brouillon' && $peutValider;
        $afficheRegler = ! $commande->trashed() && $commande->statut === 'validee' && $peutRegler && $commande->resteDu() > 0;
        $afficheAnnuler = ! $commande->trashed() && $commande->statut === 'validee' && $peutAnnuler;
        $afficheRetourner = ! $commande->trashed() && $commande->statut === 'validee' && $peutRetourner;
        $lignesRetournables = $commande->lignes->filter(
            fn ($ligne) => $ligne->quantite_pieces - ($dejaRetourneParLigne[$ligne->id] ?? 0) > 0
        );
    @endphp

    @if ($afficheValider || $afficheRegler || $afficheRetourner || $afficheAnnuler)
        <div class="d-flex flex-wrap justify-content-end gap-2 mb-3">
            @if ($afficheValider)
                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#validerAchatModal">
                    <i class="bi bi-check-circle me-1"></i>Valider
                </button>
            @endif

            @if ($afficheRegler)
                <button type="button" class="btn btn-reglement btn-sm" data-bs-toggle="modal" data-bs-target="#reglerFournisseurModal">
                    <i class="bi bi-cash-coin me-1"></i>Régler
                </button>
            @endif

            @if ($afficheRetourner)
                <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#retourAchatModal"
                        @disabled($lignesRetournables->isEmpty())
                        title="{{ $lignesRetournables->isEmpty() ? 'Tout a déjà été retourné' : '' }}">
                    <i class="bi bi-arrow-return-left me-1"></i>Retourner
                </button>
            @endif

            @if ($afficheAnnuler)
                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#annulerAchatModal">
                    <i class="bi bi-x-circle me-1"></i>Annuler
                </button>
            @endif
        </div>
    @endif

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

    {{-- Petit tableau de règlement, juste sous le tableau des produits (qui --}}
    {{-- affiche déjà Total HT/Taxes/TTC dans son pied) — plus la grosse --}}
    {{-- carte listant chaque paiement en pleine largeur. --}}
    @if ($commande->statut === 'validee')
        <div class="d-flex justify-content-end mb-3">
            <table class="table table-sm mb-0" style="max-width: 380px;">
                <tbody>
                    @forelse ($commande->paiements as $paiement)
                        <tr>
                            <td>{{ $paiement->moyenPaiement->nom }}</td>
                            <td class="text-end">{{ number_format($paiement->montant, 0, ',', ' ') }} F</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-secondary small">Aucun paiement encaissé à la validation.</td>
                        </tr>
                    @endforelse
                    @foreach ($commande->reglementsFournisseur as $reglement)
                        <tr>
                            <td class="text-success">Règlement du {{ $reglement->created_at->format('d/m/Y') }}</td>
                            <td class="text-end text-success">{{ number_format($reglement->montant, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                    <tr class="fw-medium border-top">
                        <td>Total réglé</td>
                        <td class="text-end">{{ number_format($commande->montantRegle(), 0, ',', ' ') }} F</td>
                    </tr>
                    <tr class="fw-semibold {{ $commande->resteDu() > 0 ? 'text-danger' : '' }}">
                        <td>Reste dû au fournisseur</td>
                        <td class="text-end">{{ number_format($commande->resteDu(), 0, ',', ' ') }} F</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    @if ($commande->retours->isNotEmpty())
        <div class="card mt-3">
            <div class="card-body">
                <h3 class="h6">Retours</h3>
                @foreach ($commande->retours as $retour)
                    <div class="small border-bottom py-1 text-info-emphasis">
                        <div class="d-flex justify-content-between">
                            <span>
                                <i class="bi bi-arrow-return-left me-1"></i><code>{{ $retour->numero }}</code>
                                du {{ $retour->created_at->format('d/m/Y') }}
                                par {{ $retour->auteur?->name ?? 'utilisateur supprimé' }}
                            </span>
                            <span class="fw-medium">Avoir {{ number_format($retour->montant_total, 0, ',', ' ') }} F</span>
                        </div>
                        @if ($retour->motif)
                            <div class="text-secondary ps-4 fst-italic">{{ $retour->motif }}</div>
                        @endif
                        @foreach ($retour->lignes as $ligneRetour)
                            <div class="d-flex justify-content-between text-secondary ps-4">
                                <span>{{ $ligneRetour->produit->libelle_affichage }} × {{ $ligneRetour->quantite_pieces }}</span>
                                <span>{{ number_format($ligneRetour->montant, 0, ',', ' ') }} F</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('commande-achats.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>

    @if ($afficheValider)
        <div class="modal fade" id="validerAchatModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" x-data="{ paiements: [{ moyen_paiement_id: '', montant: '' }],
                        get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                        get resteDu() { return Math.max({{ $commande->totalTtc() }} - this.totalPaiements, 0); },
                        ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: '' }); },
                        retirerPaiement(index) { this.paiements.splice(index, 1); } }">
                    <form id="formValiderAchat" method="POST" action="{{ route('commande-achats.valider', $commande) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-check-circle me-1"></i>Valider le bon d'achat</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-secondary mb-2">
                                Total TTC à régler : <strong>{{ number_format($commande->totalTtc(), 0, ',', ' ') }} F</strong><br>
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
                            <button type="button" class="btn btn-sm btn-outline-success" @click="ajouterPaiement()">
                                <i class="bi bi-plus-lg"></i> Ajouter un paiement
                            </button>
                            <div class="mt-2 small text-secondary">
                                Reste dû après validation : <span class="fw-semibold" x-text="resteDu.toLocaleString('fr-FR') + ' F'"></span>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-success">Valider le bon d'achat</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($afficheRegler)
        <div class="modal fade" id="reglerFournisseurModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content" x-data="{ paiements: [{ moyen_paiement_id: '', montant: {{ $commande->resteDu() }} }],
                        especeIds: @json($moyensPaiement->where('est_espece', true)->pluck('id')->values()),
                        get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                        get contientEspeces() { return this.paiements.some(p => this.especeIds.includes(Number(p.moyen_paiement_id))); },
                        ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: '' }); },
                        retirerPaiement(index) { this.paiements.splice(index, 1); } }">
                    <form id="formReglerFournisseur" method="POST" action="{{ route('reglements-fournisseur.store', $commande->fournisseur) }}">
                        @csrf
                        <input type="hidden" name="commande_achat_id" value="{{ $commande->id }}">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-cash-coin me-1"></i>Règlement fournisseur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-secondary mb-2">
                                Reste dû sur ce bon d'achat : <strong>{{ number_format($commande->resteDu(), 0, ',', ' ') }} F</strong><br>
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
                            <button type="button" class="btn btn-sm btn-outline-reglement" @click="ajouterPaiement()">
                                <i class="bi bi-plus-lg"></i> Ajouter un paiement
                            </button>
                            <div class="mt-2 small text-secondary" x-show="contientEspeces" x-cloak>
                                <i class="bi bi-safe me-1"></i>Paiement en espèces : sort de la Caisse Générale.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-reglement" :disabled="totalPaiements <= 0">Enregistrer le règlement</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($afficheAnnuler)
        <div class="modal fade" id="annulerAchatModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form id="formAnnulerAchat" method="POST" action="{{ route('commande-achats.annuler', $commande) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-1"></i>Annuler le bon d'achat</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-secondary">
                                Le stock sera décrémenté en conséquence (échoue si une partie a déjà été vendue ou
                                transférée ailleurs). Cette action est irréversible.
                            </p>
                            <label for="motif-annulation-achat" class="form-label small">Motif de l'annulation (obligatoire)</label>
                            <textarea name="motif" id="motif-annulation-achat" class="form-control form-control-sm" rows="2" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-danger">Confirmer l'annulation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    @if ($afficheRetourner && $lignesRetournables->isNotEmpty())
        <div class="modal fade" id="retourAchatModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content"
                     x-data="{
                        lignes: {{ $lignesRetournables->mapWithKeys(fn ($l) => [$l->id => 0])->toJson() }},
                        get total() { return Object.values(this.lignes).reduce((s, q) => s + (Number(q) || 0), 0); },
                     }">
                    <form method="POST" action="{{ route('commande-achats.retours.store', $commande) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-arrow-return-left me-1"></i>Retourner au fournisseur</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-secondary">
                                Quantité à rendre au fournisseur par article (unité de base — pièce). Le stock est
                                repris et un avoir est crédité au compte du fournisseur.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th class="text-end">Reçu</th>
                                            <th class="text-end">Déjà retourné</th>
                                            <th class="text-end" style="width: 140px;">À retourner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lignesRetournables as $ligne)
                                            @php $dejaRetourne = $dejaRetourneParLigne[$ligne->id] ?? 0; @endphp
                                            <tr>
                                                <td>
                                                    {{ $ligne->produit->libelle_affichage }}
                                                    <input type="hidden" name="lignes[{{ $ligne->id }}][ligne_commande_achat_id]" value="{{ $ligne->id }}">
                                                </td>
                                                <td class="text-end">{{ $ligne->quantite_pieces }}</td>
                                                <td class="text-end">{{ $dejaRetourne }}</td>
                                                <td>
                                                    <input type="number" name="lignes[{{ $ligne->id }}][quantite_pieces]"
                                                           x-model.number="lignes[{{ $ligne->id }}]"
                                                           min="0" max="{{ $ligne->quantite_pieces - $dejaRetourne }}"
                                                           class="form-control form-control-sm text-end">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mb-2">
                                <label for="retour-achat-motif" class="form-label small">Motif (optionnel)</label>
                                <textarea name="motif" id="retour-achat-motif" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-info" :disabled="total <= 0">Enregistrer le retour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
