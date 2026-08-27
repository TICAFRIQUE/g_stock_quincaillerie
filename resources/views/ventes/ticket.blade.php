@extends('layouts.app')

@section('title', "Ticket {$vente->numero}")

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h2 class="h4 mb-0">Détail de la facture</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('sessions.show', $vente->sessionCaisse) }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour à la session
            </a>
            <button type="button" class="btn btn-outline-secondary"
                onclick="(window.gstock && window.gstock.print) ? window.gstock.print() : window.print()">
                <i class="bi bi-printer me-1"></i>Ticket caisse
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="imprimerFacture()">
                <i class="bi bi-receipt me-1"></i>Facture
            </button>
            <a href="{{ route('ventes.pdf', $vente) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            <a href="{{ route('ventes.excel', $vente) }}" class="btn btn-outline-secondary">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('ventes.create', $vente->sessionCaisse) }}" class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i>Nouvelle facture
            </a>
        </div>
    </div>

    @if ($vente->trashed())
        <div class="alert alert-danger mx-auto mb-3 d-print-none" style="max-width: 420px;">
            <i class="bi bi-x-circle-fill me-1"></i>
            <strong>Vente annulée</strong> par {{ $vente->annulateur?->name ?? 'un utilisateur supprimé' }}
            le {{ $vente->deleted_at->format('d/m/Y H:i') }}.
            <div class="mt-1 fst-italic">{{ $vente->motif_annulation }}</div>
        </div>
    @endif

    @php
        $afficheReglerClient = ! $vente->trashed() && $peutRegler && $vente->soldeDuReel() > 0;
        $afficheSignaler = ! $vente->trashed() && auth()->user()->can('vente.signaler');
        $afficheAnnulerVente = ! $vente->trashed() && auth()->user()->can('vente.annuler');
        $afficheRetourner = ! $vente->trashed() && $peutRetourner;
        $lignesRetournables = $vente->lignes->filter(
            fn ($ligne) => $ligne->quantite_pieces - ($dejaRetourneParLigne[$ligne->id] ?? 0) > 0
        );
        $nombreActionsVente = collect([$afficheReglerClient, $afficheSignaler, $afficheAnnulerVente, $afficheRetourner])->filter()->count();
        $colActionVente = match ($nombreActionsVente) {
            1 => 'col-md-6 col-lg-4',
            2 => 'col-md-6',
            default => 'col-md-4',
        };
    @endphp

    <div class="row g-3 mb-3 d-print-none">
        <div class="col-md-4">
            <div class="card h-100 bg-primary-subtle border-0">
                <div class="card-body">
                    <div class="text-secondary small">Client</div>
                    <div class="fw-medium">
                        @if ($vente->client)
                            <a href="{{ route('clients.show', $vente->client) }}">{{ $vente->client->nom }}</a>
                        @else
                            Vente comptant
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 bg-info-subtle border-0">
                <div class="card-body">
                    <div class="text-secondary small">Magasin / Caisse</div>
                    <div class="fw-medium">{{ $vente->magasin->nom }} — {{ $vente->sessionCaisse->caisse->nom }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100 bg-warning-subtle border-0">
                <div class="card-body">
                    <div class="text-secondary small">Créé le</div>
                    <div class="fw-medium">{{ $vente->created_at->format('d/m/Y à H:i') }} par {{ $vente->caissier->name }}</div>
                </div>
            </div>
        </div>
    </div>

    @if ($afficheReglerClient || $afficheSignaler || $afficheAnnulerVente || $afficheRetourner)
        <div class="row g-3 mb-3 d-print-none">
            @if ($afficheRetourner)
                <div class="{{ $colActionVente }}">
                    <div class="card h-100 shadow-sm bg-info-subtle border-0">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex align-items-center gap-2 text-info-emphasis fw-semibold">
                                <i class="bi bi-arrow-return-left fs-5"></i>Retourner des articles
                            </div>
                            @if (! $vente->client_id)
                                <div class="small text-secondary mt-1">
                                    Cette vente n'a pas de client identifié : un retour ne peut pas être enregistré
                                    (aucun compte à créditer).
                                </div>
                            @elseif ($lignesRetournables->isEmpty())
                                <div class="small text-secondary mt-1">
                                    Tous les articles de cette facture ont déjà été retournés.
                                </div>
                            @else
                                <div class="small text-secondary mt-1 mb-2">
                                    L'article sera remis en stock et un avoir sera crédité au compte du client.
                                </div>
                                <button type="button" class="btn btn-info btn-sm mt-auto align-self-start" data-bs-toggle="modal" data-bs-target="#retourVenteModal">
                                    <i class="bi bi-arrow-return-left me-1"></i>Enregistrer un retour
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($afficheReglerClient)
                <div class="{{ $colActionVente }}">
                    <div class="card h-100 shadow-sm bg-reglement-subtle border-0" x-data="{ ouvert: false, paiements: [{ moyen_paiement_id: '', montant: {{ $vente->soldeDuReel() }} }],
                            get totalPaiements() { return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0); },
                            ajouterPaiement() { this.paiements.push({ moyen_paiement_id: '', montant: '' }); },
                            retirerPaiement(index) { this.paiements.splice(index, 1); } }">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <div>
                                    <div class="d-flex align-items-center gap-2 text-reglement fw-semibold">
                                        <i class="bi bi-cash-coin fs-5"></i>Règlement client
                                    </div>
                                    <div class="small text-secondary mt-1">
                                        Reste dû sur cette facture : <strong>{{ number_format($vente->soldeDuReel(), 0, ',', ' ') }} F</strong>
                                    </div>
                                </div>
                                @if ($sessionOuverte)
                                    <button type="button" class="btn btn-reglement btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                                        <i class="bi bi-plus-lg me-1"></i>Régler
                                    </button>
                                @endif
                            </div>

                            @if ($sessionOuverte)
                                <form id="formReglerClient" method="POST" action="{{ route('reglements.store', $sessionOuverte) }}" x-show="ouvert" x-cloak class="mt-2">
                                    @csrf
                                    <input type="hidden" name="client_id" value="{{ $vente->client_id }}">
                                    <input type="hidden" name="vente_id" value="{{ $vente->id }}">
                                    <p class="small text-secondary mb-2">
                                        Solde total du client (toutes factures confondues) : {{ number_format($vente->client->solde(), 0, ',', ' ') }} F
                                    </p>
                                    <template x-for="(paiement, index) in paiements" :key="index">
                                        <div class="row g-1 align-items-center mb-2">
                                            <div class="col-6 col-md-5">
                                                <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm" required>
                                                    <option value="">Moyen…</option>
                                                    @foreach ($moyensPaiement as $moyen)
                                                        <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="1" class="form-control form-control-sm" placeholder="Montant" required>
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)" x-show="paiements.length > 1">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    <button type="button" class="btn btn-sm btn-outline-reglement mb-2" @click="ajouterPaiement()">
                                        <i class="bi bi-plus-lg"></i> Ajouter un paiement
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-reglement btn-sm flex-fill" :disabled="totalPaiements <= 0">
                                            Enregistrer le règlement
                                        </button>
                                        <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                                    </div>
                                </form>
                            @else
                                <a href="{{ route('sessions.index') }}" class="btn btn-outline-secondary btn-sm">Ouvrir une caisse pour régler</a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($afficheSignaler)
                <div class="{{ $colActionVente }}">
                    <div class="card h-100 shadow-sm bg-warning-subtle border-0" x-data="{ ouvert: false }">
                        <div class="card-body">
                            @forelse ($signalements as $signalement)
                                <div class="alert alert-warning small mb-2">
                                    <i class="bi bi-flag-fill me-1"></i>
                                    Signalée par {{ $signalement->causer?->name ?? 'un utilisateur supprimé' }}
                                    le {{ $signalement->created_at->format('d/m/Y H:i') }} :
                                    {{ $signalement->properties['motif'] ?? '' }}
                                </div>
                            @empty
                            @endforelse

                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <div>
                                    <div class="d-flex align-items-center gap-2 text-warning-emphasis fw-semibold">
                                        <i class="bi bi-flag fs-5"></i>Signaler un problème
                                    </div>
                                    <div class="small text-secondary mt-1">Ex. vente enregistrée deux fois par erreur.</div>
                                </div>
                                <button type="button" class="btn btn-warning btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                                    <i class="bi bi-flag me-1"></i>Signaler
                                </button>
                            </div>

                            <form method="POST" action="{{ route('ventes.signaler', $vente) }}" x-show="ouvert" x-cloak class="mt-2">
                                @csrf
                                <label for="motif" class="form-label small">Motif</label>
                                <textarea name="motif" id="motif" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-warning btn-sm flex-fill">Envoyer le signalement</button>
                                    <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif

            @if ($afficheAnnulerVente)
                <div class="{{ $colActionVente }}">
                    <div class="card h-100 shadow-sm bg-danger-subtle border-0" x-data="{ ouvert: false }">
                        <div class="card-body">
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                <div>
                                    <div class="d-flex align-items-center gap-2 text-danger fw-semibold">
                                        <i class="bi bi-x-circle fs-5"></i>Annuler la vente
                                    </div>
                                    <div class="small text-secondary mt-1">Le stock sera remis à jour automatiquement.</div>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm flex-shrink-0" @click="ouvert = !ouvert" x-show="!ouvert">
                                    <i class="bi bi-x-circle me-1"></i>Annuler
                                </button>
                            </div>

                            <form id="formAnnulerVente" method="POST" action="{{ route('ventes.annuler', $vente) }}" x-show="ouvert" x-cloak class="mt-2">
                                @csrf
                                <label for="motif-annulation" class="form-label small">
                                    Motif de l'annulation (obligatoire)
                                </label>
                                <textarea name="motif" id="motif-annulation" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-danger btn-sm flex-fill"
                                            data-bs-toggle="modal" data-bs-target="#confirmActionModal"
                                            data-form-id="formAnnulerVente"
                                            data-message="Annuler cette vente ? Le stock sera remis à jour. Cette action est irréversible."
                                            data-button-label="Annuler la vente" data-button-class="btn-danger">
                                        Confirmer l'annulation
                                    </button>
                                    <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="card mb-3 d-print-none bg-white">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th>Qté</th>
                        <th class="text-end">Prix unitaire</th>
                        <th class="text-end">Remise</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($vente->lignes as $ligne)
                        <tr>
                            <td>
                                {{ $ligne->produit->libelle_affichage }}
                                <span class="text-secondary small">({{ $ligne->uniteVente?->libelle ?? $ligne->produit->unite_base_libelle }})</span>
                            </td>
                            <td>{{ $ligne->quantite }}</td>
                            <td class="text-end">{{ number_format($ligne->prix_unitaire_applique, 0, ',', ' ') }} F</td>
                            <td class="text-end text-danger">{{ $ligne->remise_ligne_montant > 0 ? '− '.number_format($ligne->remise_ligne_montant, 0, ',', ' ').' F' : '—' }}</td>
                            <td class="text-end fw-medium">{{ number_format($ligne->total_ligne, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    @if ($vente->remise_totale_montant > 0)
                        <tr>
                            <th colspan="4" class="text-end">Sous-total</th>
                            <th class="text-end">{{ number_format($vente->sous_total, 0, ',', ' ') }} F</th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">Remise</th>
                            <th class="text-end text-danger">− {{ number_format($vente->remise_totale_montant, 0, ',', ' ') }} F</th>
                        </tr>
                    @endif
                    <tr class="fw-bold fs-5">
                        <th colspan="4" class="text-end">Net à payer</th>
                        <th class="text-end">{{ number_format($vente->total_net, 0, ',', ' ') }} F</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card mb-3 d-print-none bg-light border-0">
        <div class="card-body">
            <h3 class="h6">Règlement</h3>
            @forelse ($vente->paiements as $paiement)
                <div class="d-flex justify-content-between small border-bottom py-1">
                    <span>{{ $paiement->moyenPaiement->nom }}</span>
                    <span>{{ number_format($paiement->montant, 0, ',', ' ') }} F</span>
                </div>
            @empty
                <p class="text-secondary small mb-0">Aucun paiement encaissé à la vente.</p>
            @endforelse
            @foreach ($vente->reglementsClient as $reglement)
                <div class="small border-bottom py-1 text-success">
                    <div class="d-flex justify-content-between">
                        <span>
                            <i class="bi bi-cash-coin me-1"></i>Règlement du {{ $reglement->created_at->format('d/m/Y') }}
                            par {{ $reglement->caissier?->name ?? 'utilisateur supprimé' }}
                        </span>
                        <span class="fw-medium">{{ number_format($reglement->montant, 0, ',', ' ') }} F</span>
                    </div>
                    @foreach ($reglement->paiements as $paiementReglement)
                        <div class="d-flex justify-content-between text-secondary ps-4">
                            <span>{{ $paiementReglement->moyenPaiement->nom }}</span>
                            <span>{{ number_format($paiementReglement->montant, 0, ',', ' ') }} F</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div class="d-flex justify-content-between fw-medium mt-2">
                <span>Total réglé</span>
                <span>{{ number_format($vente->montantRegle(), 0, ',', ' ') }} F</span>
            </div>
            @if ($vente->avoir_applique > 0)
                <div class="d-flex justify-content-between text-success">
                    <span><i class="bi bi-piggy-bank me-1"></i>Avoir appliqué</span>
                    <span>{{ number_format($vente->avoir_applique, 0, ',', ' ') }} F</span>
                </div>
            @endif
            <div class="d-flex justify-content-between fw-semibold {{ $vente->soldeDuReel() > 0 ? 'text-danger' : '' }}">
                <span>Reste dû</span>
                <span>{{ number_format($vente->soldeDuReel(), 0, ',', ' ') }} F</span>
            </div>
        </div>
    </div>

    @if ($vente->retours->isNotEmpty())
        <div class="card mb-3 d-print-none bg-light border-0">
            <div class="card-body">
                <h3 class="h6">Retours</h3>
                @foreach ($vente->retours as $retour)
                    <div class="small border-bottom py-1 text-info-emphasis">
                        <div class="d-flex justify-content-between">
                            <span>
                                <i class="bi bi-arrow-return-left me-1"></i><code>{{ $retour->numero }}</code>
                                du {{ $retour->created_at->format('d/m/Y') }}
                                par {{ $retour->auteur?->name ?? 'utilisateur supprimé' }}
                            </span>
                            <span class="fw-medium">Avoir {{ number_format($retour->montant_total, 0, ',', ' ') }} F</span>
                        </div>
                        @if ($retour->motif)
                            <div class="text-secondary ps-4 fst-italic">{{ $retour->motif }}</div>
                        @endif
                        @foreach ($retour->lignes as $ligneRetour)
                            <div class="d-flex justify-content-between text-secondary ps-4">
                                <span>{{ $ligneRetour->produit->libelle_affichage }} × {{ $ligneRetour->quantite_pieces }}</span>
                                <span>{{ number_format($ligneRetour->montant, 0, ',', ' ') }} F</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card recu-pos mx-auto d-none d-print-block" style="max-width: 420px;">
        <div class="card-body">
            @if ($vente->trashed())
                <div class="text-center mb-2">
                    <span class="badge text-bg-danger fs-6">ANNULÉE</span>
                </div>
            @endif
            <div class="text-center mb-3">
                <div class="fw-bold">{{ $vente->magasin->nom }}</div>
                <div class="small text-secondary">{{ $vente->sessionCaisse->caisse->nom }}</div>
                <div class="small text-secondary">{{ $vente->created_at->format('d/m/Y H:i') }}</div>
                <div class="fw-medium mt-1"><code>{{ $vente->numero }}</code></div>
                @if ($vente->client)
                    <div class="small text-secondary mt-1">
                        Client : <a href="{{ route('clients.show', $vente->client) }}">{{ $vente->client->nom }}</a>
                    </div>
                @endif
            </div>

            <table class="table table-sm">
                <tbody>
                    @foreach ($vente->lignes as $ligne)
                        <tr>
                            <td>
                                {{ $ligne->produit->libelle_affichage }}
                                <span class="text-secondary small">({{ $ligne->uniteVente?->libelle ?? $ligne->produit->unite_base_libelle }})</span>
                                <br>
                                <span class="text-secondary small">{{ $ligne->quantite }} × {{ number_format($ligne->prix_unitaire_applique, 0, ',', ' ') }} F</span>
                                @if ($ligne->remise_ligne_montant > 0)
                                    <br><span class="text-danger small">Remise : − {{ number_format($ligne->remise_ligne_montant, 0, ',', ' ') }} F</span>
                                @endif
                            </td>
                            <td class="text-end align-top">{{ number_format($ligne->total_ligne, 0, ',', ' ') }} F</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="table table-sm mb-0">
                <tr>
                    <td>Sous-total</td>
                    <td class="text-end">{{ number_format($vente->sous_total, 0, ',', ' ') }} F</td>
                </tr>
                @if ($vente->remise_totale_montant > 0)
                    <tr>
                        <td>Remise</td>
                        <td class="text-end text-danger">− {{ number_format($vente->remise_totale_montant, 0, ',', ' ') }} F</td>
                    </tr>
                @endif
                <tr class="fw-bold fs-5">
                    <td>Net à payer</td>
                    <td class="text-end">{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
                </tr>
            </table>

            <hr>

            <table class="table table-sm mb-0">
                @foreach ($vente->paiements as $paiement)
                    <tr>
                        <td>{{ $paiement->moyenPaiement->nom }}</td>
                        <td class="text-end">{{ number_format($paiement->montant, 0, ',', ' ') }} F</td>
                    </tr>
                @endforeach
                @if ($vente->monnaie_rendue > 0)
                    <tr>
                        <td>Reçu</td>
                        <td class="text-end">{{ number_format($vente->montant_recu, 0, ',', ' ') }} F</td>
                    </tr>
                    <tr class="fw-medium">
                        <td>Monnaie rendue</td>
                        <td class="text-end">{{ number_format($vente->monnaie_rendue, 0, ',', ' ') }} F</td>
                    </tr>
                @endif
                @if ($vente->soldeDu() > 0)
                    <tr class="fw-medium">
                        <td>Montant payé</td>
                        <td class="text-end">{{ number_format($vente->montantRegle(), 0, ',', ' ') }} F</td>
                    </tr>
                    @if ($vente->avoir_applique > 0)
                        <tr>
                            <td>Avoir appliqué</td>
                            <td class="text-end">{{ number_format($vente->avoir_applique, 0, ',', ' ') }} F</td>
                        </tr>
                    @endif
                    <tr class="fw-bold {{ $vente->soldeDuReel() > 0 ? 'text-danger' : '' }}">
                        <td>Reste à payer</td>
                        <td class="text-end">{{ number_format($vente->soldeDuReel(), 0, ',', ' ') }} F</td>
                    </tr>
                @endif
            </table>

            <div class="text-center text-secondary small mt-3">
                Merci de votre visite !
                @if ($parametre->numero || $parametre->adresse || $parametre->slogan)
                    <hr>
                @endif
                @if ($parametre->slogan)
                    <div>{{ $parametre->slogan }}</div>
                @endif
                @if ($parametre->numero)
                    <div>{{ $parametre->numero }}</div>
                @endif
                @if ($parametre->adresse)
                    <div>{{ $parametre->adresse }}</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Contenu au format facture, masqué à l'écran : le bouton "Facture"
         imprime ce bloc en place (voir imprimerFacture() ci-dessous), sans
         redirection ni nouvel onglet — même geste que "Ticket caisse". --}}
    <div id="factureImprimable" class="d-none">
        @if ($vente->trashed())
            <div class="badge-annulee">VENTE ANNULÉE</div>
        @endif

        <table class="entete">
            <tr>
                <td style="width: 55%;">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" class="logo" alt="Logo">
                        <br>
                    @endif
                    <span class="entreprise-nom">{{ $parametre->nom }}</span><br>
                    @if ($parametre->adresse) {{ $parametre->adresse }}<br> @endif
                    @if ($parametre->numero) Tél : {{ $parametre->numero }} @endif
                </td>
                <td style="width: 45%;">
                    <div class="facture-titre">FACTURE</div>
                    <div class="facture-meta">
                        N° {{ $vente->numero }}<br>
                        Date : {{ $vente->created_at->format('d/m/Y à H:i') }}<br>
                        Magasin : {{ $vente->magasin->nom }}<br>
                        Caisse : {{ $vente->sessionCaisse->caisse->nom }}<br>
                        Caissier : {{ $vente->caissier->name }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="bloc-client">
            <div class="label">Client</div>
            <strong>{{ $vente->client->nom ?? 'Client comptant' }}</strong><br>
            @if ($vente->client?->telephone) Tél : {{ $vente->client->telephone }}<br> @endif
            @if ($vente->client?->adresse) {{ $vente->client->adresse }} @endif
        </div>

        <table class="lignes">
            <thead>
                <tr>
                    <th>Désignation</th>
                    <th>Unité</th>
                    <th class="text-end">Qté</th>
                    <th class="text-end">Prix unitaire</th>
                    <th class="text-end">Remise</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($vente->lignes as $ligne)
                    <tr>
                        <td>{{ $ligne->produit->libelle_affichage }}</td>
                        <td>{{ $ligne->uniteVente->libelle ?? $ligne->produit->unite_base_libelle }}</td>
                        <td class="text-end">{{ $ligne->quantite }}</td>
                        <td class="text-end">{{ number_format($ligne->prix_unitaire_applique, 0, ',', ' ') }} F</td>
                        <td class="text-end">{{ $ligne->remise_ligne_montant > 0 ? '− '.number_format($ligne->remise_ligne_montant, 0, ',', ' ').' F' : '—' }}</td>
                        <td class="text-end">{{ number_format($ligne->total_ligne, 0, ',', ' ') }} F</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totaux">
            <tr>
                <td>Sous-total</td>
                <td class="text-end">{{ number_format($vente->sous_total, 0, ',', ' ') }} F</td>
            </tr>
            @if ($vente->remise_totale_montant > 0)
                <tr>
                    <td>Remise</td>
                    <td class="text-end">− {{ number_format($vente->remise_totale_montant, 0, ',', ' ') }} F</td>
                </tr>
            @endif
            <tr class="net">
                <td>Net à payer</td>
                <td class="text-end">{{ number_format($vente->total_net, 0, ',', ' ') }} F</td>
            </tr>
            @foreach ($vente->paiements as $paiement)
                <tr>
                    <td>{{ $paiement->moyenPaiement->nom }}</td>
                    <td class="text-end">{{ number_format($paiement->montant, 0, ',', ' ') }} F</td>
                </tr>
            @endforeach
            @if ($vente->monnaie_rendue > 0)
                <tr>
                    <td>Monnaie rendue</td>
                    <td class="text-end">{{ number_format($vente->monnaie_rendue, 0, ',', ' ') }} F</td>
                </tr>
            @endif
            @if ($vente->soldeDu() > 0)
                <tr>
                    <td>Montant payé</td>
                    <td class="text-end">{{ number_format($vente->montantRegle(), 0, ',', ' ') }} F</td>
                </tr>
                @if ($vente->avoir_applique > 0)
                    <tr>
                        <td>Avoir appliqué</td>
                        <td class="text-end">{{ number_format($vente->avoir_applique, 0, ',', ' ') }} F</td>
                    </tr>
                @endif
                <tr class="{{ $vente->soldeDuReel() > 0 ? 'credit' : '' }}">
                    <td>Reste à payer</td>
                    <td class="text-end">{{ number_format($vente->soldeDuReel(), 0, ',', ' ') }} F</td>
                </tr>
            @endif
        </table>

        <div class="mention">
            Merci de votre confiance.
            @if ($parametre->slogan) {{ $parametre->slogan }} @endif
        </div>
    </div>

    @if ($afficheRetourner && $vente->client_id && $lignesRetournables->isNotEmpty())
        <div class="modal fade d-print-none" id="retourVenteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content"
                     x-data="{
                        lignes: {{ $lignesRetournables->mapWithKeys(fn ($l) => [$l->id => 0])->toJson() }},
                        max: {{ $lignesRetournables->mapWithKeys(fn ($l) => [$l->id => $l->quantite_pieces - ($dejaRetourneParLigne[$l->id] ?? 0)])->toJson() }},
                        get total() { return Object.values(this.lignes).reduce((s, q) => s + (Number(q) || 0), 0); },
                     }">
                    <form method="POST" action="{{ route('ventes.retours.store', $vente) }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-arrow-return-left me-1"></i>Retourner des articles</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body">
                            <p class="small text-secondary">
                                Quantité à retourner par article (unité de base — pièce). Le stock est remis à
                                disposition et un avoir est crédité au compte du client, jamais un remboursement caisse.
                            </p>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th>Produit</th>
                                            <th class="text-end">Vendu</th>
                                            <th class="text-end">Déjà retourné</th>
                                            <th class="text-end" style="width: 140px;">À retourner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($lignesRetournables as $ligne)
                                            @php $dejaRetourne = $dejaRetourneParLigne[$ligne->id] ?? 0; @endphp
                                            <tr>
                                                <td>
                                                    {{ $ligne->produit->libelle_affichage }}
                                                    <input type="hidden" name="lignes[{{ $ligne->id }}][ligne_vente_id]" value="{{ $ligne->id }}">
                                                </td>
                                                <td class="text-end">{{ $ligne->quantite_pieces }}</td>
                                                <td class="text-end">{{ $dejaRetourne }}</td>
                                                <td>
                                                    <input type="number" name="lignes[{{ $ligne->id }}][quantite_pieces]"
                                                           x-model.number="lignes[{{ $ligne->id }}]"
                                                           min="0" max="{{ $ligne->quantite_pieces - $dejaRetourne }}"
                                                           class="form-control form-control-sm text-end">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mb-2">
                                <label for="retour-motif" class="form-label small">Motif (optionnel)</label>
                                <textarea name="motif" id="retour-motif" class="form-control form-control-sm" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link" data-bs-dismiss="modal">Fermer</button>
                            <button type="submit" class="btn btn-info" :disabled="total <= 0">Enregistrer le retour</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
    <script>
        // Imprime le PDF réel (dompdf, route ventes.pdf) dans un iframe caché,
        // plutôt que de basculer l'affichage sur #factureImprimable et
        // d'imprimer cette page HTML : rendu garanti identique à
        // "Télécharger en PDF" (dompdf a un support CSS différent d'un
        // navigateur/Electron — voir x-bouton-imprimer pour le même mécanisme).
        function imprimerFacture() {
            let iframe = document.getElementById('__iframeFacturePdf');
            if (! iframe) {
                iframe = document.createElement('iframe');
                iframe.id = '__iframeFacturePdf';
                iframe.style.display = 'none';
                document.body.appendChild(iframe);
            }
            iframe.onload = function () {
                setTimeout(() => {
                    iframe.contentWindow.focus();
                    if (window.gstock && window.gstock.print) {
                        window.gstock.print();
                    } else {
                        iframe.contentWindow.print();
                    }
                }, 200);
            };
            iframe.src = '{{ route('ventes.pdf', $vente) }}?imprimer=1';
        }
    </script>
@endpush
