@extends('layouts.app')

@section('title', 'Vente')

@section('content')
    <div x-data="posApp({{ \Illuminate\Support\Js::from($produits) }}, {{ \Illuminate\Support\Js::from($panierInitial) }}, {{ \Illuminate\Support\Js::from($venteEnAttente->libelle ?? '') }})">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3">
            <div>
                <h2 class="h4 mb-0">Vente — {{ $session->caisse->nom }}</h2>
                <p class="text-secondary small mb-0">
                    {{ $session->caisse->magasin->nom }}
                    @if ($venteEnAttente)
                        <span class="badge text-bg-warning ms-2">
                            <i class="bi bi-hourglass-split me-1"></i>Reprise du panier en attente #{{ $venteEnAttente->id }}
                        </span>
                    @endif
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('ventenattente.gerer')
                    <a href="{{ route('ventes-en-attente.index', $session) }}" class="btn btn-outline-warning position-relative">
                        <i class="bi bi-hourglass-split me-1"></i>Ventes en attente
                        @if ($venteEnAttentesCount > 0)
                            <span class="badge rounded-pill text-bg-warning ms-1">{{ $venteEnAttentesCount }}</span>
                        @endif
                    </a>
                @endcan
                <a href="{{ route('sessions.show', $session) }}" class="btn btn-link">
                    <i class="bi bi-arrow-left me-1"></i>Retour à la session
                </a>
            </div>
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-8">
                <div class="mb-3">
                    <select id="produit-picker" class="form-select" @change="ajouterDepuisSelect($event)">
                        <option value="">— Rechercher un produit à ajouter —</option>
                        @foreach ($produits as $produit)
                            <option value="{{ $produit['id'] }}"
                                    data-produit-id="{{ $produit['id'] }}"
                                    @if ($produit['stock'] < 1) disabled @endif>
                                {{ $produit['libelle_affichage'] }} (Stock : {{ $produit['stock'] }})
                                @if ($produit['stock'] < 1) — rupture de stock @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h3 class="h6">Panier</h3>

                        <div class="table-responsive" style="max-height: 65vh; overflow-y: auto;">
                            @php $colonnesPanier = auth()->user()->can('vente.remise') ? 6 : 5; @endphp
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Désignation</th>
                                        <th>Qté</th>
                                        <th>Pièce / Lot</th>
                                        @can('vente.remise')
                                            <th>Remise</th>
                                        @endcan
                                        <th class="text-end">Total</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-if="panier.length === 0">
                                        <tr>
                                            <td colspan="{{ $colonnesPanier }}" class="text-secondary fst-italic small text-center py-3">Le panier est vide. Sélectionnez un produit ci-dessus.</td>
                                        </tr>
                                    </template>
                                    <template x-for="(ligne, index) in panier" :key="index">
                                        <tr>
                                            <td class="small" x-text="ligne.produitLibelle + ' (Stock : ' + produitDe(ligne).stock + ')'"></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" @click="changerQuantite(ligne, -1)">−</button>
                                                    <span x-text="ligne.quantite"></span>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" @click="changerQuantite(ligne, 1)">+</button>
                                                </div>
                                            </td>
                                            <td>
                                                <select class="form-select form-select-sm" style="min-width: 150px;"
                                                        :class="{ 'is-invalid': estDoublon(index) || ligne.unite_vente_id === undefined }"
                                                        @change="changerVarianteDepuisSelect(ligne, $event.target.value)" required>
                                                    <option value="" :selected="ligne.unite_vente_id === undefined">— Choisir —</option>
                                                    <option value="piece" :selected="ligne.unite_vente_id === null" x-text="'Pièce — ' + produitDe(ligne).prix_piece + ' F'"></option>
                                                    <template x-for="unite in produitDe(ligne).unites" :key="unite.id">
                                                        <option :value="unite.id" :selected="unite.id === ligne.unite_vente_id" x-text="unite.libelle + ' — ' + unite.prix + ' F'"></option>
                                                    </template>
                                                </select>
                                                <div class="text-danger small mt-1" x-show="estDoublon(index)" x-cloak>Déjà dans le panier avec la même variante.</div>
                                            </td>
                                            @can('vente.remise')
                                                <td>
                                                    <div class="d-flex flex-column gap-1" style="min-width: 130px;">
                                                        <select x-model="ligne.remise_type" class="form-select form-select-sm">
                                                            <option value="">Sans remise</option>
                                                            <option value="montant">Remise (F)</option>
                                                            <option value="pourcentage">Remise (%)</option>
                                                        </select>
                                                        <input type="number" x-model.number="ligne.remise_valeur"
                                                               @input="if (ligne.remise_type === 'pourcentage' && ligne.remise_valeur > 100) ligne.remise_valeur = 100"
                                                               x-show="ligne.remise_type" min="0" :max="ligne.remise_type === 'pourcentage' ? 100 : null" class="form-control form-control-sm" placeholder="Valeur">
                                                    </div>
                                                </td>
                                            @endcan
                                            <td class="text-end fw-medium" x-text="totalLigne(ligne) + ' F'"></td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="Dupliquer (pour ajouter une autre variante de ce produit)" @click="dupliquerLigne(index)">
                                                        <i class="bi bi-copy"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerLigne(index)">
                                                        <i class="bi bi-x-lg"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-4">
                <div style="position: sticky; top: 1rem;">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="h6">Comptabilité</h3>

                            @can('vente.remise')
                                <div class="mb-2">
                                    <label class="form-label small">Remise sur le total</label>
                                    <div class="row g-1">
                                        <div class="col-6">
                                            <select x-model="remiseTotaleType" class="form-select form-select-sm">
                                                <option value="">Sans remise</option>
                                                <option value="montant">Remise (F)</option>
                                                <option value="pourcentage">Remise (%)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <input type="number" x-model.number="remiseTotaleValeur"
                                                   @input="if (remiseTotaleType === 'pourcentage' && remiseTotaleValeur > 100) remiseTotaleValeur = 100"
                                                   x-show="remiseTotaleType" min="0" :max="remiseTotaleType === 'pourcentage' ? 100 : null" class="form-control form-control-sm" placeholder="Valeur">
                                        </div>
                                    </div>
                                </div>
                            @endcan

                            <table class="table table-sm mt-3 mb-0">
                                <tr>
                                    <td>Sous-total</td>
                                    <td class="text-end" x-text="sousTotal + ' F'"></td>
                                </tr>
                                @can('vente.remise')
                                    <tr>
                                        <td>Remise totale</td>
                                        <td class="text-end" x-text="'− ' + remiseTotaleMontant + ' F'"></td>
                                    </tr>
                                @endcan
                                <tr class="fw-bold">
                                    <td>Net à payer</td>
                                    <td class="text-end" x-text="totalNet + ' F'"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="h6">Paiement</h3>
                            <template x-for="(paiement, index) in paiements" :key="index">
                                <div class="row g-1 align-items-center mb-2">
                                    <div class="col-5">
                                        <select x-model="paiement.moyen_paiement_id" class="form-select form-select-sm"
                                                :class="{ 'is-invalid': (Number(paiement.montant) || 0) > 0 && !paiement.moyen_paiement_id }">
                                            <option value="">Moyen…</option>
                                            @foreach ($moyensPaiement as $moyen)
                                                <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="number" x-model.number="paiement.montant" min="0" class="form-control form-control-sm" placeholder="Montant">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="completerPaiement(index)" title="Compléter le solde restant">
                                            <i class="bi bi-magic"></i>
                                        </button>
                                    </div>
                                    <div class="col-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="ajouterPaiement()">
                                <i class="bi bi-plus-lg"></i> Ajouter un moyen de paiement
                            </button>

                            <div class="mt-2 small" :class="totalPaiements >= totalNet ? 'text-success' : 'text-danger'">
                                Total réglé : <span x-text="totalPaiements"></span> F
                                <span x-show="totalPaiements < totalNet"> (net à payer : <span x-text="totalNet"></span> F)</span>
                            </div>
                            <div class="mt-1 small fw-medium text-primary" x-show="monnaieARendre > 0" x-cloak>
                                <i class="bi bi-cash-coin me-1"></i>Monnaie à rendre : <span x-text="monnaieARendre"></span> F
                            </div>
                            <div class="mt-1 small text-danger" x-show="aUnPaiementSansMoyen" x-cloak>
                                <i class="bi bi-exclamation-triangle-fill me-1"></i>Choisissez un moyen de paiement pour chaque montant saisi.
                            </div>
                        </div>
                    </div>

                    <div class="text-danger small mb-2" x-show="aUneLigneNonChoisie" x-cloak>
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Choisissez une variante (pièce ou lot) pour chaque ligne du panier.
                    </div>
                    <div class="text-danger small mb-2" x-show="aUnDoublon" x-cloak>
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Un même produit ne peut pas apparaître deux fois avec la même variante (pièce ou lot) : corrigez avant d'enregistrer.
                    </div>

                    <div class="d-flex gap-2">
                        <form id="formVente" method="POST"
                              action="{{ $venteEnAttente ? route('ventes-en-attente.reprendre', $venteEnAttente) : route('ventes.store', $session) }}"
                              class="flex-grow-1">
                            @csrf
                            <template x-for="(ligne, index) in panier" :key="'v'+index">
                                <span>
                                    <input type="hidden" :name="'lignes['+index+'][produit_id]'" :value="ligne.produit_id">
                                    <input type="hidden" :name="'lignes['+index+'][unite_vente_id]'" :value="ligne.unite_vente_id">
                                    <input type="hidden" :name="'lignes['+index+'][quantite]'" :value="ligne.quantite">
                                    <input type="hidden" :name="'lignes['+index+'][remise_type]'" :value="ligne.remise_type">
                                    <input type="hidden" :name="'lignes['+index+'][remise_valeur]'" :value="ligne.remise_valeur">
                                </span>
                            </template>
                            <input type="hidden" name="remise_totale_type" :value="remiseTotaleType">
                            <input type="hidden" name="remise_totale_valeur" :value="remiseTotaleValeur">
                            <template x-for="(paiement, index) in paiementsAppliques" :key="'p'+index">
                                <span>
                                    <input type="hidden" :name="'paiements['+index+'][moyen_paiement_id]'" :value="paiement.moyen_paiement_id">
                                    <input type="hidden" :name="'paiements['+index+'][montant]'" :value="paiement.montant">
                                </span>
                            </template>
                            <input type="hidden" name="montant_recu" :value="totalPaiements">
                            <button type="button" class="btn btn-primary w-100"
                                    :disabled="panier.length === 0 || totalPaiements < totalNet || aUnDoublon || aUneLigneNonChoisie || aUnPaiementSansMoyen"
                                    @click="declencherFinalisation($event)"
                                    data-form-id="formVente"
                                    data-message="Finaliser cette vente ? Le stock sera mis à jour et le paiement enregistré. Cette action est irréversible."
                                    data-button-label="Finaliser la vente" data-button-class="btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Finaliser la vente
                            </button>
                        </form>
                    </div>

                    @can('ventenattente.gerer')
                        <div class="mt-2">
                            <form id="formAttente" method="POST"
                                  action="{{ $venteEnAttente ? route('ventes-en-attente.update', $venteEnAttente) : route('ventes-en-attente.store', $session) }}">
                                @csrf
                                @if ($venteEnAttente) @method('PUT') @endif
                                <template x-for="(ligne, index) in panier" :key="'a'+index">
                                    <span>
                                        <input type="hidden" :name="'lignes['+index+'][produit_id]'" :value="ligne.produit_id">
                                        <input type="hidden" :name="'lignes['+index+'][unite_vente_id]'" :value="ligne.unite_vente_id">
                                        <input type="hidden" :name="'lignes['+index+'][quantite]'" :value="ligne.quantite">
                                    </span>
                                </template>
                                <input type="text" name="libelle" x-model="libelleAttente" class="form-control form-control-sm mb-2" placeholder="Note (optionnel, ex. « Client au téléphone »)">
                                <button type="button" class="btn btn-outline-secondary w-100"
                                        :disabled="panier.length === 0 || aUnDoublon || aUneLigneNonChoisie"
                                        @click="declencherMiseEnAttente($event)"
                                        data-form-id="formAttente"
                                        data-message="Mettre ce panier en attente ? Vous pourrez le reprendre plus tard depuis « Ventes en attente »."
                                        data-button-label="Mettre en attente" data-button-class="btn-outline-secondary">
                                    <i class="bi bi-hourglass-split me-1"></i>Mettre en attente
                                </button>
                            </form>
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initSelect2('#produit-picker', { placeholder: '— Rechercher un produit à ajouter —' });
        });
    </script>
@endpush
