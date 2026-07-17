@extends('layouts.app')

@section('title', 'Écarts de caisse')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Écarts de caisse</h2>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Imprimer
        </button>
    </div>

    <form method="GET" action="{{ route('rapports.ecarts-caisse') }}" class="row g-2 mb-3">
        <div class="col-auto">
            <input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <select name="magasin_id" class="form-select form-select-sm">
                <option value="">Tous les magasins</option>
                @foreach ($magasins as $magasin)
                    <option value="{{ $magasin->id }}" @selected(request('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Caisse</th>
                        <th>Magasin</th>
                        <th>Caissier</th>
                        <th>Clôturée le</th>
                        <th>Théorique</th>
                        <th>Compté</th>
                        <th>Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->caisse->nom }}</td>
                            <td>{{ $session->caisse->magasin->nom }}</td>
                            <td>{{ $session->caissier->name }}</td>
                            <td>{{ $session->date_cloture->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($session->fond_de_caisse + $session->total_ventes_especes, 0, ',', ' ') }} F</td>
                            <td>{{ number_format($session->montant_compte, 0, ',', ' ') }} F</td>
                            <td class="{{ $session->ecart == 0 ? '' : ($session->ecart > 0 ? 'text-success' : 'text-danger') }} fw-medium">
                                {{ $session->ecart > 0 ? '+' : '' }}{{ number_format($session->ecart, 0, ',', ' ') }} F
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">Aucune session clôturée sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $sessions->links() }}
    </div>
@endsection
