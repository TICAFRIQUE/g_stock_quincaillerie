@props(['pdfRoute', 'excelRoute', 'tout' => false])

{{-- L'impression couvre tout le résultat filtré (voir x-bouton-imprimer),
     pas seulement la page affichée à l'écran — PDF/Excel font de même,
     chaque contrôleur applique les mêmes filtres sans pagination. --}}
<x-bouton-imprimer :tout="$tout" />
<a href="{{ $pdfRoute }}" class="btn btn-outline-secondary d-print-none">
    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
</a>
<a href="{{ $excelRoute }}" class="btn btn-outline-secondary d-print-none">
    <i class="bi bi-file-earmark-excel me-1"></i>Excel
</a>
