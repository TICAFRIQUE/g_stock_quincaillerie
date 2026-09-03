@props(['pdfRoute', 'excelRoute', 'tout' => false])

{{-- Groupées (btn-group) plutôt que trois boutons séparés : rendu plus
     compact et cohérent qu'une rangée de gros boutons espacés. L'impression
     ouvre directement le PDF (voir x-bouton-imprimer, ?imprimer=1) : même
     rendu que le bouton "PDF" ci-dessous, jamais un print() du HTML de la
     page qui rendrait différemment. --}}
<div class="btn-group d-print-none" role="group">
    <x-bouton-imprimer :tout="$tout" :pdf-route="$pdfRoute" />
    <a href="{{ $pdfRoute }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
    </a>
    <a href="{{ $excelRoute }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-file-earmark-excel me-1"></i>Excel
    </a>
</div>
