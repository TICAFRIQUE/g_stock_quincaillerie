@extends('layouts.app')

@section('title', 'Reprendre la vente')

@section('content')
    <div x-data="{
        remiseTotaleType: '',
        remiseTotaleValeur: null,
        paiements: [{ moyen_paiement_id: '', montant: {{ $sousTotal }} }],
        sousTotal: {{ $sousTotal }},
        get remiseTotaleMontant() {
            if (!this.remiseTotaleType || !this.remiseTotaleValeur) return 0;
            const montant = this.remiseTotaleType === 'pourcentage' ? Math.round(this.sousTotal * this.remiseTotaleValeur / 100) : Number(this.remiseTotaleValeur);
            return Math.min(montant, this.sousTotal);
        },
        get totalNet() { return this.sousTotal - this.remiseTotaleMontant; },
        get totalPaiements() { return this.paiements.reduce((s, p) => s + (Number(p.montant) || 0), 0); },
        ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: null }); },
        retirerPaiement(i) { if (this.paiements.length > 1) this.paiements.splice(i, 1); },
        completerPaiement(i) { this.paiements[i].montant = Math.max(this.totalNet - (this.totalPaiements - (Number(this.paiements[i].montant) || 0)), 0); },
    }">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Reprendre le panier — prix courant</h2>
            <a href="{{ route('ventes-en-attente.index', $venteEnAttente->sessionCaisse) }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Qté</th>
                                    <th>Prix</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lignesAffichage as $ligne)
                                    <tr>
                                        <td>{{ $ligne['libelle'] }}</td>
                                        <td>{{ $ligne['quantite'] }}</td>
                                        <td>{{ number_format($ligne['prix_unitaire'], 0, ',', ' ') }} F</td>
                                        <td>{{ number_format($ligne['total'], 0, ',', ' ') }} F</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="3">Sous-total</td>
                                    <td>{{ number_format($sousTotal, 0, ',', ' ') }} F</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <form method="POST" action="{{ route('ventes-en-attente.reprendre', $venteEnAttente) }}">
                    @csrf
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="h6">Remise sur le total</h3>
                            <div class="row g-1">
                                <div class="col-6">
                                    <select name="remise_totale_type" x-model="remiseTotaleType" class="form-select form-select-sm">
                                        <option value="">Sans remise</option>
                                        <option value="montant">Remise (F)</option>
                                        <option value="pourcentage">Remise (%)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="number" name="remise_totale_valeur" x-model.number="remiseTotaleValeur" x-show="remiseTotaleType" min="0" class="form-control form-control-sm" placeholder="Valeur">
                                </div>
                            </div>
                            <p class="mt-2 mb-0 fw-bold">Net à payer : <span x-text="totalNet"></span> F</p>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="h6">Paiement</h3>
                            <template x-for="(paiement, index) in paiements" :key="index">
                                <div class="row g-1 align-items-center mb-2">
                                    <div class="col-5">
                                        <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm">
                                            <option value="">Moyen…</option>
                                            @foreach ($moyensPaiement as $moyen)
                                                <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="0" class="form-control form-control-sm" placeholder="Montant">
                                    </div>
                                    <div class="col-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="completerPaiement(index)">=</button>
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
                            <div class="mt-2 small" :class="totalPaiements === totalNet ? 'text-success' : 'text-danger'">
                                Total réglé : <span x-text="totalPaiements"></span> F
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100" :disabled="totalPaiements !== totalNet">
                        <i class="bi bi-check-circle me-1"></i>Finaliser la vente
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
