@props(['action', 'placeholder' => 'Rechercher…', 'nom' => 'recherche', 'autresParams' => []])
<form method="GET" action="{{ $action }}" class="mb-3">
    <div class="d-flex flex-wrap gap-2 align-items-end">
        {{-- Filtre automatique pendant la frappe (demande client) : la
             recherche se relance seule après une courte pause, sans attendre
             Entrée ni un clic — le bouton/l'icône loupe reste comme filet de
             secours (double-clic rapide, JS désactivé). --}}
        <div class="input-group" style="max-width: 400px;" x-data="{ debounce: null }">
            <input type="text" name="{{ $nom }}" class="form-control" placeholder="{{ $placeholder }}" value="{{ request($nom) }}"
                   @input="clearTimeout(debounce); debounce = setTimeout(() => $el.form.submit(), 400)">
            <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </div>
        {{ $slot }}
        @if (request()->hasAny(array_merge([$nom, 'tri', 'direction'], $autresParams)))
            <x-bouton-reinitialiser :route="$action" />
        @endif
        {{-- Boutons d'impression/export : optionnels, passés via <x-slot:export>.
             ms-auto (pas de sous-groupe + justify-content-between, qui forçait
             les deux groupes à se rétrécir l'un l'autre et cassait le retour à
             la ligne naturel des filtres) : chaque champ garde sa largeur
             normale, seul ce bloc est poussé à droite s'il reste de la place. --}}
        @isset($export)
            <div class="d-flex gap-2 flex-wrap d-print-none ms-auto">
                {{ $export }}
            </div>
        @endisset
    </div>
</form>
