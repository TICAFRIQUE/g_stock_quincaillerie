@extends('layouts.app')

@section('title', 'Nouveau devis')

@section('content')
    <div x-data="devisApp({{ \Illuminate\Support\Js::from($produits) }}, {{ \Illuminate\Support\Js::from($panierInitial) }})">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 mb-0">Nouveau devis</h2>
            <a href="{{ route('devis.index') }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-8">
                @include('devis._panier')
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h6">Informations</h3>

                        <form id="formDevis" method="POST" action="{{ route('devis.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label for="magasin_id" class="form-label">Magasin<span class="required-marker">*</span></label>
                                <select name="magasin_id" id="magasin_id" class="form-select @error('magasin_id') is-invalid @enderror" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($magasins as $magasin)
                                        <option value="{{ $magasin->id }}" {{ old('magasin_id', auth()->user()->magasin_id) == $magasin->id ? 'selected' : '' }}>{{ $magasin->nom }}</option>
                                    @endforeach
                                </select>
                                @error('magasin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="client_id" class="form-label">Client<span class="required-marker">*</span></label>
                                <div class="d-flex gap-1">
                                    <select name="client_id" id="client_id" class="form-select @error('client_id') is-invalid @enderror" required>
                                        <option value="">— Choisir un client —</option>
                                        @foreach ($clients as $c)
                                            <option value="{{ $c->id }}">{{ $c->nom }}{{ $c->telephone ? ' — '.$c->telephone : '' }}</option>
                                        @endforeach
                                    </select>
                                    @can('client.gerer')
                                        <button type="button" class="btn btn-outline-primary btn-icon flex-shrink-0" title="Nouveau client"
                                                onclick="window.ouvrirClientRapide('client_id')">
                                            <i class="bi bi-person-plus"></i>
                                            <span class="visually-hidden">Nouveau client</span>
                                        </button>
                                    @endcan
                                </div>
                                @error('client_id') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>

                            <template x-for="(ligne, index) in panier" :key="'d'+index">
                                <span>
                                    <input type="hidden" :name="'lignes['+index+'][produit_id]'" :value="ligne.produit_id">
                                    <input type="hidden" :name="'lignes['+index+'][unite_vente_id]'" :value="ligne.unite_vente_id">
                                    <input type="hidden" :name="'lignes['+index+'][quantite]'" :value="ligne.quantite">
                                    <input type="hidden" :name="'lignes['+index+'][remise_type]'" :value="ligne.remise_type">
                                    <input type="hidden" :name="'lignes['+index+'][remise_valeur]'" :value="ligne.remise_valeur">
                                </span>
                            </template>

                            <button type="button" class="btn btn-primary w-100"
                                    :disabled="panier.length === 0 || aUnDoublon || aUneLigneNonChoisie"
                                    @click="declencherEnregistrement($event, 'formDevis')"
                                    data-form-id="formDevis"
                                    data-message="Créer ce devis ? Les montants restent indicatifs jusqu'à sa transformation en vente."
                                    data-button-label="Créer le devis" data-button-class="btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Créer le devis
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        @can('client.gerer')
            @include('partials.client-rapide-modal')
        @endcan
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initSelect2('#produit-picker', { placeholder: '— Rechercher un produit à ajouter —' });
            window.initSelect2('#client_id', { placeholder: '— Choisir un client —' });
        });
    </script>
@endpush
