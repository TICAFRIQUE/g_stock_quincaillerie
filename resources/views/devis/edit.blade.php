@extends('layouts.app')

@section('title', "Modifier le devis {$devis->numero}")

@section('content')
    <div x-data="devisApp({{ \Illuminate\Support\Js::from($produits) }}, {{ \Illuminate\Support\Js::from($panierInitial) }}, {{ \Illuminate\Support\Js::from($taxes->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->nom, 'taux' => $t->taux])) }})">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="h4 mb-1">Modifier le devis {{ $devis->numero }}</h2>
                <p class="text-secondary small mb-0">Client : {{ $devis->client->nom }}</p>
            </div>
            <a href="{{ route('devis.show', $devis) }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour au devis
            </a>
        </div>

        <div class="row g-3 align-items-start">
            <div class="col-12 col-lg-8">
                @include('devis._panier')
            </div>

            <div class="col-12 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <form id="formDevis" method="POST" action="{{ route('devis.update', $devis) }}">
                            @csrf
                            @method('PUT')

                            <template x-for="(ligne, index) in panier" :key="'d'+index">
                                <span>
                                    <input type="hidden" :name="'lignes['+index+'][produit_id]'" :value="ligne.produit_id">
                                    <input type="hidden" :name="'lignes['+index+'][unite_vente_id]'" :value="ligne.unite_vente_id">
                                    <input type="hidden" :name="'lignes['+index+'][taxe_id]'" :value="ligne.taxe_id">
                                    <input type="hidden" :name="'lignes['+index+'][quantite]'" :value="ligne.quantite">
                                    <input type="hidden" :name="'lignes['+index+'][remise_type]'" :value="ligne.remise_type">
                                    <input type="hidden" :name="'lignes['+index+'][remise_valeur]'" :value="ligne.remise_valeur">
                                </span>
                            </template>

                            <button type="button" class="btn btn-primary w-100"
                                    :disabled="panier.length === 0 || aUnDoublon || aUneLigneNonChoisie"
                                    @click="declencherEnregistrement($event, 'formDevis')"
                                    data-form-id="formDevis"
                                    data-message="Enregistrer les modifications de ce devis ?"
                                    data-button-label="Enregistrer" data-button-class="btn-primary">
                                <i class="bi bi-check-circle me-1"></i>Enregistrer
                            </button>
                        </form>
                    </div>
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
