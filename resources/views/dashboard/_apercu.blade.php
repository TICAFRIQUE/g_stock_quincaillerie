{{-- Partagé entre dashboard.gerant et dashboard.superadmin (voir DashboardController::donneesMagasin) --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">CA du jour</div>
                <div class="fs-5 fw-medium">{{ number_format($caJour, 0, ',', ' ') }} F</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">CA de la semaine</div>
                <div class="fs-5 fw-medium">{{ number_format($caSemaine, 0, ',', ' ') }} F</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">CA du mois</div>
                <div class="fs-5 fw-medium">{{ number_format($caMois, 0, ',', ' ') }} F</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">Panier moyen (mois)</div>
                <div class="fs-5 fw-medium">{{ number_format($panierMoyenMois, 0, ',', ' ') }} F</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">Valeur du stock (CMP)</div>
                <div class="fs-5 fw-medium">{{ number_format($valeurStock, 0, ',', ' ') }} F</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 {{ $produitsSousSeuilCount > 0 ? 'border-danger' : '' }}">
            <div class="card-body">
                <div class="text-secondary small">Produits sous seuil</div>
                <div class="fs-5 fw-medium {{ $produitsSousSeuilCount > 0 ? 'text-danger' : '' }}">{{ $produitsSousSeuilCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-secondary small">Caisses ouvertes</div>
                <div class="fs-5 fw-medium">{{ $caissesOuvertes->count() }}</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Évolution des ventes (14 derniers jours)</h3>
                <canvas id="chart-evolution" height="120"
                        data-labels="{{ json_encode($evolutionVentes['labels']) }}"
                        data-valeurs="{{ json_encode($evolutionVentes['valeurs']) }}"></canvas>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Moyens de paiement (ce mois)</h3>
                @if ($repartitionMoyens->isEmpty())
                    <p class="text-secondary small fst-italic">Aucune vente ce mois.</p>
                @else
                    <canvas id="chart-moyens"
                            data-labels="{{ json_encode($repartitionMoyens->pluck('nom')) }}"
                            data-valeurs="{{ json_encode($repartitionMoyens->pluck('total')) }}"></canvas>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Top produits (ce mois)</h3>
                @if ($topProduits->isEmpty())
                    <p class="text-secondary small fst-italic mb-0">Aucune vente ce mois.</p>
                @else
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Pièces vendues</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topProduits as $produit)
                                <tr>
                                    <td>{{ $produit->libelle_distinctif ? "{$produit->nom} — {$produit->libelle_distinctif}" : $produit->nom }}</td>
                                    <td>{{ $produit->pieces_vendues }}</td>
                                    <td>{{ number_format($produit->total, 0, ',', ' ') }} F</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body">
                <h3 class="h6">Produits sous seuil d'alerte</h3>
                @if ($produitsSousSeuil->isEmpty())
                    <p class="text-secondary small fst-italic mb-0">Aucun produit sous le seuil.</p>
                @else
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Magasin</th>
                                <th>Stock</th>
                                <th>Seuil</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($produitsSousSeuil as $stock)
                                <tr>
                                    <td>{{ $stock->produit->libelle_affichage }}</td>
                                    <td>{{ $stock->magasin->nom }}</td>
                                    <td class="text-danger fw-medium">{{ $stock->quantite }}</td>
                                    <td>{{ $stock->produit->seuil_alerte }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <h3 class="h6">Écarts de caisse récents</h3>
        @if ($ecartsCaisse->isEmpty())
            <p class="text-secondary small fst-italic mb-0">Aucun écart récent.</p>
        @else
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Caisse</th>
                        <th>Caissier</th>
                        <th>Clôturée le</th>
                        <th>Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ecartsCaisse as $s)
                        <tr>
                            <td>{{ $s->caisse->nom }}</td>
                            <td>{{ $s->caissier->name }}</td>
                            <td>{{ $s->date_cloture->format('d/m/Y H:i') }}</td>
                            <td class="{{ $s->ecart > 0 ? 'text-success' : 'text-danger' }} fw-medium">
                                {{ $s->ecart > 0 ? '+' : '' }}{{ number_format($s->ecart, 0, ',', ' ') }} F
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const evolutionEl = document.getElementById('chart-evolution');
            if (evolutionEl) {
                new Chart(evolutionEl, {
                    type: 'line',
                    data: {
                        labels: JSON.parse(evolutionEl.dataset.labels),
                        datasets: [{
                            label: 'Ventes (F)',
                            data: JSON.parse(evolutionEl.dataset.valeurs),
                            borderColor: '#e8590c',
                            backgroundColor: 'rgba(232, 89, 12, 0.15)',
                            tension: 0.3,
                            fill: true,
                        }],
                    },
                    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
                });
            }

            const moyensEl = document.getElementById('chart-moyens');
            if (moyensEl) {
                new Chart(moyensEl, {
                    type: 'doughnut',
                    data: {
                        labels: JSON.parse(moyensEl.dataset.labels),
                        datasets: [{
                            data: JSON.parse(moyensEl.dataset.valeurs),
                            backgroundColor: ['#e8590c', '#2470a8', '#f5b800', '#c62828', '#2e7d32'],
                        }],
                    },
                });
            }
        });
    </script>
@endpush
