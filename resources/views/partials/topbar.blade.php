<div class="dropdown">
    <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        <span class="fw-medium">{{ auth()->user()->name }}</span>
        @if (auth()->user()->magasin)
            <span class="badge text-bg-secondary">{{ auth()->user()->magasin->nom }}</span>
        @endif
    </button>
    <ul class="dropdown-menu dropdown-menu-end">
        <li><h6 class="dropdown-header">{{ auth()->user()->email }}</h6></li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item">Se déconnecter</button>
            </form>
        </li>
    </ul>
</div>
