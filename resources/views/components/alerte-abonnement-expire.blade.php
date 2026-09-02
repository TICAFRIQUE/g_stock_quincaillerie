@php
    $joursRestants = \App\Models\Abonnement::joursRestants();
@endphp
@if ($joursRestants !== null && $joursRestants <= 5)
    {{-- alert-danger : le $warning ambre de ce thème est trop terne comme fond
         de bandeau (voir components/alerte-sessions-anciennes.blade.php). --}}
    <div class="alert alert-danger d-flex justify-content-between align-items-start gap-3 mb-3"
         x-data="{
             visible: true,
             cle: 'rappel-abonnement-{{ $joursRestants }}-{{ now()->toDateString() }}',
             init() { if (localStorage.getItem(this.cle)) { this.visible = false; } },
             fermer() { localStorage.setItem(this.cle, '1'); this.visible = false; },
         }"
         x-show="visible" x-cloak>
        <div>
            <i class="bi bi-calendar-x-fill me-2"></i>
            @if ($joursRestants === 0)
                <strong>Votre abonnement expire aujourd'hui.</strong>
            @else
                <strong>Votre abonnement expire dans {{ $joursRestants }} jour{{ $joursRestants > 1 ? 's' : '' }}.</strong>
            @endif
            {{-- Vers "Mon abonnement" pour tout le monde (formules disponibles +
                 coordonnées de contact, ou raccourci Gestion abonnement pour un
                 Superadmin/développeur — voir abonnement/mon.blade.php) — sauf
                 si on y est déjà, où le lien n'aurait aucun sens. --}}
            @unless (request()->routeIs('abonnement.mon'))
                <a href="{{ route('abonnement.mon') }}" class="alert-link">Renouveler maintenant</a>
            @endunless
        </div>
        <button type="button" class="btn-close" @click="fermer()" aria-label="Fermer"></button>
    </div>
@endif
