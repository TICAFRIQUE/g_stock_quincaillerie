<div class="mb-3">
    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
           value="{{ old('nom', $devise->nom ?? '') }}" placeholder="ex. Franc CFA" required autofocus>
    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="abreviation" class="form-label">Abréviation<span class="required-marker">*</span></label>
    <input type="text" name="abreviation" id="abreviation" class="form-control @error('abreviation') is-invalid @enderror"
           value="{{ old('abreviation', $devise->abreviation ?? '') }}" placeholder="ex. FCFA" maxlength="20" required>
    @error('abreviation') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Affichée telle quelle après chaque montant (ex. "15 000 FCFA").</div>
</div>

<x-actif-toggle :checked="$devise->actif ?? true" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('devises.index') }}" class="btn btn-link">Annuler</a>
