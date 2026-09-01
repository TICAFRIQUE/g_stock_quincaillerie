@extends('layouts.app')

@section('title', 'Casse / ajustement de stock')

@section('content')
    <h2 class="h4 mb-3">Casse / ajustement de stock</h2>

    <div class="row g-4">
        <div class="col-12 col-lg-7">
            <div class="card" x-data="{ type: {{ Js::from(old('type', '')) }} }">
                <div class="card-body">
                    <form method="POST" action="{{ route('stock.mouvements.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="magasin_id" class="form-label">Destination<span class="required-marker">*</span></label>
                            <select name="magasin_id" id="magasin_id" class="form-select @error('magasin_id') is-invalid @enderror"
                                    onchange="window.location.href = '{{ route('stock.mouvements.create') }}?magasin_id=' + this.value" required>
                                <option value="">— Choisir la destination —</option>
                                @foreach ($magasins as $magasin)
                                    <option value="{{ $magasin->id }}" @selected(old('magasin_id', $magasinId) == $magasin->id)>{{ $magasin->nom }}</option>
                                @endforeach
                            </select>
                            @error('magasin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type de mouvement<span class="required-marker">*</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="type" id="type-casse" value="casse" x-model="type" required>
                                    <label class="form-check-label text-danger" for="type-casse"><i class="bi bi-x-octagon me-1"></i>Casse / perte</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="type" id="type-ajustement" value="ajustement" x-model="type">
                                    <label class="form-check-label" for="type-ajustement"><i class="bi bi-sliders me-1"></i>Ajustement</label>
                                </div>
                            </div>
                            @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3" x-show="type === 'ajustement'" x-cloak>
                            <label class="form-label">Sens de l'ajustement<span class="required-marker">*</span></label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="direction" id="direction-entree" value="entree" :required="type === 'ajustement'" @if (old('direction') === 'entree') checked @endif>
                                    <label class="form-check-label text-success" for="direction-entree"><i class="bi bi-plus-lg me-1"></i>Entrée (+)</label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" class="form-check-input" name="direction" id="direction-sortie" value="sortie" :required="type === 'ajustement'" @if (old('direction') === 'sortie') checked @endif>
                                    <label class="form-check-label" for="direction-sortie"><i class="bi bi-dash-lg me-1"></i>Sortie (−)</label>
                                </div>
                            </div>
                            @error('direction') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="row g-2">
                            <div class="col-8 mb-3">
                                <label for="produit_id" class="form-label">Produit<span class="required-marker">*</span></label>
                                <select name="produit_id" id="produit_id" class="form-select @error('produit_id') is-invalid @enderror"
                                        @disabled(! $magasinId) required>
                                    <option value="">{{ $magasinId ? '— Choisir —' : '— Choisir le magasin d\'abord —' }}</option>
                                    @foreach ($produits as $produit)
                                        <option value="{{ $produit->id }}" @selected(old('produit_id') == $produit->id)>
                                            {{ $produit->sku }} — {{ $produit->libelle_affichage }}{{ $magasinId ? ' (Stock : '.($produit->stock_magasin ?? 0).')' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('produit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-4 mb-3">
                                <label for="quantite" class="form-label">Quantité (pièces)<span class="required-marker">*</span></label>
                                <input type="number" name="quantite" id="quantite" class="form-control @error('quantite') is-invalid @enderror"
                                       value="{{ old('quantite') }}" min="1" required>
                                @error('quantite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="motif" class="form-label">Motif</label>
                            <input type="text" name="motif" id="motif" class="form-control @error('motif') is-invalid @enderror"
                                   value="{{ old('motif') }}" placeholder="Optionnel">
                            @error('motif') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                        <a href="{{ route('stock.index') }}" class="btn btn-link">Annuler</a>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-5">
            <div class="card bg-light-subtle">
                <div class="card-body">
                    <h3 class="h6"><i class="bi bi-info-circle me-1"></i>À quoi correspond chaque type ?</h3>
                    <dl class="small mb-0">
                        <dt class="text-danger"><i class="bi bi-x-octagon me-1"></i>Casse / perte</dt>
                        <dd>Marchandise cassée, volée ou périmée : le stock diminue toujours. Aucun sens à choisir, la sortie est automatique.</dd>
                        <dt class="text-secondary"><i class="bi bi-sliders me-1"></i>Ajustement</dt>
                        <dd class="mb-2">Correction d'un écart constaté hors inventaire formel (ex. erreur de saisie antérieure). Choisissez le sens :</dd>
                        <dd class="ms-3"><span class="text-success"><i class="bi bi-plus-lg me-1"></i>Entrée</span> — le stock réel est supérieur à ce qui est enregistré.</dd>
                        <dd class="ms-3"><span class="text-secondary"><i class="bi bi-dash-lg me-1"></i>Sortie</span> — le stock réel est inférieur à ce qui est enregistré.</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initSelect2('#produit_id', {
                placeholder: {{ Js::from($magasinId ? '— Choisir —' : "— Choisir le magasin d'abord —") }},
            });
        });
    </script>
@endpush
