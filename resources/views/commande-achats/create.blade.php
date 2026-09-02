@extends('layouts.app')

@section('title', "Nouveau bon d'achat")

@section('content')
    <h2 class="h4 mb-3">Nouveau bon d'achat</h2>

    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card">
                <div class="card-body">
                    <form id="formCommandeAchat" method="POST" action="{{ route('commande-achats.store') }}" x-data="{
                            // _cle : identifiant stable par ligne (jamais l'index du
                            // tableau) pour le :key du x-for — avec l'index, retirer
                            // une ligne du milieu fait réutiliser le mauvais <select>
                            // du DOM pour une autre ligne (valeur affichée désynchronisée
                            // de la donnée réelle, d'où un unite_vente_id incohérent
                            // envoyé au serveur).
                            // Le middleware global ConvertEmptyStringsToNull convertit
                            // tout champ vide soumis en null AVANT que la requête
                            // n'atteigne le contrôleur — donc un select laissé sur son
                            // option vide revient en null dans old() après un échec de
                            // validation. Mais ce composant utilise '' partout comme
                            // valeur « rien choisi » — cf. la ligne par défaut ci-dessous,
                            // unitesDisponibles, les comparaisons plus bas) : un null
                            // ne correspond à aucune option, x-model ne peut rien
                            // sélectionner, et re-choisir manuellement était le seul
                            // moyen de repartir d'un état propre. On reconvertit donc
                            // systématiquement null en '' à la relecture.
                            lignes: {{ Js::from(collect(old('lignes', [['produit_id' => '', 'unite_vente_id' => '', 'taxe_id' => '', 'magasin_destination_id' => '', 'quantite' => '', 'prix_achat' => '']]))->map(
                                fn (array $l) => collect($l)->map(fn ($valeur) => $valeur ?? '')->all()
                            )) }}.map((l, i) => ({ ...l, _cle: 'l' + i + '-' + Date.now() })),
                            unitesParProduit: {{ Js::from($unitesParProduit) }},
                            taxes: {{ Js::from($taxes->map(fn ($t) => ['id' => $t->id, 'libelle' => $t->nom, 'taux' => $t->taux])) }},
                            fournisseurId: {{ Js::from(old('fournisseur_id', '')) }},
                            fournisseurSoldes: {{ Js::from($fournisseurSoldes) }},
                            erreurs: {{ Js::from($errors->getMessages()) }},
                            actionSoumission: {{ Js::from(old('action', 'brouillon')) }},
                            // Deux lignes du même produit ne sont un doublon que si
                            // elles partagent aussi la même unité d'achat (pièce vs
                            // carton = légitimement deux lignes distinctes) ET la même
                            // destination (une commande peut livrer plusieurs sites en
                            // une fois — voir CLAUDE.md, section Achat à crédit).
                            estDoublon(index) {
                                const ligne = this.lignes[index];
                                if (!ligne.produit_id) return false;
                                return this.lignes.some((l, i) => i !== index
                                    && l.produit_id === ligne.produit_id
                                    && (l.unite_vente_id || '') === (ligne.unite_vente_id || '')
                                    && (l.magasin_destination_id || '') === (ligne.magasin_destination_id || ''));
                            },
                            get aUnDoublon() {
                                return this.lignes.some((l, i) => this.estDoublon(i));
                            },
                            // Séparé de l'unité de base (voir baseLibelle) : une option
                            // liée par :value à une chaîne vide ne pose jamais
                            // l'attribut value côté Alpine — l'option retombe alors
                            // silencieusement sur son texte affiché comme valeur
                            // soumise (ex. « Pièce (pc) » envoyé au lieu d'une chaîne
                            // vide), d'où l'erreur « valeur sélectionnée invalide »
                            // côté serveur. L'option de l'unité de base reste donc une
                            // option statique à valeur vide, jamais générée par x-for.
                            unitesDisponibles(produitId) {
                                const infos = this.unitesParProduit[produitId];
                                return infos ? infos.variantes : [];
                            },
                            baseLibelle(produitId) {
                                const infos = this.unitesParProduit[produitId];
                                return infos ? infos.basePiece : 'pièce';
                            },
                            uniteChoisie(ligne) {
                                if (!ligne.unite_vente_id) {
                                    return { libelle: this.baseLibelle(ligne.produit_id), facteur: 1 };
                                }
                                const options = this.unitesDisponibles(ligne.produit_id);
                                return options.find((o) => String(o.id) === String(ligne.unite_vente_id))
                                    || { libelle: this.baseLibelle(ligne.produit_id), facteur: 1 };
                            },
                            quantitePieces(ligne) {
                                const quantite = Number(ligne.quantite) || 0;
                                return quantite * this.uniteChoisie(ligne).facteur;
                            },
                            tauxLigne(ligne) {
                                const taxe = this.taxes.find((t) => String(t.id) === String(ligne.taxe_id || ''));
                                return taxe ? taxe.taux : 0;
                            },
                            montantHtLigne(ligne) {
                                // Arrondi immédiat : sans lui, une quantité/un prix saisi
                                // en décimal (que step/min n'empêchent pas de taper, seule
                                // la soumission le rejette) laisse un HT fractionnaire dans
                                // totalHt alors que totalTtc est déjà arrondi ligne par
                                // ligne (voir montantTtcLigne) — totalTaxes = totalTtc −
                                // totalHt affichait alors un reste non nul même à taux 0%.
                                return Math.round((Number(ligne.quantite) || 0) * (Number(ligne.prix_achat) || 0));
                            },
                            montantTtcLigne(ligne) {
                                const ht = this.montantHtLigne(ligne);
                                return Math.round(ht + ht * this.tauxLigne(ligne) / 100);
                            },
                            get totalHt() {
                                return this.lignes.reduce((total, l) => total + this.montantHtLigne(l), 0);
                            },
                            get totalTtc() {
                                return this.lignes.reduce((total, l) => total + this.montantTtcLigne(l), 0);
                            },
                            get totalTaxes() {
                                return this.totalTtc - this.totalHt;
                            },
                            ajouterLigne() {
                                this.lignes.push({ produit_id: '', unite_vente_id: '', taxe_id: '', magasin_destination_id: '', quantite: '', prix_achat: '', _cle: 'l' + this.lignes.length + '-' + Date.now() + '-' + Math.random() });
                            },
                            paiements: {{ Js::from(old('paiements', [])) }}.map((p, i) => ({ ...p, _cle: 'p' + i + '-' + Date.now() })),
                            get totalPaiements() {
                                return this.paiements.reduce((total, p) => total + (Number(p.montant) || 0), 0);
                            },
                            get resteDu() {
                                return Math.max(this.totalTtc - this.totalPaiements, 0);
                            },
                            ajouterPaiement() {
                                this.paiements.push({ moyen_paiement_id: '', montant: '', _cle: 'p' + this.paiements.length + '-' + Date.now() + '-' + Math.random() });
                            },
                            retirerPaiement(index) {
                                this.paiements.splice(index, 1);
                            },
                            declencherValidation(event) {
                                const form = document.getElementById('formCommandeAchat');
                                if (!form.checkValidity()) {
                                    form.classList.add('was-validated');
                                    form.reportValidity();
                                    return;
                                }
                                this.actionSoumission = 'valider';
                                window.bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show(event.currentTarget);
                            },
                        }">
                        @csrf
                        <input type="hidden" name="action" x-model="actionSoumission">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="numero" class="form-label">Numéro</label>
                                <input type="text" name="numero" id="numero" class="form-control @error('numero') is-invalid @enderror"
                                       value="{{ old('numero') }}" placeholder="Génération automatique">
                                @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="fournisseur_id" class="form-label">Fournisseur<span class="required-marker">*</span></label>
                                <select name="fournisseur_id" id="fournisseur_id" class="form-select @error('fournisseur_id') is-invalid @enderror"
                                        x-model="fournisseurId" x-init="window.initSelect2($el)" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($fournisseurs as $fournisseur)
                                        <option value="{{ $fournisseur->id }}" @selected(old('fournisseur_id') == $fournisseur->id)>{{ $fournisseur->nom }}</option>
                                    @endforeach
                                </select>
                                @error('fournisseur_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <div class="form-text small text-success fw-semibold" x-show="fournisseurId && fournisseurSoldes[fournisseurId] < 0" x-cloak>
                                    <i class="bi bi-piggy-bank me-1"></i>Avoir de <span x-text="(-fournisseurSoldes[fournisseurId]) + ' ' + window.DEVISE_ABREVIATION"></span> sur ce fournisseur — il sera automatiquement déduit de la dette de cet achat.
                                </div>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="date_commande" class="form-label">Date<span class="required-marker">*</span></label>
                                <input type="date" name="date_commande" id="date_commande" class="form-control @error('date_commande') is-invalid @enderror"
                                       value="{{ old('date_commande', now()->toDateString()) }}" required>
                                @error('date_commande') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="h6 mb-0">Lignes de la commande</h3>
                            <button type="button" class="btn btn-sm btn-outline-primary" @click="ajouterLigne()">
                                <i class="bi bi-plus-lg"></i> Ajouter une ligne
                            </button>
                        </div>

                        {{-- Fond gris clair pour le panneau : chaque ligne reste une --}}
                        {{-- vraie carte blanche qui se détache nettement dessus, au --}}
                        {{-- lieu de se fondre dans le blanc du formulaire (confusion --}}
                        {{-- signalée quand plusieurs lignes sont dupliquées). --}}
                        <div class="bg-light rounded p-2 p-md-3 mb-2">
                        <template x-for="(ligne, index) in lignes" :key="ligne._cle">
                            <div class="card bg-white shadow-sm mb-3" :class="{ 'border-danger': estDoublon(index) }">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                                        <span class="fw-semibold text-secondary">Ligne <span x-text="index + 1"></span></span>
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger" title="Retirer cette ligne" @click="lignes.length > 1 && lignes.splice(index, 1)">
                                            <i class="bi bi-x-lg"></i>
                                            <span class="visually-hidden">Retirer cette ligne</span>
                                        </button>
                                    </div>
                                    <div class="row g-2 align-items-end mb-2">
                                        <div class="col-12 col-md-7">
                                            <label class="form-label small">Produit<span class="required-marker">*</span></label>
                                            <select :name="'lignes['+index+'][produit_id]'" x-model="ligne.produit_id" @change="ligne.unite_vente_id = ''"
                                                    class="form-select form-select-sm produit-ligne-select" :class="{ 'is-invalid': estDoublon(index) }"
                                                    x-init="$nextTick(() => window.initSelect2($el, { width: '100%' }))" required>
                                                <option value="">— Choisir —</option>
                                                @foreach ($produits as $produit)
                                                    <option value="{{ $produit->id }}">{{ $produit->sku }} — {{ $produit->libelle_affichage }}</option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger small mt-1" x-show="estDoublon(index)" x-cloak>Ce produit, avec la même unité et la même destination, est déjà présent dans une autre ligne.</div>
                                            <div class="text-danger small mt-1" x-show="erreurs['lignes.'+index+'.produit_id']" x-text="(erreurs['lignes.'+index+'.produit_id'] || [])[0]"></div>
                                        </div>
                                        <div class="col-12 col-md-5">
                                            <label class="form-label small">Unité d'achat<span class="required-marker">*</span></label>
                                            <select :name="'lignes['+index+'][unite_vente_id]'" x-model="ligne.unite_vente_id" class="form-select form-select-sm">
                                                <option value="" :selected="!ligne.unite_vente_id" x-text="baseLibelle(ligne.produit_id)"></option>
                                                <template x-for="option in unitesDisponibles(ligne.produit_id)" :key="option.id">
                                                    <option :value="option.id" :selected="String(option.id) === String(ligne.unite_vente_id)" x-text="option.libelle"></option>
                                                </template>
                                            </select>
                                            <div class="text-danger small mt-1" x-show="erreurs['lignes.'+index+'.unite_vente_id']" x-text="(erreurs['lignes.'+index+'.unite_vente_id'] || [])[0]"></div>
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-end mb-2">
                                        <div class="col-4">
                                            <label class="form-label small" x-text="'Quantité (' + uniteChoisie(ligne).libelle + ') *'"></label>
                                            <input type="number" :name="'lignes['+index+'][quantite]'" x-model="ligne.quantite" class="form-control form-control-sm" min="1" step="1" required>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small" x-text="'Prix d\'achat HT (' + uniteChoisie(ligne).libelle + ', F) *'"></label>
                                            <input type="number" :name="'lignes['+index+'][prix_achat]'" x-model="ligne.prix_achat" class="form-control form-control-sm" min="0" step="1" required>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label small">Qté en pièces (stock)</label>
                                            <input type="text" class="form-control form-control-sm" :value="quantitePieces(ligne)" disabled>
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-end">
                                        <div class="col-6 col-md-4">
                                            <label class="form-label small">Destination<span class="required-marker">*</span></label>
                                            <select :name="'lignes['+index+'][magasin_destination_id]'" x-model="ligne.magasin_destination_id" class="form-select form-select-sm" required>
                                                <option value="">— Choisir —</option>
                                                @foreach ($magasins as $magasin)
                                                    <option value="{{ $magasin->id }}">{{ $magasin->nom }} @if ($magasin->estDepot()) (dépôt) @endif</option>
                                                @endforeach
                                            </select>
                                            <div class="text-danger small mt-1" x-show="erreurs['lignes.'+index+'.magasin_destination_id']" x-text="(erreurs['lignes.'+index+'.magasin_destination_id'] || [])[0]"></div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small">Taxe</label>
                                            <select :name="'lignes['+index+'][taxe_id]'" x-model="ligne.taxe_id" class="form-select form-select-sm">
                                                <option value="" :selected="!ligne.taxe_id">Aucune</option>
                                                <template x-for="taxe in taxes" :key="taxe.id">
                                                    <option :value="taxe.id" :selected="String(taxe.id) === String(ligne.taxe_id)" x-text="taxe.libelle + ' (' + taxe.taux + '%)'"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small">Total HT</label>
                                            <input type="text" class="form-control form-control-sm" :value="montantHtLigne(ligne).toLocaleString('fr-FR')" disabled>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <label class="form-label small">Total TTC</label>
                                            <input type="text" class="form-control form-control-sm fw-semibold" :value="montantTtcLigne(ligne).toLocaleString('fr-FR') + ' ' + window.DEVISE_ABREVIATION" disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <button type="button" class="btn btn-sm btn-outline-primary" @click="ajouterLigne()">
                            <i class="bi bi-plus-lg"></i> Ajouter une ligne
                        </button>
                        </div>

                        @error('lignes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('lignes.*.quantite') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        @error('lignes.*.prix_achat') <div class="text-danger small mt-1">{{ $message }}</div> @enderror

                        <div class="text-danger small mt-2" x-show="aUnDoublon" x-cloak>
                            <i class="bi bi-exclamation-triangle-fill me-1"></i>Un même produit avec la même unité et la même destination ne peut pas apparaître sur plusieurs lignes : corrigez avant d'enregistrer.
                        </div>

                        <hr>

                        <div class="d-flex justify-content-end mb-3">
                            <div class="text-end">
                                <div class="text-secondary">Total HT : <span class="fw-semibold" x-text="totalHt.toLocaleString('fr-FR') + ' ' + window.DEVISE_ABREVIATION"></span></div>
                                <div class="text-secondary">Total taxes : <span class="fw-semibold" x-text="totalTaxes.toLocaleString('fr-FR') + ' ' + window.DEVISE_ABREVIATION"></span></div>
                                <div class="h5 mb-0">Total TTC : <span class="fw-bold" x-text="totalTtc.toLocaleString('fr-FR') + ' ' + window.DEVISE_ABREVIATION"></span></div>
                            </div>
                        </div>

                        @if ($peutValider)
                            <div class="card mb-3 bg-white border">
                                <div class="card-body">
                                    <h3 class="h6">Règlement au fournisseur</h3>
                                    <p class="text-secondary small">
                                        Laissez vide pour ne rien encaisser maintenant : le total TTC devient une dette fournisseur.
                                    </p>

                                    <template x-for="(paiement, index) in paiements" :key="paiement._cle">
                                        <div class="row g-1 align-items-center mb-2">
                                            <div class="col-6 col-md-4">
                                                <select :name="'paiements['+index+'][moyen_paiement_id]'" x-model="paiement.moyen_paiement_id" class="form-select form-select-sm">
                                                    <option value="">Moyen…</option>
                                                    @foreach ($moyensPaiement as $moyen)
                                                        <option value="{{ $moyen->id }}">{{ $moyen->nom }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4 col-md-3">
                                                <input type="number" :name="'paiements['+index+'][montant]'" x-model.number="paiement.montant" min="1" class="form-control form-control-sm" placeholder="Montant">
                                            </div>
                                            <div class="col-2">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger" @click="retirerPaiement(index)">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                    <button type="button" class="btn btn-sm btn-outline-primary mb-2" @click="ajouterPaiement()">
                                        <i class="bi bi-plus-lg"></i> Ajouter un paiement
                                    </button>
                                    <div class="mb-0 small text-secondary">
                                        Reste dû au fournisseur après validation : <span class="fw-semibold" x-text="resteDu.toLocaleString('fr-FR') + ' ' + window.DEVISE_ABREVIATION"></span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <hr>

                        @unless ($peutValider)
                            <button type="submit" class="btn btn-outline-primary" @click="actionSoumission = 'brouillon'" :disabled="aUnDoublon">
                                Enregistrer en brouillon
                            </button>
                        @endunless
                        @if ($peutValider)
                            <button type="button" class="btn btn-success" :disabled="aUnDoublon"
                                    @click="declencherValidation($event)"
                                    data-form-id="formCommandeAchat"
                                    data-message="Valider ce bon d'achat maintenant ? Le stock sera mis à jour immédiatement et cette action est irréversible."
                                    data-button-label="Valider le bon d'achat" data-button-class="btn-success">
                                <i class="bi bi-check-circle me-1"></i>Valider le bon d'achat
                            </button>
                        @endif
                        <a href="{{ route('commande-achats.index') }}" class="btn btn-link">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
