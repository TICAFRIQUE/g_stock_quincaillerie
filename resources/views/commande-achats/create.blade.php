@extends('layouts.app')

@section('title', "Nouvelle commande d'achat")

@section('content')
    <h2 class="h4 mb-3">Nouvelle commande d'achat</h2>

    <div class="row">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-body">
                    <form id="formCommandeAchat" method="POST" action="{{ route('commande-achats.store') }}" x-data="{
                            lignes: {{ Js::from(old('lignes', [['produit_id' => '', 'unite_vente_id' => '', 'quantite' => '', 'prix_achat' => '']])) }},
                            unitesParProduit: {{ Js::from($unitesParProduit) }},
                            erreurs: {{ Js::from($errors->getMessages()) }},
                            actionSoumission: {{ Js::from(old('action', 'brouillon')) }},
                            estDoublon(index) {
                                const id = this.lignes[index].produit_id;
                                return !!id && this.lignes.some((l, i) => i !== index && l.produit_id === id);
                            },
                            get aUnDoublon() {
                                return this.lignes.some((l, i) => this.estDoublon(i));
                            },
                            unitesDisponibles(produitId) {
                                const infos = this.unitesParProduit[produitId];
                                if (!infos) return [];
                                return [{ id: '', libelle: infos.basePiece, facteur: 1 }, ...infos.variantes];
                            },
                            uniteChoisie(ligne) {
                                const options = this.unitesDisponibles(ligne.produit_id);
                                return options.find((o) => String(o.id) === String(ligne.unite_vente_id || '')) || options[0] || { libelle: 'pièce', facteur: 1 };
                            },
                            quantitePieces(ligne) {
                                const quantite = Number(ligne.quantite) || 0;
                                return quantite * this.uniteChoisie(ligne).facteur;
                            },
                            declencherValidation(event) {
                                const form = document.getElementById('formCommandeAchat');
                                if (!form.checkValidity()) {
                                    form.classList.add('was-validated');
                                    form.reportValidity();
                                    return;
                                }
                                this.actionSoumission = 'valider';
                                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show(event.currentTarget);
                            },
                        }">
                        @csrf
                        <input type="hidden" name="action" x-model="actionSoumission">

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="numero" class="form-label">Numéro</label>
                                <input type="text" name="numero" id="numero" class="form-control @error('numero') is-invalid @enderror"
                                       value="{{ old('numero') }}" placeholder="Génération automatique">
                                @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="fournisseur_id" class="form-label">Fournisseur<span class="required-marker">*</span></label>
                                <select name="fournisseur_id" id="fournisseur_id" class="form-select @error('fournisseur_id') is-invalid @enderror" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($fournisseurs as $fournisseur)
                                        <option value="{{ $fournisseur->id }}" @selected(old('fournisseur_id') == $fournisseur->id)>{{ $fournisseur->nom }}</option>
                                    @endforeach
                                </select>
                                @error('fournisseur_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="magasin_id" class="form-label">Magasin (destination)<span class="required-marker">*</span></label>
                                <select name="magasin_id" id="magasin_id" class="form-select @error('magasin_id') is-invalid @enderror" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($magasins as $magasin)
                                        <option value="{{ $magasin->id }}" @selected(old('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                                    @endforeach
                                </select>
                                @error('magasin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3 mb-3">
                                <label for="date_commande" class="form-label">Date<span class="required-marker">*</span></label>
                                <input type="date" name="date_commande" id="date_commande" class="form-control @error('date_commande') is-invalid @enderror"
                                       value="{{ old('date_commande', now()->toDateString()) }}" required>
                                @error('date_commande') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="h6 mb-0">Lignes de la commande</h3>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="lignes.push({ produit_id: '', unite_vente_id: '', quantite: '', prix_achat: '' })">
                                <i class="bi bi-plus-lg"></i> Ajouter une ligne
                            </button>
                        </div>

                        <template x-for="(ligne, index) in lignes" :key="index">
                            <div class="border rounded p-2 mb-2">
                                <div class="row g-2 align-items-end mb-2">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label small">Produit<span class="required-marker">*</span></label>
                                        <select :name="'lignes['+index+'][produit_id]'" x-model="ligne.produit_id" @change="ligne.unite_vente_id = ''"
                                                class="form-select form-select-sm produit-ligne-select" :class="{ 'is-invalid': estDoublon(index) }"
                                                x-init="window.initSelect2($el, { width: '100%' })" required>
                                            <option value="">— Choisir —</option>
                                            @foreach ($produits as $produit)
                                                <option value="{{ $produit->id }}">{{ $produit->sku }} — {{ $produit->libelle_affichage }}</option>
                                            @endforeach
                                        </select>
                                        <div class="text-danger small mt-1" x-show="estDoublon(index)" x-cloak>Ce produit est déjà présent dans une autre ligne.</div>
                                        <div class="text-danger small mt-1" x-show="erreurs['lignes.'+index+'.produit_id']" x-text="(erreurs['lignes.'+index+'.produit_id'] || [])[0]"></div>
                                    </div>
                                    <div class="col-6 col-md-5">
                                        <label class="form-label small">Unité d'achat<span class="required-marker">*</span></label>
                                        <select :name="'lignes['+index+'][unite_vente_id]'" x-model="ligne.unite_vente_id" class="form-select form-select-sm">
                                            <template x-for="option in unitesDisponibles(ligne.produit_id)" :key="option.id">
                                                <option :value="option.id" x-text="option.libelle"></option>
                                            </template>
                                        </select>
                                        <div class="text-danger small mt-1" x-show="erreurs['lignes.'+index+'.unite_vente_id']" x-text="(erreurs['lignes.'+index+'.unite_vente_id'] || [])[0]"></div>
                                    </div>
                                    <div class="col-md-1 text-md-end">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger" title="Retirer" @click="lignes.length > 1 && lignes.splice(index, 1)">
                                            <i class="bi bi-x-lg"></i>
                                            <span class="visually-hidden">Retirer</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="row g-2 align-items-end">
                                    <div class="col-4">
                                        <label class="form-label small" x-text="'Quantité (' + uniteChoisie(ligne).libelle + ') *'"></label>
                                        <input type="number" :name="'lignes['+index+'][quantite]'" x-model="ligne.quantite" class="form-control form-control-sm" min="1" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small" x-text="'Prix d\'achat (' + uniteChoisie(ligne).libelle + ', F) *'"></label>
                                        <input type="number" :name="'lignes['+index+'][prix_achat]'" x-model="ligne.prix_achat" class="form-control form-control-sm" min="0" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label small">Qté en pièces (stock)</label>
                                        <input type="text" class="form-control form-control-sm" :value="quantitePieces(ligne)" disabled>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="button" class="btn btn-sm btn-outline-primary mb-2" @click="lignes.push({ produit_id: '', unite_vente_id: '', quantite: '', prix_achat: '' })">
                            <i class="bi bi-plus-lg"></i> Ajouter une ligne
                        </button>

                        @error('lignes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('lignes.*.quantite') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('lignes.*.prix_achat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                        <div class="text-danger small mt-2" x-show="aUnDoublon" x-cloak>
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Un même produit ne peut pas apparaître sur plusieurs lignes : corrigez avant d'enregistrer.
                        </div>

                        <hr>

                        <button type="submit" class="btn btn-outline-primary" @click="actionSoumission = 'brouillon'" :disabled="aUnDoublon">
                            Enregistrer en brouillon
                        </button>
                        @if ($peutValider)
                            <button type="button" class="btn btn-success" :disabled="aUnDoublon"
                                    @click="declencherValidation($event)"
                                    data-form-id="formCommandeAchat"
                                    data-message="Valider cet achat maintenant ? Le stock sera mis à jour immédiatement et cette action est irréversible."
                                    data-button-label="Valider l'achat" data-button-class="btn-success">
                                <i class="bi bi-check-circle me-1"></i>Valider l'achat
                            </button>
                        @endif
                        <a href="{{ route('commande-achats.index') }}" class="btn btn-link">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initSelect2('#fournisseur_id');
        });
    </script>
@endpush
