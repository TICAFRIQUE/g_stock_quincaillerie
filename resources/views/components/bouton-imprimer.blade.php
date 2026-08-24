@props(['tout' => false, 'pdfRoute' => null])
@if ($pdfRoute)
    {{-- Un pdfRoute fourni : "Imprimer" ouvre directement le dialogue
         d'impression sur le même PDF que le bouton "PDF" (voir
         ExporteListe::pdfDepuisListe(), ?imprimer=1 → stream() au lieu de
         download()) — jamais un window.print() du HTML de la page (qui
         rendait différemment : cards, formulaires…). Chargé dans un iframe
         caché plutôt qu'un nouvel onglet, pour ne jamais faire "sortir"
         l'utilisateur de l'écran courant. --}}
    <button type="button" class="btn btn-outline-secondary d-print-none"
        onclick="window.__imprimerPdf(this.dataset.pdfUrl)"
        data-pdf-url="{{ $pdfRoute . (str_contains($pdfRoute, '?') ? '&' : '?') . 'imprimer=1' }}">
        <i class="bi bi-printer me-1"></i>Imprimer
    </button>
    <script>
        window.__imprimerPdf = function (url) {
            let iframe = document.getElementById('__iframeImpressionPdf');
            if (! iframe) {
                iframe = document.createElement('iframe');
                iframe.id = '__iframeImpressionPdf';
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
            }
            iframe.onload = function () {
                setTimeout(() => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }, 200);
            };
            iframe.src = url;
        };
    </script>
@elseif ($tout)
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
