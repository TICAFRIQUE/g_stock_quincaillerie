@props(['pdfRoute', 'excelRoute', 'tout' => false])

{{-- L'impression ouvre directement le PDF (voir x-bouton-imprimer,
     ?imprimer=1) : même rendu que le bouton "PDF" ci-dessous, jamais un
     print() du HTML de la page qui rendrait différemment. --}}
<x-bouton-imprimer :tout="$tout" :pdf-route="$pdfRoute" />
<a href="{{ $pdfRoute }}" class="btn btn-outline-secondary d-print-none">
    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
</a>
<a href="{{ $excelRoute }}" class="btn btn-outline-secondary d-print-none">
    <i class="bi bi-file-earmark-excel me-1"></i>Excel
</a>
