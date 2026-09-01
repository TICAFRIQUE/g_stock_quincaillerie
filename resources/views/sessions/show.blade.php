@extends('layouts.app')

@section('title', "Session — {$session->caisse->nom}")

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h2 class="h4 mb-1">{{ $session->caisse->nom }} — {{ $session->caisse->magasin->nom }}</h2>
            <p class="text-secondary small mb-0">
                Ouverte par {{ $session->caissier->name }} le {{ $session->date_ouverture->format('d/m/Y à H:i') }}
                @if ($session->date_cloture)
                    <span class="badge text-bg-secondary ms-2">Clôturée</span>
                @else
                    <span class="badge text-bg-success ms-2">Ouverte</span>
                @endif
            </p>
            @can('rapport.voir')
                @if ($sessionsAujourdhui > 0)
                    <p class="small mb-0">
                        <a href="{{ route('rapports.ventes', ['caisse_id' => $session->caisse_id, 'debut' => $session->date_ouverture->toDateString(), 'fin' => $session->date_ouverture->toDateString()]) }}">
                            <i class="bi bi-clock-history me-1"></i>Voir les autres sessions de cette caisse aujourd'hui ({{ $sessionsAujourdhui }})
                        </a>
                    </p>
                @endif
            @endcan
        </div>

        <div class="d-flex flex-wrap gap-2">
            @if ($session->date_cloture)
                <a href="{{ route('sessions.rapport', $session) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-file-earmark-text me-1"></i>Rapport de caisse
                </a>
            @endif
            @if (! $session->date_cloture)
                @can('ventenattente.gerer')
                    <a href="{{ route('ventes-en-attente.index', $session) }}" class="btn btn-outline-warning position-relative">
                        <i class="bi bi-hourglass-split me-1"></i>Ventes en attente
                        @if ($venteEnAttentesVisibles > 0)
                            <span class="badge rounded-pill text-bg-warning ms-1">{{ $venteEnAttentesVisibles }}</span>
                        @endif
                    </a>
                @endcan
                @can('vente.creer')
                    <a href="{{ route('ventes.create', $session) }}" class="btn btn-primary">
                        <i class="bi bi-cart-plus me-1"></i>Vendre
                    </a>
                @endcan
                @can('client.reglement')
                    <a href="{{ route('reglements.create', $session) }}" class="btn btn-outline-primary">
                        <i class="bi bi-credit-card me-1"></i>Encaisser un règlement
                    </a>
                @endcan
                @can('caisse.cloturer')
                    @if ($session->vente_en_attentes_count > 0)
                        <button type="button" class="btn btn-outline-secondary disabled" disabled
                                title="Impossible de clôturer : {{ $session->vente_en_attentes_count }} vente(s) en attente à finaliser ou annuler d'abord.">
                            <i class="bi bi-lock me-1"></i>Clôturer
                        </button>
                    @else
                        <a href="{{ route('sessions.cloturer.form', $session) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-lock me-1"></i>Clôturer
                        </a>
                    @endif
                @endcan
            @elseif (! $session->date_fermeture)
                @can('caisse.fermer')
                    <x-confirm-button :action="route('sessions.fermer', $session)"
                        message="Fermer cette session ? La caisse redeviendra libre pour une nouvelle ouverture."
                        button-label="Fermer la session" button-class="btn-outline-danger" icon="bi-door-closed" />
                @endcan
            @endif
        </div>
    </div>

    <div class="row g-2 mb-2">
        <div class="col-6 col-md-3">
            <x-kpi-card compact label="Fond de caisse" icon="bi-cash-stack" color="primary"
                :value="number_format($session->fond_de_caisse, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card compact label="Total ventes" icon="bi-receipt" color="info"
                :value="$session->ventes_count" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card compact label="Ventes en attente" icon="bi-hourglass-split" color="warning"
                :value="$venteEnAttentesVisibles" />
        </div>
        <div class="col-6 col-md-3">
            <x-kpi-card compact label="Chiffre d'affaires" icon="bi-graph-up-arrow" color="success"
                :value="number_format($totalVentes, 0, ',', ' ') . ' F'" />
        </div>
        @if ($soldeTheorique !== null)
            <div class="col-6 col-md-3">
                <x-kpi-card compact label="Solde théorique du tiroir" icon="bi-wallet2" color="secondary"
                    :value="number_format($soldeTheorique, 0, ',', ' ') . ' F'" />
            </div>
        @endif
    </div>

    {{-- Décompose le chiffre d'affaires ci-dessus : dû (crédit encore
         ouvert), avoir (compensé, jamais encaissé) et espèces (réellement
         dans le tiroir) — séparés pour ne jamais laisser croire que le CA
         est de l'argent en caisse (règle 10). --}}
    <p class="text-secondary small mb-1">Décomposition</p>
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-4">
            <x-kpi-card compact label="Total dû (crédit)" icon="bi-credit-card" :color="$totalDu > 0 ? 'warning' : 'secondary'"
                :value="number_format($totalDu, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4">
            <x-kpi-card compact label="Avoirs appliqués" icon="bi-piggy-bank" color="info"
                :value="number_format($avoirApplique, 0, ',', ' ') . ' F'" />
        </div>
        <div class="col-6 col-md-4">
            <x-kpi-card compact label="Total en caisse" icon="bi-cash-stack" color="success"
                :value="number_format($totalEspeces, 0, ',', ' ') . ' F'" />
        </div>
    </div>

    @if ($paiementsParMoyen->isNotEmpty())
        <div class="card mb-2">
            <div class="card-body p-2 px-3">
                <div class="text-secondary small mb-1">Répartition par moyen de paiement</div>
                <div class="d-flex flex-wrap column-gap-4 row-gap-1">
                    @foreach ($paiementsParMoyen as $paiement)
                        <div>
                            <div class="text-secondary" style="font-size: .75rem;">{{ $paiement->moyenPaiement->nom }}</div>
                            <div class="fw-medium" style="font-size: 1.05rem;">{{ number_format($paiement->total, 0, ',', ' ') }} F</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @if ($session->date_cloture)
        <div class="card mb-2">
            <div class="card-body p-2 px-3">
                <div class="text-secondary small mb-1">Clôture</div>
                <div class="d-flex flex-wrap column-gap-4 row-gap-1">
                    <div>
                        <div class="text-secondary" style="font-size: .75rem;">Théorique</div>
                        <div class="fw-medium" style="font-size: 1.05rem;">{{ number_format($session->fond_de_caisse + $session->total_ventes_especes + $session->total_reglements_especes + $session->total_entrees_especes - $session->total_sorties_especes, 0, ',', ' ') }} F</div>
                    </div>
                    @if ($session->total_reglements_especes > 0)
                        <div>
                            <div class="text-secondary" style="font-size: .75rem;">Règlements clients (espèces)</div>
                            <div class="fw-medium" style="font-size: 1.05rem;">{{ number_format($session->total_reglements_especes, 0, ',', ' ') }} F</div>
                        </div>
                    @endif
                    @if ($session->total_entrees_especes > 0)
                        <div>
                            <div class="text-secondary" style="font-size: .75rem;">Entrées de caisse</div>
                            <div class="fw-medium" style="font-size: 1.05rem;">{{ number_format($session->total_entrees_especes, 0, ',', ' ') }} F</div>
                        </div>
                    @endif
                    @if ($session->total_sorties_especes > 0)
                        <div>
                            <div class="text-secondary" style="font-size: .75rem;">Sorties de caisse</div>
                            <div class="fw-medium" style="font-size: 1.05rem;">− {{ number_format($session->total_sorties_especes, 0, ',', ' ') }} F</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-secondary" style="font-size: .75rem;">Compté</div>
                        <div class="fw-medium" style="font-size: 1.05rem;">{{ number_format($session->montant_compte, 0, ',', ' ') }} F</div>
                    </div>
                    <div>
                        <div class="text-secondary" style="font-size: .75rem;">Écart</div>
                        <div class="fw-medium {{ $session->ecart === 0 ? '' : ($session->ecart > 0 ? 'text-success' : 'text-danger') }}" style="font-size: 1.05rem;">
                            {{ $session->ecart > 0 ? '+' : '' }}{{ number_format($session->ecart, 0, ',', ' ') }} F
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($peutMouvementer && ! $session->date_cloture)
        <div class="card mb-2 shadow-sm" x-data="{ ouvert: false, ajoutMotifOuvert: false }">
            <div class="card-body p-2 px-3">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 fw-semibold">
                            <i class="bi bi-arrow-left-right me-1"></i>Mouvement de caisse
                        </div>
                        <div class="small text-secondary">
                            Entrée (appoint…) ou sortie (paiement fournisseur en espèces, dépense diverse…) du tiroir.
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                        <i class="bi bi-plus-lg me-1"></i>Enregistrer
                    </button>
                </div>

                <form method="POST" action="{{ route('sessions.mouvements.store', $session) }}" x-show="ouvert" x-cloak class="mt-2">
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
                            <div class="d-flex gap-1">
                                <select name="motif" id="motif" x-ref="motifSelect" class="form-select form-select-sm" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($motifs as $m)
                                        <option value="{{ $m->nom }}">{{ $m->nom }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-secondary btn-sm flex-shrink-0" title="Nouveau motif"
                                        @click="ajoutMotifOuvert = !ajoutMotifOuvert">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                            <div class="input-group input-group-sm mt-1" x-show="ajoutMotifOuvert" x-cloak>
                                <input type="text" x-ref="motifNouveau" class="form-control" placeholder="Nouveau motif…" maxlength="255"
                                       @keydown.enter.prevent="window.ajouterMotifRapide($refs.motifSelect, $refs.motifNouveau, () => ajoutMotifOuvert = false)">
                                <button type="button" class="btn btn-outline-primary"
                                        @click="window.ajouterMotifRapide($refs.motifSelect, $refs.motifNouveau, () => ajoutMotifOuvert = false)">
                                    Ajouter
                                </button>
                            </div>
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

    @if ($session->mouvementCaisses->isNotEmpty())
        <div class="card mb-2">
            <div class="card-body p-2 px-3">
                <div class="text-secondary small mb-1">Historique des mouvements de caisse</div>
                @foreach ($session->mouvementCaisses as $mouvement)
                    <div class="d-flex justify-content-between small border-bottom py-1">
                        <span>
                            <span class="badge {{ $mouvement->type->classeBadge() }} me-1">{{ $mouvement->type->libelle() }}</span>
                            {{ $mouvement->motif }}
                            <span class="text-secondary">— {{ $mouvement->auteur->name ?? 'utilisateur supprimé' }}, {{ $mouvement->created_at->format('d/m/Y H:i') }}</span>
                        </span>
                        <span class="fw-medium">{{ $mouvement->type->value === 'entree' ? '+ ' : '− ' }}{{ number_format($mouvement->montant, 0, ',', ' ') }} F</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <x-recherche-form :action="route('sessions.show', $session)" placeholder="Numéro de vente…" />

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <x-th-tri champ="numero" label="Numéro" />
                        <x-th-tri champ="created_at" label="Date et heure" />
                        <x-th-tri champ="total_net" label="Montant dû" />
                        <th class="text-end">Déjà réglé</th>
                        <th class="text-end">Avoir appliqué</th>
                        <th class="text-end">Reste à payer</th>
                        <th>Statut</th>
                        <th>Livraison</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ventes as $vente)
                        <tr>
                            <td><code>{{ $vente->numero }}</code></td>
                            <td>{{ $vente->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                            <td class="text-end text-success">{{ number_format($vente->montantRegle(), 0, ',', ' ') }} F</td>
                            <td class="text-end text-secondary">{{ $vente->avoir_applique > 0 ? number_format($vente->avoir_applique, 0, ',', ' ').' F' : '—' }}</td>
                            <td class="text-end {{ $vente->soldeDuReel() > 0 ? 'text-danger fw-medium' : 'text-secondary' }}">{{ number_format($vente->soldeDuReel(), 0, ',', ' ') }} F</td>
                            <td><span class="badge text-bg-success">Validée</span></td>
                            <td>
                                @if ($vente->livraisonEngagee())
                                    @if ($vente->entierementLivree())
                                        <span class="badge text-bg-success-subtle text-success-emphasis">Entièrement livrée</span>
                                    @else
                                        <span class="badge text-bg-warning-subtle text-warning-emphasis">
                                            {{ $vente->quantiteLivreePieces() }}/{{ $vente->lignes->sum('quantite_pieces') }} pièce(s)
                                        </span>
                                    @endif
                                @else
                                    <span class="text-secondary">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('ventes.ticket', $vente) }}" class="btn btn-sm btn-icon btn-outline-secondary" title="Détail de la vente">
                                    <i class="bi bi-eye"></i>
                                    <span class="visually-hidden">Détail</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-secondary py-4">Aucune vente pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-between align-items-center">
        <a href="{{ route('sessions.index') }}" class="btn btn-link ps-0">Retour aux caisses</a>
        {{ $ventes->links() }}
    </div>
@endsection
