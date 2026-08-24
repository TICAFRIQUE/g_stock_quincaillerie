@props(['sessions'])

{{-- Pendant gérant/superadmin de x-alerte-session-ancienne (dashboard
     caissier) : ici on liste TOUTE session encore ouverte depuis un jour
     précédent dans le périmètre (magasin pour un gérant, tous magasins pour
     le superadmin) — pas seulement celle de l'utilisateur connecté, puisque
     le gérant/superadmin a l'autorité pour clôturer une session qu'il n'a
     pas ouverte lui-même (caisse.gerer). Rappel non bloquant, comme son
     pendant singulier — jamais un blocage de la vente. --}}
@if ($sessions->isNotEmpty())
    {{-- alert-danger : voir x-alerte-session-ancienne (le $warning custom de
         ce thème est trop terne comme fond de bandeau). --}}
    <div class="alert alert-danger d-flex justify-content-between align-items-start gap-3 mb-3"
         x-data="{
             visible: true,
             cle: 'rappel-sessions-anciennes-{{ $sessions->pluck('id')->sort()->implode('-') }}-{{ now()->toDateString() }}',
             init() { if (localStorage.getItem(this.cle)) { this.visible = false; } },
             fermer() { localStorage.setItem(this.cle, '1'); this.visible = false; },
         }"
         x-show="visible" x-cloak>
        <div>
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>{{ $sessions->count() }}</strong> session{{ $sessions->count() > 1 ? 's' : '' }} de caisse encore
            ouverte{{ $sessions->count() > 1 ? 's' : '' }} depuis un jour précédent :
            <ul class="mb-0 mt-1">
                @foreach ($sessions as $session)
                    <li>
                        <strong>{{ $session->caisse->nom }}</strong> ({{ $session->caisse->magasin->nom }}) —
                        {{ $session->caissier->name }}, depuis le {{ $session->date_ouverture->format('d/m/Y') }}
                        @can('caisse.cloturer')
                            · <a href="{{ route('sessions.cloturer.form', $session) }}" class="alert-link">Clôturer</a>
                        @endcan
                    </li>
                @endforeach
            </ul>
        </div>
        <button type="button" class="btn-close" @click="fermer()" aria-label="Fermer"></button>
    </div>
@endif
