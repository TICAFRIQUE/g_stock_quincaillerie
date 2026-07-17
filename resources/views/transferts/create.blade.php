@extends('layouts.app')

@section('title', 'Nouveau transfert')

@section('content')
    <h2 class="h4 mb-3">Nouveau transfert de stock</h2>

    <div class="card" style="max-width: 700px;">
        <div class="card-body">
            <form method="POST" action="{{ route('transferts.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="magasin_source_id" class="form-label">Magasin source<span class="required-marker">*</span></label>
                    <select name="magasin_source_id" id="magasin_source_id" class="form-select @error('magasin_source_id') is-invalid @enderror"
                            onchange="window.location.href = '{{ route('transferts.create') }}?magasin_source_id=' + this.value" required>
                        <option value="">— Choisir le magasin de départ —</option>
                        @foreach ($magasins as $magasin)
                            <option value="{{ $magasin->id }}" @selected(old('magasin_source_id', $magasinSourceId) == $magasin->id)>{{ $magasin->nom }}</option>
                        @endforeach
                    </select>
                    @error('magasin_source_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Choisissez d'abord le magasin de départ : seuls les produits qu'il a en stock seront proposés.</div>
                </div>

                <div class="mb-3">
                    <label for="magasin_destination_id" class="form-label">Magasin destination<span class="required-marker">*</span></label>
                    <select name="magasin_destination_id" id="magasin_destination_id" class="form-select @error('magasin_destination_id') is-invalid @enderror" required>
                        <option value="">— Choisir —</option>
                        @foreach ($magasins as $magasin)
                            <option value="{{ $magasin->id }}" @selected(old('magasin_destination_id') == $magasin->id)>{{ $magasin->nom }}</option>
                        @endforeach
                    </select>
                    @error('magasin_destination_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-2">
                    <div class="col-8 mb-3">
                        <label for="produit_id" class="form-label">Produit<span class="required-marker">*</span></label>
                        <select name="produit_id" id="produit_id" class="form-select @error('produit_id') is-invalid @enderror"
                                @disabled(! $magasinSourceId) required>
                            <option value="">
                                {{ $magasinSourceId ? '— Choisir —' : '— Choisir le magasin source d\'abord —' }}
                            </option>
                            @foreach ($produits as $produit)
                                <option value="{{ $produit->id }}" @selected(old('produit_id') == $produit->id)>{{ $produit->sku }} — {{ $produit->libelle_affichage }} (Stock : {{ $produit->stock_magasin }})</option>
                            @endforeach
                        </select>
                        @error('produit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        @if ($magasinSourceId && $produits->isEmpty())
                            <div class="form-text text-danger">Ce magasin n'a aucun produit en stock actuellement.</div>
                        @endif
                    </div>

                    <div class="col-4 mb-4">
                        <label for="quantite" class="form-label">Quantité (pièces)<span class="required-marker">*</span></label>
                        <input type="number" name="quantite" id="quantite" class="form-control @error('quantite') is-invalid @enderror"
                               value="{{ old('quantite') }}" min="1" required>
                        @error('quantite') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Transférer</button>
                <a href="{{ route('transferts.index') }}" class="btn btn-link">Annuler</a>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initSelect2('#produit_id', {
                placeholder: {{ Js::from($magasinSourceId ? '— Choisir —' : "— Choisir le magasin source d'abord —") }},
            });
        });
    </script>
@endpush
