<div class="mb-3">
    <select id="produit-picker" class="form-select" @change="ajouterDepuisSelect($event)">
        <option value="">— Rechercher un produit à ajouter —</option>
        @foreach ($produits as $produit)
            <option value="{{ $produit['id'] }}" data-produit-id="{{ $produit['id'] }}">
                {{ $produit['libelle_affichage'] }}
            </option>
        @endforeach
    </select>
</div>

<div class="card">
    <div class="card-body">
        <h3 class="h6">Lignes du devis</h3>
        <p class="text-secondary small">Montants indicatifs, calculés au prix courant du catalogue — jamais figés.</p>

        <div class="table-responsive">
            @php $colonnesPanier = auth()->user()->can('vente.remise') ? 6 : 5; @endphp
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th>Désignation</th>
                        <th>Qté</th>
                        <th>Unité</th>
                        @can('vente.remise')
                            <th>Remise</th>
                        @endcan
                        <th class="text-end">Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-if="panier.length === 0">
                        <tr>
                            <td colspan="{{ $colonnesPanier }}" class="text-secondary fst-italic small text-center py-3">Le devis est vide. Sélectionnez un produit ci-dessus.</td>
                        </tr>
                    </template>
                    <template x-for="(ligne, index) in panier" :key="index">
                        <tr>
                            <td class="small" x-text="ligne.produitLibelle"></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" @click="changerQuantite(ligne, -1)">−</button>
                                    <span x-text="ligne.quantite"></span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-icon" @click="changerQuantite(ligne, 1)">+</button>
                                </div>
                            </td>
                            <td>
                                <select class="form-select form-select-sm" style="min-width: 150px;"
                                        :class="{ 'is-invalid': estDoublon(index) || ligne.unite_vente_id === undefined }"
                                        @change="changerVarianteDepuisSelect(ligne, $event.target.value)" required>
                                    <option value="" :selected="ligne.unite_vente_id === undefined">— Choisir —</option>
                                    <option value="piece" :selected="ligne.unite_vente_id === null" x-text="produitDe(ligne).unite_base_libelle + ' — ' + produitDe(ligne).prix_piece + ' F'"></option>
                                    <template x-for="unite in produitDe(ligne).unites" :key="unite.id">
                                        <option :value="unite.id" :selected="unite.id === ligne.unite_vente_id" x-text="unite.libelle + ' — ' + unite.prix + ' F'"></option>
                                    </template>
                                </select>
                                <div class="text-danger small mt-1" x-show="estDoublon(index)" x-cloak>Déjà dans le devis avec la même variante.</div>
                            </td>
                            @can('vente.remise')
                                <td>
                                    <div class="d-flex flex-column gap-1" style="min-width: 130px;">
                                        <select x-model="ligne.remise_type" class="form-select form-select-sm">
                                            <option value="">Sans remise</option>
                                            <option value="montant">Remise (F)</option>
                                            <option value="pourcentage">Remise (%)</option>
                                        </select>
                                        <input type="number" x-model.number="ligne.remise_valeur"
                                               @input="if (ligne.remise_type === 'pourcentage' && ligne.remise_valeur > 100) ligne.remise_valeur = 100"
                                               x-show="ligne.remise_type" min="0" :max="ligne.remise_type === 'pourcentage' ? 100 : null" class="form-control form-control-sm" placeholder="Valeur">
                                    </div>
                                </td>
                            @endcan
                            <td class="text-end fw-medium" x-text="totalLigne(ligne) + ' F'"></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" title="Dupliquer (pour ajouter une autre variante de ce produit)" @click="dupliquerLigne(index)">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerLigne(index)">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="{{ $colonnesPanier - 1 }}" class="text-end">Total indicatif</th>
                        <th x-text="sousTotal + ' F'"></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="text-danger small mt-2" x-show="aUneLigneNonChoisie" x-cloak>
    <i class="bi bi-exclamation-triangle-fill me-1"></i>Choisissez une unité pour chaque ligne.
</div>
<div class="text-danger small mt-2" x-show="aUnDoublon" x-cloak>
    <i class="bi bi-exclamation-triangle-fill me-1"></i>Un même produit ne peut pas apparaître deux fois avec la même unité : corrigez avant d'enregistrer.
</div>
