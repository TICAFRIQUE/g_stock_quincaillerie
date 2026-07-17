<div class="mb-3">
    <label for="name" class="form-label">Nom<span class="required-marker">*</span></label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $utilisateur->name ?? '') }}" required autofocus>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">E-mail<span class="required-marker">*</span></label>
    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror"
           value="{{ old('email', $utilisateur->email ?? '') }}" required>
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@unless (isset($utilisateur))
    <div class="alert alert-info small">
        <i class="bi bi-info-circle me-1"></i>Un mot de passe sera généré automatiquement et envoyé par e-mail à l'utilisateur.
    </div>
@endunless

<div class="mb-3">
    <label for="role" class="form-label">Rôle<span class="required-marker">*</span></label>
    <select name="role" id="role" class="form-select @error('role') is-invalid @enderror" required>
        <option value="">— Choisir —</option>
        @foreach ($roles as $roleOption)
            <option value="{{ $roleOption->name }}" @selected(old('role', $utilisateur?->roles?->first()?->name ?? '') === $roleOption->name)>
                {{ $roleOption->name }}
            </option>
        @endforeach
    </select>
    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<div class="mb-3">
    <label for="magasin_id" class="form-label">Magasin de rattachement</label>
    <select name="magasin_id" id="magasin_id" class="form-select @error('magasin_id') is-invalid @enderror">
        <option value="">— Aucun (multi-magasin) —</option>
        @foreach ($magasins as $magasin)
            <option value="{{ $magasin->id }}" @selected(old('magasin_id', $utilisateur->magasin_id ?? '') == $magasin->id)>
                {{ $magasin->nom }}
            </option>
        @endforeach
    </select>
    @error('magasin_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    <div class="form-text">Obligatoire en pratique pour un caissier ; laissez vide pour un rôle multi-magasin.</div>
</div>

<x-actif-toggle :checked="$utilisateur->actif ?? true" />

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('utilisateurs.index') }}" class="btn btn-link">Annuler</a>
