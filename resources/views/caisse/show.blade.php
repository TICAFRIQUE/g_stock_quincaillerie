@extends('layouts.app')

@section('title', "Caisse — {$session->caisse->nom}")

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $session->caisse->nom }} — {{ $session->caisse->magasin->nom }}</h2>
            <p class="text-secondary small mb-0">
                Tenue par {{ $session->caissier->name }} depuis {{ $session->date_ouverture->format('d/m/Y à H:i') }}
                @if ($session->date_cloture)
                    <span class="badge text-bg-secondary ms-2">Clôturée</span>
                @else
                    <span class="badge text-bg-success ms-2">Ouverte</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sessions.show', $session) }}" class="btn btn-outline-secondary">
                <i class="bi bi-receipt me-1"></i>Voir les ventes de cette session
            </a>
            <a href="{{ route('caisse.index') }}" class="btn btn-link">Retour</a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @if ($soldeTheorique !== null)
            <div class="col-6 col-md-4">
                <x-kpi-card label="Solde théorique du tiroir" icon="bi-wallet2" color="secondary"
                    :value="number_format($soldeTheorique, 0, ',', ' ') . ' F'" />
            </div>
        @endif
        <div class="col-6 col-md-4">
            <x-kpi-card label="Solde entrées" icon="bi-box-arrow-in-down" color="success"
                :value="number_format($totalEntrees, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4">
            <x-kpi-card label="Solde sorties" icon="bi-box-arrow-up" color="danger"
                :value="number_format($totalSorties, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    @if (! $session->date_cloture)
        <div class="card mb-3 shadow-sm" x-data="{ ouvert: false }">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                    <div>
                        <div class="d-flex align-items-center gap-2 fw-semibold">
                            <i class="bi bi-arrow-left-right fs-5"></i>Nouveau mouvement
                        </div>
                        <div class="small text-secondary mt-1">
                            Entrée (appoint…) ou sortie (paiement fournisseur en espèces, dépense diverse…) du tiroir.
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                        <i class="bi bi-plus-lg me-1"></i>Enregistrer
                    </button>
                </div>

                <form method="POST" action="{{ route('caisse.mouvements.store', $session) }}" x-show="ouvert" x-cloak class="mt-2">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-6 col-md-3">
                            <label for="type" class="form-label small mb-1">Type</label>
                            <select name="type" id="type" class="form-select form-select-sm" required>
                                <option value="sortie">Sortie</option>
                                <option value="entree">Entrée</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3">
                            <label for="montant" class="form-label small mb-1">Montant (F)</label>
                            <input type="number" name="montant" id="montant" min="1" class="form-control form-control-sm" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="motif" class="form-label small mb-1">Motif</label>
                            <input type="text" name="motif" id="motif" maxlength="255" class="form-control form-control-sm"
                                   placeholder="Ex. paiement fournisseur en espèces, achat de fournitures…" required>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button type="submit" class="btn btn-outline-secondary btn-sm">Enregistrer le mouvement</button>
                        <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <h3 class="h6">Historique de cette caisse</h3>
            @forelse ($journal as $mouvement)
                <div class="d-flex justify-content-between small border-bottom py-1">
                    <span>
                        <span class="badge {{ $mouvement->type_badge }} me-1">{{ $mouvement->type_libelle }}</span>
                        {{ $mouvement->motif }}
                        <span class="text-secondary">— {{ $mouvement->auteur_nom ?? 'utilisateur supprimé' }}, {{ \Illuminate\Support\Carbon::parse($mouvement->created_at)->format('d/m/Y H:i') }}</span>
                    </span>
                    <span class="fw-medium">{{ $mouvement->signe_positif ? '+ ' : '− ' }}{{ number_format($mouvement->montant, 0, ',', ' ') }} F</span>
                </div>
            @empty
                <p class="text-secondary small fst-italic mb-0">Aucun mouvement sur cette caisse pour l'instant.</p>
            @endforelse
        </div>
    </div>
@endsection
