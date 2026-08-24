@props(['session'])

{{-- Rappel non bloquant (règle 9 : une session peut légitimement s'étendre
     sur plusieurs jours) — jamais un blocage qui empêcherait de vendre, juste
     une invitation à clôturer le travail de la veille. Un gérant/superadmin
     peut lui aussi détenir sa propre session (règle 6 : vendre exige une
     session pour TOUS les rôles), donc ce composant est partagé par les
     trois tableaux de bord (caissier, gérant, superadmin), pas seulement
     celui du caissier. Rejoué chaque jour tant que la session reste ouverte
     (clé localStorage datée du jour), pour ne pas harceler dans la même
     journée une fois fermé. --}}
@if ($session && $session->estOuverteDepuisJourPrecedent())
    {{-- alert-danger plutôt que alert-warning : le $warning custom de ce
         thème est délibérément sombre/mat pour rester lisible en texte (voir
         resources/sass/app.scss), ce qui le rend terne en fond de bandeau —
         $danger reste le seul accent déjà réglé pour un fond plein et
         attirant (voir .text-bg-danger juste à côté dans app.scss). --}}
    <div class="alert alert-danger d-flex justify-content-between align-items-start gap-3 mb-3"
         x-data="{
             visible: true,
             cle: 'rappel-session-ancienne-{{ $session->id }}-{{ now()->toDateString() }}',
             init() { if (localStorage.getItem(this.cle)) { this.visible = false; } },
             fermer() { localStorage.setItem(this.cle, '1'); this.visible = false; },
         }"
         x-show="visible" x-cloak>
        <div>
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            Votre session sur <strong>{{ $session->caisse->nom }}</strong> ({{ $session->caisse->magasin->nom }}) est
            ouverte depuis le <strong>{{ $session->date_ouverture->format('d/m/Y') }}</strong>. Clôturer les ventes
            d'hier avant de continuer aujourd'hui, ou poursuivre dans la même session ?
            @can('caisse.cloturer')
                <div class="mt-2">
                    <a href="{{ route('sessions.cloturer.form', $session) }}" class="btn btn-sm btn-danger">
                        <i class="bi bi-lock me-1"></i>Clôturer maintenant
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary ms-1" @click="fermer()">Continuer</button>
                </div>
            @endcan
        </div>
        <button type="button" class="btn-close" @click="fermer()" aria-label="Fermer"></button>
    </div>
@endif
