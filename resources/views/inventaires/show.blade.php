@extends('layouts.app')

@section('title', "Inventaire du {$inventaire->date->format('d/m/Y')}")

@section('content')
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="h4 mb-1">Inventaire — {{ $inventaire->magasin->nom }} — {{ $inventaire->date->format('d/m/Y') }}</h2>
            @if ($inventaire->statut === 'valide')
                <span class="badge text-bg-success">Validé le {{ $inventaire->valide_at->format('d/m/Y à H:i') }} par {{ $inventaire->validateur->name }}</span>
            @else
                <span class="badge text-bg-secondary">Brouillon</span>
            @endif
        </div>

        @if ($inventaire->statut === 'brouillon')
            <div class="d-flex gap-2">
                @if ($peutValider)
                    @if ($inventaire->lignes->isEmpty())
                        <button type="button" class="btn btn-success" disabled>
                            <i class="bi bi-check-circle me-1"></i>Valider l'inventaire
                        </button>
                    @else
                        <x-confirm-button :action="route('inventaires.valider', $inventaire)"
                            message="Valider cet inventaire ? Les écarts seront appliqués au stock immédiatement et cette action est irréversible."
                            button-label="Valider l'inventaire" button-class="btn-success" icon="bi-check-circle" />
                    @endif
                @endif
                @if ($peutRealiser)
                    <x-delete-button :action="route('inventaires.destroy', $inventaire)"
                        :label="'cet inventaire du '.$inventaire->date->format('d/m/Y').' ('.$inventaire->magasin->nom.')'" />
                @endif
            </div>
        @endif
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produit</th>
                        <th>Théorique</th>
                        <th>Compté</th>
                        <th>Écart</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inventaire->lignes as $ligne)
                        <tr>
                            <td>{{ $ligne->produit->libelle_affichage }} <code class="small">{{ $ligne->produit->sku }}</code></td>
                            <td>{{ quantite($ligne->quantite_theorique) }}</td>
                            <td>{{ quantite($ligne->quantite_comptee) }}</td>
                            <td>
                                @if ((float) $ligne->ecart === 0.0)
                                    <span class="text-secondary">0</span>
                                @elseif ($ligne->ecart > 0)
                                    <span class="text-success fw-medium">+{{ quantite($ligne->ecart) }}</span>
                                @else
                                    <span class="text-danger fw-medium">{{ quantite($ligne->ecart) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">Aucune ligne comptée pour l'instant.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($inventaire->statut === 'brouillon' && $peutRealiser)
        <div class="card mx-auto" style="max-width: 800px;">
            <div class="card-body">
                <h3 class="h6">Saisir un comptage</h3>
                <p class="text-secondary small">Le théorique est capturé au moment de la saisie ; ressaisir un produit déjà compté met à jour sa ligne.</p>
                <form method="POST" action="{{ route('inventaires.saisir', $inventaire) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-7">
                        <label class="form-label small">Produit<span class="required-marker">*</span></label>
                        <select name="produit_id" id="produit_id_inventaire" class="form-select form-select-sm" required>
                            <option value="">— Choisir —</option>
                            @foreach ($produits as $produit)
                                <option value="{{ $produit->id }}" data-deja-compte="{{ in_array($produit->id, $produitsComptesIds) ? '1' : '0' }}">
                                    {{ $produit->sku }} — {{ $produit->libelle_affichage }}{{ in_array($produit->id, $produitsComptesIds) ? ' — déjà compté ✓' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-3">
                        <label class="form-label small">Qté comptée<span class="required-marker">*</span></label>
                        <input type="number" name="quantite_comptee" class="form-control form-control-sm" min="0" step="0.001" required>
                    </div>
                    <div class="col-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="mt-3">
        <a href="{{ route('inventaires.index') }}" class="btn btn-link ps-0">Retour à la liste</a>
    </div>
@endsection

@if ($inventaire->statut === 'brouillon' && $peutRealiser)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.initSelect2('#produit_id_inventaire', {
                    templateResult: (option) => {
                        if (!option.id) {
                            return option.text;
                        }
                        const dejaCompte = option.element && option.element.dataset.dejaCompte === '1';
                        const $result = window.jQuery('<span></span>').text(option.text);
                        if (dejaCompte) {
                            $result.addClass('text-success fw-medium');
                        }

                        return $result;
                    },
                });
            });
        </script>
    @endpush
@endif
