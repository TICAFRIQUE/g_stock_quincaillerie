<div class="mb-4" style="max-width: 480px;">
    <label for="name" class="form-label">Nom du rôle<span class="required-marker">*</span></label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
           value="{{ old('name', $role->name ?? '') }}" required autofocus>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

<h3 class="h6">Permissions</h3>
<p class="text-secondary small">
    @if (auth()->user()->hasRole('Superadmin'))
        La gestion des paramètres généraux n'est pas proposée ici : elle reste réservée au rôle Superadmin.
    @else
        La gestion des rôles, des utilisateurs et des paramètres généraux n'est pas proposée ici :
        elle reste réservée au rôle Superadmin.
    @endif
</p>

@php $selectionnees = old('permissions', $permissionsActuelles ?? []); @endphp

<div class="row g-2 mb-4">
    @foreach ($permissionsParModule as $module => $permissions)
        <div class="col-12 col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h4 class="h6 text-capitalize">{{ $module }}</h4>
                    @foreach ($permissions as $permission)
                        <div class="form-check">
                            <input type="checkbox" name="permissions[]" value="{{ $permission }}" id="perm-{{ $permission }}"
                                   class="form-check-input" {{ in_array($permission, $selectionnees) ? 'checked' : '' }}>
                            <label for="perm-{{ $permission }}" class="form-check-label small">{{ $permission }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>

<button type="submit" class="btn btn-primary">Enregistrer</button>
<a href="{{ route('roles.index') }}" class="btn btn-link">Annuler</a>
