<div class="mb-3">
    <label for="nom" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="nom" id="nom" class="form-control @error('nom') is-invalid @enderror"
           value="{{ old('nom', $categorie->nom ?? '') }}" required autofocus>
    @error('nom') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="parent_id" class="form-label">Catégorie parente</label>
    <select name="parent_id" id="parent_id" class="form-select @error('parent_id') is-invalid @enderror"
            @disabled(($aDesEnfants ?? false))>
        <option value="">— Aucune (catégorie de premier niveau) —</option>
        @foreach ($categoriesParentes as $parente)
            <option value="{{ $parente->id }}" @selected(old('parent_id', $categorie->parent_id ?? '') == $parente->id)>{{ $parente->nom }}</option>
        @endforeach
    </select>
    @error('parent_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    @if ($aDesEnfants ?? false)
        <div class="form-text">Cette catégorie a déjà des sous-catégories : elle doit rester de premier niveau.</div>
    @else
        <div class="form-text">Laissez vide pour une catégorie principale, ou choisissez-en une pour créer une sous-catégorie.</div>
    @endif
</div>

<x-actif-toggle :checked="$categorie->actif ?? true" label="Active" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('categories.index') }}" class="btn btn-link">Annuler</a>
