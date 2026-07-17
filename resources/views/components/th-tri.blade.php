@props(['champ', 'label'])
@php
    $triActuel = request('tri');
    $directionActuelle = request('direction', 'desc');
    $estActif = $triActuel === $champ;
    $prochaineDirection = $estActif && $directionActuelle === 'asc' ? 'desc' : 'asc';
    $params = array_merge(request()->except('page'), ['tri' => $champ, 'direction' => $prochaineDirection]);
@endphp
<th>
    <a href="{{ request()->url().'?'.http_build_query($params) }}"
       class="text-decoration-none text-reset d-inline-flex align-items-center gap-1">
        {{ $label }}
        @if ($estActif)
            <i class="bi bi-caret-{{ $directionActuelle === 'asc' ? 'up' : 'down' }}-fill small"></i>
        @else
            <i class="bi bi-arrow-down-up small text-secondary opacity-50"></i>
        @endif
    </a>
</th>
