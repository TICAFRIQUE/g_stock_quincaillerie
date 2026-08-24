@extends('layouts.app')

@section('title', 'Écarts de caisse')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="h4 mb-0">Écarts de caisse</h2>
        <div class="d-flex gap-2 flex-wrap">
            <x-bouton-imprimer :pdf-route="route('rapports.ecarts-caisse.pdf', request()->query())" />
            <a href="{{ route('rapports.ecarts-caisse.pdf', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('rapports.ecarts-caisse.excel', request()->query()) }}" class="btn btn-outline-secondary d-print-none">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
        </div>
    </div>
    @php
        $magasinIdActif = auth()->user()->magasin_id ?: request('magasin_id');
        $filtresActifs = collect([
            $magasinIdActif ? 'Magasin : ' . $magasins->firstWhere('id', (int) $magasinIdActif)?->nom : null,
        ])->filter();
    @endphp
    <p class="text-secondary small mb-3">
        Période : du {{ $debut->format('d/m/Y') }} au {{ $fin->format('d/m/Y') }}
        @if ($filtresActifs->isNotEmpty())
            · {{ $filtresActifs->implode(' · ') }}
        @endif
    </p>

    <form method="GET" action="{{ route('rapports.ecarts-caisse') }}" class="row g-2 mb-3 d-print-none">
        <div class="col-auto">
            <input type="date" name="debut" value="{{ $debut->toDateString() }}" class="form-control form-control-sm">
        </div>
        <div class="col-auto">
            <input type="date" name="fin" value="{{ $fin->toDateString() }}" class="form-control form-control-sm">
        </div>
        @unless (auth()->user()->magasin_id)
            <div class="col-auto">
                <select name="magasin_id" class="form-select form-select-sm">
                    <option value="">Tous les magasins</option>
                    @foreach ($magasins as $magasin)
                        <option value="{{ $magasin->id }}" @selected(request('magasin_id') == $magasin->id)>{{ $magasin->nom }}</option>
                    @endforeach
                </select>
            </div>
        @endunless
        <div class="col-auto">
            <button type="submit" class="btn btn-sm btn-primary">Filtrer</button>
        </div>
        @if (request()->hasAny(['debut', 'fin', 'magasin_id']))
            <div class="col-auto">
                <a href="{{ route('rapports.ecarts-caisse') }}" class="btn btn-sm btn-outline-danger" title="Réinitialiser les filtres">
                    <i class="bi bi-x-circle me-1"></i>Réinitialiser
                </a>
            </div>
        @endif
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
                        <th class="text-end d-print-none">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr>
                            <td>{{ $session->caisse->nom }}</td>
                            <td>{{ $session->caisse->magasin->nom }}</td>
                            <td>{{ $session->caissier->name }}</td>
                            <td>{{ $session->date_cloture->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($session->fond_de_caisse + $session->total_ventes_especes + $session->total_reglements_especes + $session->total_entrees_especes - $session->total_sorties_especes, 0, ',', ' ') }} F</td>
                            <td>{{ number_format($session->montant_compte, 0, ',', ' ') }} F</td>
                            <td class="{{ $session->ecart == 0 ? '' : ($session->ecart > 0 ? 'text-success' : 'text-danger') }} fw-medium">
                                {{ $session->ecart > 0 ? '+' : '' }}{{ number_format($session->ecart, 0, ',', ' ') }} F
                            </td>
                            <td class="text-end d-print-none">
                                <a href="{{ route('sessions.rapport', $session) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Rapport de caisse">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">Aucune session clôturée sur cette période.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($sessions instanceof \Illuminate\Pagination\LengthAwarePaginator)
        <div class="mt-3 d-print-none">
            {{ $sessions->links() }}
        </div>
    @endif
@endsection
