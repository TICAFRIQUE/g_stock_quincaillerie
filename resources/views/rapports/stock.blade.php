@extends('layouts.app')

@section('title', 'Valeur du stock')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Valeur du stock</h2>
        <button type="button" class="btn btn-outline-secondary d-print-none" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimer
        </button>
    </div>

    <div class="card mb-3" style="max-width: 320px;">
        <div class="card-body">
            <div class="text-secondary small">Valeur globale (CMP)</div>
            <div class="fs-4 fw-medium">{{ number_format($valeurGlobale, 0, ',', ' ') }} F</div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Magasin</th>
                        <th>Quantité (pièces)</th>
                        <th>Valeur (CMP)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($parMagasin as $ligne)
                        <tr>
                            <td>{{ $ligne->magasin_nom }}</td>
                            <td>{{ $ligne->quantite_totale }}</td>
                            <td>{{ number_format($ligne->valeur_totale, 0, ',', ' ') }} F</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">Aucun stock enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
