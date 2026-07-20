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

    <div class="card recu-pos mx-auto" style="max-width: 420px;">
        <div class="card-body">
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

            <div class="text-center text-secondary small mt-3">Merci de votre visite !</div>
        </div>
    </div>
@endsection
