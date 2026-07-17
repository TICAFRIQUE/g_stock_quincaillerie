<div class="row">
    <div class="col-md-6 mb-3">
        <label for="sku" class="form-label">SKU</label>
        <input type="text" name="sku" id="sku" class="form-control @error('sku') is-invalid @enderror"
               value="{{ old('sku', $produit->sku ?? '') }}" placeholder="Laissez vide pour génération automatique">
        @error('sku') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Laissez vide pour générer un SKU automatiquement (ex. PRD-000123).</div>
    </div>

    <div class="col-md-6 mb-3">
        <label for="code_barre" class="form-label">Code-barres</label>
        <input type="text" name="code_barre" id="code_barre" class="form-control @error('code_barre') is-invalid @enderror"
               value="{{ old('code_barre', $produit->code_barre ?? '') }}">
        @error('code_barre') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row" x-data="{
        nom: {{ Js::from(old('nom', $produit->nom ?? '')) }},
        libelleDistinctif: {{ Js::from(old('libelle_distinctif', $produit->libelle_distinctif ?? '')) }},
        nomsExistants: {{ Js::from($nomsExistants->map(fn ($n) => mb_strtolower(trim($n)))) }},
        get nomEnDouble() {
            return this.nom.trim() !== '' && this.nomsExistants.includes(this.nom.trim().toLowerCase());
        },
    }">
    <div class="col-md-6 mb-3">
        <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
        <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
               x-model="nom" required>
        @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6 mb-3">
        <label for="libelle_distinctif" class="form-label">Libellé distinctif</label>
        <input type="text" name="libelle_distinctif" id="libelle_distinctif" class="form-control @error('libelle_distinctif') is-invalid @enderror"
               x-model="libelleDistinctif" placeholder="Qualité, motif, provenance…">
        @error('libelle_distinctif') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">Permet de distinguer deux produits qui portent le même nom.</div>
        <div class="form-text text-warning" x-show="nomEnDouble && libelleDistinctif.trim() === ''" x-cloak>
            <i class="bi bi-exclamation-triangle-fill me-1"></i>Un autre produit s'appelle déjà « <span x-text="nom"></span> ». Renseignez un libellé distinctif pour les différencier à la caisse.
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 mb-3">
        <label for="categorie_id" class="form-label">Catégorie<span class="required-marker">*</span></label>
        <select name="categorie_id" id="categorie_id" class="form-select @error('categorie_id') is-invalid @enderror" required>
            <option value="">— Choisir —</option>
            @foreach ($categories->whereNull('parent_id') as $parente)
                <option value="{{ $parente->id }}" @selected(old('categorie_id', $produit->categorie_id ?? '') == $parente->id)>
                    {{ $parente->nom }}
                </option>
                @foreach ($categories->where('parent_id', $parente->id) as $enfant)
                    <option value="{{ $enfant->id }}" @selected(old('categorie_id', $produit->categorie_id ?? '') == $enfant->id)>
                        — {{ $enfant->nom }}
                    </option>
                @endforeach
            @endforeach
        </select>
        @error('categorie_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="prix_piece" class="form-label">Prix pièce (F CFA)<span class="required-marker">*</span></label>
        <input type="number" name="prix_piece" id="prix_piece" class="form-control @error('prix_piece') is-invalid @enderror"
               value="{{ old('prix_piece', $produit->prix_piece ?? '') }}" min="0" required>
        @error('prix_piece') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="seuil_alerte" class="form-label">Seuil d'alerte (pièces)<span class="required-marker">*</span></label>
        <input type="number" name="seuil_alerte" id="seuil_alerte" class="form-control @error('seuil_alerte') is-invalid @enderror"
               value="{{ old('seuil_alerte', $produit->seuil_alerte ?? 0) }}" min="0" required>
        @error('seuil_alerte') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

@unless (isset($produit))
    <div class="mb-3 p-3 bg-light rounded" x-data="{ lots: {{ \Illuminate\Support\Js::from(old('unites_vente', [])) }} }">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span class="form-label mb-0">Unités de vente (lots)</span>
                <div class="form-text mt-0">Optionnel — la pièce reste toujours vendable. Ajoutez des lots (ex. « Lot de 5 ») avec leur propre prix.</div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary text-nowrap" @click="lots.push({ facteur: '', prix: '' })">
                <i class="bi bi-plus-lg"></i> Ajouter une unité de vente
            </button>
        </div>

        <template x-if="lots.length > 0">
            <div class="mt-3">
                <template x-for="(lot, index) in lots" :key="index">
                    <div class="row g-2 align-items-end mb-2">
                        <div class="col-5">
                            <label class="form-label small">Pièces par lot<span class="required-marker">*</span></label>
                            <input type="number" :name="'unites_vente['+index+'][facteur]'" x-model="lot.facteur" class="form-control form-control-sm" min="2" placeholder="Ex. 5" required>
                            <div class="form-text">Le nom (« Lot de 5 »…) est généré automatiquement.</div>
                        </div>
                        <div class="col-5">
                            <label class="form-label small">Prix (F)<span class="required-marker">*</span></label>
                            <input type="number" :name="'unites_vente['+index+'][prix]'" x-model="lot.prix" class="form-control form-control-sm" min="0" required>
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger" title="Retirer" @click="lots.splice(index, 1)">
                                <i class="bi bi-x-lg"></i>
                                <span class="visually-hidden">Retirer</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        @error('unites_vente.*.facteur') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        @error('unites_vente.*.prix') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
@endunless

<div class="mb-3">
    <label for="image" class="form-label">Image</label>
    <input type="file" name="image" id="image" class="form-control @error('image') is-invalid @enderror" accept="image/*">
    @error('image') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<x-actif-toggle :checked="$produit->actif ?? true" />
