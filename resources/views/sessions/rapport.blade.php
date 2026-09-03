@extends('layouts.app')

@section('title', "Rapport de caisse — {$session->caisse->nom}")

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <div class="d-flex align-items-center gap-2">
            <x-bouton-retour :route="route('sessions.show', $session)" />
            <h2 class="h4 mb-0">Rapport de caisse</h2>
        </div>
        <div class="d-flex gap-2">
            <x-export-buttons :pdf-route="route('sessions.rapport.pdf', $session)" :excel-route="route('sessions.rapport.excel', $session)" />
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <div class="fw-bold fs-5">{{ $session->caisse->nom }} — {{ $session->caisse->magasin->nom }}</div>
                <div class="text-secondary small">Caissier : {{ $session->caissier->name }}</div>
                <div class="text-secondary small">Ouverte le {{ $session->date_ouverture->format('d/m/Y à H:i') }}</div>
                @if ($session->date_cloture)
                    <div class="text-secondary small">
                        Clôturée le {{ $session->date_cloture->format('d/m/Y à H:i') }}
                        @if ($session->clotureePar)
                            par {{ $session->clotureePar->name }}
                        @endif
                    </div>
                @else
                    <span class="badge text-bg-success">Session en cours</span>
                @endif
            </div>

            <hr>

            <h3 class="h6">Liste des ventes</h3>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Numéro</th>
                            <th>Date et heure</th>
                            <th class="text-end">Total net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($ventes as $vente)
                            <tr>
                                <td><code>{{ $vente->numero }}</code></td>
                                <td>{{ $vente->created_at->format('d/m/Y H:i') }}</td>
                                <td class="text-end">{{ montant($vente->total_net) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-3">Aucune vente pour l'instant.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Petits tableaux récap sous la liste des ventes, plutôt que --}}
            {{-- de gros blocs de chiffres en haut de page — chaque carte a --}}
            {{-- son propre accent de couleur (bordure gauche) pour se --}}
            {{-- distinguer d'un coup d'œil, la carte Clôture reprenant la --}}
            {{-- couleur de l'écart (neutre/positif/négatif). --}}
            <div class="row g-3 mt-1">
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm border-0 border-start border-4 border-primary">
                        <div class="card-header bg-white border-0 pb-0">
                            <h4 class="h6 mb-0"><i class="bi bi-receipt me-1 text-primary"></i>Résumé</h4>
                        </div>
                        <div class="card-body pt-2">
                            <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                <span>Fond de caisse</span>
                                <span>{{ montant($session->fond_de_caisse) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                <span>Nombre de ventes</span>
                                <span>{{ $ventes->count() }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2 fw-semibold">
                                <span>Total net</span>
                                <span>{{ montant($totalVentes) }}</span>
                            </div>
                            @foreach ($paiementsParMoyen as $paiement)
                                <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                    <span>{{ $paiement->moyenPaiement->nom }}</span>
                                    <span>{{ montant($paiement->total) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($session->date_cloture)
                    @php
                        $couleurEcart = $session->ecart === 0 ? 'secondary' : ($session->ecart > 0 ? 'success' : 'danger');
                    @endphp
                    <div class="col-md-6">
                        <div class="card h-100 shadow-sm border-0 border-start border-4 border-{{ $couleurEcart }}">
                            <div class="card-header bg-white border-0 pb-0">
                                <h4 class="h6 mb-0"><i class="bi bi-safe2 me-1 text-{{ $couleurEcart }}"></i>Clôture</h4>
                            </div>
                            <div class="card-body pt-2">
                                <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                    <span>Théorique</span>
                                    <span>{{ montant($session->fond_de_caisse + $session->total_ventes_especes + $session->total_reglements_especes + $session->total_entrees_especes - $session->total_sorties_especes) }}</span>
                                </div>
                                @if ($session->total_reglements_especes > 0)
                                    <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                        <span>Règlements clients (espèces)</span>
                                        <span>{{ montant($session->total_reglements_especes) }}</span>
                                    </div>
                                @endif
                                <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                    <span>Entrées de caisse</span>
                                    <span class="text-success">{{ montant($session->total_entrees_especes) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2">
                                    <span>Sorties de caisse</span>
                                    <span class="text-danger">{{ montant($session->total_sorties_especes) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2 fw-semibold">
                                    <span>Compté</span>
                                    <span>{{ montant($session->montant_compte) }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center bg-light rounded px-3 py-2 mb-2 fw-bold text-{{ $couleurEcart }}">
                                    <span>Écart</span>
                                    <span>{{ $session->ecart > 0 ? '+' : '' }}{{ montant($session->ecart) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
