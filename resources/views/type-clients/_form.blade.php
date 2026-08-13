<div class="mb-3">
    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
           value="{{ old('nom', $typeClient->nom ?? '') }}" required autofocus>
    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<x-actif-toggle :checked="$typeClient->actif ?? true" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('type-clients.index') }}" class="btn btn-link">Annuler</a>
