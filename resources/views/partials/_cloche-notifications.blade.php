@props(['type', 'icon', 'titre'])
@php
    $notifications = auth()->user()->notifications()->where('type', $type)->latest()->limit(10)->get();
    $nonLues = auth()->user()->unreadNotifications()->where('type', $type)->count();
@endphp
<div class="dropdown">
    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $titre }}">
        <i class="bi {{ $icon }}"></i>
        @if ($nonLues > 0)
            <span class="badge rounded-pill text-bg-danger position-absolute top-0 start-100 translate-middle">
                {{ $nonLues }}
                <span class="visually-hidden">notifications non lues</span>
            </span>
        @endif
    </button>
    <div class="dropdown-menu dropdown-menu-end p-0" style="width: 340px; max-height: 400px; overflow-y: auto;">
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0">{{ $titre }}</h6>
            @if ($nonLues > 0)
                <form method="POST" action="{{ route('notifications.marquer-lues') }}">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <button type="submit" class="btn btn-link btn-sm p-0">Tout marquer comme lu</button>
                </form>
            @endif
        </div>
        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.ouvrir', $notification->id) }}"
               class="dropdown-item px-3 py-2 border-bottom {{ $notification->read_at ? '' : 'bg-light' }}"
               style="white-space: normal;">
                @if ($type === \App\Notifications\EcartCaisseDetecte::class)
                    <div class="small fw-medium text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        Écart de {{ $notification->data['ecart'] > 0 ? '+' : '' }}{{ montant($notification->data['ecart']) }}
                    </div>
                    <div class="small text-secondary">
                        {{ $notification->data['caissier'] }} — {{ $notification->data['caisse'] }} ({{ $notification->data['magasin'] }})
                    </div>
                    <div class="small text-secondary">{{ \Illuminate\Support\Carbon::parse($notification->data['date_cloture'])->format('d/m/Y H:i') }}</div>
                @elseif ($type === \App\Notifications\SessionOuverteTropLongtemps::class)
                    <div class="small fw-medium text-warning-emphasis">
                        <i class="bi bi-hourglass-split me-1"></i>
                        Session ouverte depuis {{ $notification->data['heures_ecoulees'] }} h
                    </div>
                    <div class="small text-secondary">
                        {{ $notification->data['caissier'] }} — {{ $notification->data['caisse'] }} ({{ $notification->data['magasin'] }})
                    </div>
                    <div class="small text-secondary">Ouverte le {{ \Illuminate\Support\Carbon::parse($notification->data['date_ouverture'])->format('d/m/Y H:i') }}</div>
                @elseif ($type === \App\Notifications\VenteSignalee::class)
                    <div class="small fw-medium text-warning-emphasis">
                        <i class="bi bi-flag-fill me-1"></i>
                        Vente {{ $notification->data['numero'] }} signalée
                    </div>
                    <div class="small text-secondary">
                        {{ $notification->data['caissier'] }} ({{ $notification->data['magasin'] }})
                    </div>
                    <div class="small text-secondary fst-italic">{{ $notification->data['motif'] }}</div>
                @endif
            </a>
        @empty
            <div class="p-3 text-center text-secondary small">Aucune notification.</div>
        @endforelse
    </div>
</div>
