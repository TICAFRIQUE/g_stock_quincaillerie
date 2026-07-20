@props(['tout' => false])
@if ($tout)
    <button type="button" class="btn btn-outline-secondary d-print-none" onclick="window.__imprimerRapportComplet()">
        <i class="bi bi-printer me-1"></i>Imprimer
    </button>
    <script>
        window.__imprimerRapportComplet = function () {
            const url = new URL(window.location.href);
            url.searchParams.set('tout', '1');
            window.location.href = url.toString();
        };
        @if (request()->boolean('tout'))
            window.addEventListener('load', () => setTimeout(() => window.print(), 200));
        @endif
    </script>
@else
    <button type="button" class="btn btn-outline-secondary d-print-none" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Imprimer
    </button>
@endif
