<div class="mb-3">
    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
           value="{{ old('nom', $taxe->nom ?? '') }}" required autofocus>
    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="taux" class="form-label">Taux (%)<span class="required-marker">*</span></label>
    <input type="number" name="taux" id="taux" class="form-control @error('taux') is-invalid @enderror"
           value="{{ old('taux', $taxe->taux ?? '') }}" min="0" max="100" required>
    @error('taux') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<x-actif-toggle :checked="$taxe->actif ?? true" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('taxes.index') }}" class="btn btn-link">Annuler</a>
