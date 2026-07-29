@extends('layouts.app')

@section('title', "Ticket {$vente->numero}")

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
        <h2 class="h4 mb-0">Détail de la vente</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('sessions.show', $vente->sessionCaisse) }}" class="btn btn-link">
                <i class="bi bi-arrow-left me-1"></i>Retour à la session
            </a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer me-1"></i>Imprimer le reçu
            </button>
            <a href="{{ route('ventes.create', $vente->sessionCaisse) }}" class="btn btn-primary">
                <i class="bi bi-cart-plus me-1"></i>Nouvelle vente
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

    @if (! $vente->trashed())
        @can('vente.signaler')
            <div class="card mx-auto mb-3 d-print-none" style="max-width: 420px;" x-data="{ ouvert: false }">
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

                    <button type="button" class="btn btn-outline-warning btn-sm w-100" @click="ouvert = !ouvert" x-show="!ouvert">
                        <i class="bi bi-flag me-1"></i>Signaler un problème sur cette vente
                    </button>

                    <form method="POST" action="{{ route('ventes.signaler', $vente) }}" x-show="ouvert" x-cloak>
                        @csrf
                        <label for="motif" class="form-label small mt-2">
                            Motif (ex. « vente enregistrée deux fois par erreur »)
                        </label>
                        <textarea name="motif" id="motif" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-warning btn-sm flex-fill">Envoyer le signalement</button>
                            <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Annuler</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan

        @can('vente.annuler')
            <div class="card mx-auto mb-3 d-print-none border-danger" style="max-width: 420px;" x-data="{ ouvert: false }">
                <div class="card-body">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" @click="ouvert = !ouvert" x-show="!ouvert">
                        <i class="bi bi-x-circle me-1"></i>Annuler cette vente
                    </button>

                    <form id="formAnnulerVente" method="POST" action="{{ route('ventes.annuler', $vente) }}" x-show="ouvert" x-cloak>
                        @csrf
                        <label for="motif-annulation" class="form-label small mt-2">
                            Motif de l'annulation (obligatoire) — le stock sera remis à jour automatiquement
                        </label>
                        <textarea name="motif" id="motif-annulation" class="form-control form-control-sm mb-2" rows="2" required></textarea>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-danger btn-sm flex-fill"
                                    data-bs-toggle="modal" data-bs-target="#confirmActionModal"
                                    data-form-id="formAnnulerVente"
                                    data-message="Annuler cette vente ? Le stock sera remis à jour. Cette action est irréversible."
                                    data-button-label="Annuler la vente" data-button-class="btn-danger">
                                Annuler la vente
                            </button>
                            <button type="button" class="btn btn-link btn-sm" @click="ouvert = false">Fermer</button>
                        </div>
                    </form>
                </div>
            </div>
        @endcan
    @endif

    <div class="card recu-pos mx-auto" style="max-width: 420px;">
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
            </div>

            <table class="table table-sm">
                <tbody>
                    @foreach ($vente->lignes as $ligne)
                        <tr>
                            <td>
                                {{ $ligne->produit->libelle_affichage }}
                                @if ($ligne->uniteVente) <span class="text-secondary small">({{ $ligne->uniteVente->libelle }})</span> @endif
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
            </table>

            <div class="text-center text-secondary small mt-3">
                Merci de votre visite !
                @php($parametre = \App\Models\Parametre::actuel())
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
@endsection
