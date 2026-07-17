<div class="mb-3">
    <label for="magasin_id" class="form-label">Magasin<span class="required-marker">*</span></label>
    <select name="magasin_id" id="magasin_id" class="form-select @error('magasin_id') is-invalid @enderror" required>
        <option value="">— Choisir —</option>
        @foreach ($magasins as $magasin)
            <option value="{{ $magasin->id }}" @selected(old('magasin_id', $caisse->magasin_id ?? '') == $magasin->id)>{{ $magasin->nom }}</option>
        @endforeach
    </select>
    @error('magasin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
           value="{{ old('nom', $caisse->nom ?? '') }}" placeholder="Caisse 1" required>
    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<x-actif-toggle :checked="$caisse->actif ?? true" label="Active" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('caisses.index') }}" class="btn btn-link">Annuler</a>
