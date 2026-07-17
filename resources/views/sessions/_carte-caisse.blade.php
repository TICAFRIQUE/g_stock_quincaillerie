@php $sessionOuverte = $caisse->sessionCaisses->first(); @endphp
<div class="col-12 col-md-6 col-lg-4">
    <div class="card h-100">
        <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h3 class="h6 mb-0">{{ $caisse->nom }}</h3>
                @if ($sessionOuverte)
                    <span class="badge text-bg-warning">Occupée</span>
                @else
                    <span class="badge text-bg-success">Libre</span>
                @endif
            </div>
            <p class="text-secondary small mb-3">{{ $caisse->magasin->nom }}</p>

            @if ($sessionOuverte)
                <p class="small mb-3">
                    Ouverte par <strong>{{ $sessionOuverte->caissier->name }}</strong>
                    depuis {{ $sessionOuverte->date_ouverture->format('H:i') }}
                </p>
                @if ($sessionOuverte->caissier_id === auth()->id() || auth()->user()->can('caisse.gerer'))
                    <a href="{{ route('sessions.show', $sessionOuverte) }}" class="btn btn-outline-primary mt-auto">
                        <i class="bi bi-arrow-right-circle me-1"></i>Continuer
                    </a>
                @endif
            @else
                @can('caisse.ouvrir')
                    <a href="{{ route('sessions.create', $caisse) }}" class="btn btn-primary mt-auto">
                        <i class="bi bi-unlock me-1"></i>Ouvrir une session
                    </a>
                @endcan
            @endif
        </div>
    </div>
</div>
