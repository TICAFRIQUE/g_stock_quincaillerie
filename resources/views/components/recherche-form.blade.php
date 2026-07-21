@props(['action', 'placeholder' => 'Rechercher…', 'nom' => 'recherche', 'autresParams' => []])
<form method="GET" action="{{ $action }}" class="mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-end">
        <div class="input-group" style="max-width: 400px;">
            <input type="text" name="{{ $nom }}" class="form-control" placeholder="{{ $placeholder }}" value="{{ request($nom) }}">
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </div>
        {{ $slot }}
        @if (request()->hasAny(array_merge([$nom, 'tri', 'direction'], $autresParams)))
            <a href="{{ $action }}" class="btn btn-outline-danger" title="Réinitialiser la recherche">
                <i class="bi bi-x-circle"></i>
            </a>
        @endif
    </div>
</form>
