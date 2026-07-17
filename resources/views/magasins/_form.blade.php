<div class="mb-3">
    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
           value="{{ old('nom', $magasin->nom ?? '') }}" required>
    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="adresse" class="form-label">Adresse</label>
    <input type="text" name="adresse" id="adresse" class="form-control @error('adresse') is-invalid @enderror"
           value="{{ old('adresse', $magasin->adresse ?? '') }}">
    @error('adresse') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="telephone" class="form-label">Téléphone</label>
    <input type="text" name="telephone" id="telephone" class="form-control @error('telephone') is-invalid @enderror"
           value="{{ old('telephone', $magasin->telephone ?? '') }}">
    @error('telephone') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<x-actif-toggle :checked="$magasin->actif ?? true" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('magasins.index') }}" class="btn btn-link">Annuler</a>
