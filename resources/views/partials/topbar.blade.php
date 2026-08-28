<div class="d-flex align-items-center gap-2">
@can('rapport.voir')
    @include('partials._cloche-notifications', [
        'type' => \App\Notifications\EcartCaisseDetecte::class,
        'icon' => 'bi-exclamation-triangle',
        'titre' => 'Écarts de caisse',
    ])
    @include('partials._cloche-notifications', [
        'type' => \App\Notifications\SessionOuverteTropLongtemps::class,
        'icon' => 'bi-hourglass-split',
        'titre' => 'Sessions ouvertes',
    ])
    @include('partials._cloche-notifications', [
        'type' => \App\Notifications\VenteSignalee::class,
        'icon' => 'bi-flag',
        'titre' => 'Ventes signalées',
    ])
    @include('partials._cloche-stock-seuil')
@endcan

<div class="dropdown">
    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="fw-medium">{{ auth()->user()->name }}</span>
        @if (auth()->user()->magasin)
            <span class="badge text-bg-secondary">{{ auth()->user()->magasin->nom }}</span>
        @endif
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">{{ '@'.auth()->user()->username }}</h6></li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <a class="dropdown-item" href="{{ route('guide') }}">
                <i class="bi bi-question-circle me-1"></i>Guide d'utilisation
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item">Se déconnecter</button>
            </form>
        </li>
    </ul>
</div>
</div>
