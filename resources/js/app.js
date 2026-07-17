import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import Alpine from 'alpinejs';
window.Alpine = Alpine;

import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);
window.Chart = Chart;

// jQuery n'est chargé que pour DataTables.net et Select2 (aucune des deux
// n'a d'équivalent mûr sans jQuery) — le reste de l'app reste en Alpine.js.
import jQuery from 'jquery';
window.$ = window.jQuery = jQuery;

import 'datatables.net-bs5';

// Le module select2 exporte sa fonction d'attache UMD (factory(root, jQuery))
// plutôt que de s'auto-attacher : sous bundling ESM/CJS il faut l'appeler
// explicitement, sinon $.fn.select2 n'existe jamais silencieusement.
import select2Factory from 'select2';
select2Factory(window, jQuery);
import 'select2/dist/css/select2.min.css';
import 'select2-bootstrap-5-theme/dist/select2-bootstrap-5-theme.min.css';

// Sélecteur recherchable (produits, etc.) — thème Bootstrap 5 cohérent avec le
// reste de l'app. Un seul point d'appel pour ne pas répéter les options.
window.initSelect2 = function (selector, options = {}) {
    const $select = jQuery(selector).select2({
        theme: 'bootstrap-5',
        width: '100%',
        language: {
            noResults: () => 'Aucun résultat',
            searching: () => 'Recherche…',
            inputTooShort: () => 'Continuez à taper…',
        },
        ...options,
    });

    // Select2 met à jour le <select> natif puis fait jQuery(...).trigger('change'),
    // qui ne notifie que les écouteurs jQuery — pas les addEventListener('change', …)
    // natifs utilisés par Alpine (x-model). On redispatche donc un vrai événement
    // natif pour que la réactivité Alpine (ex. détection de doublon en temps réel)
    // voie la sélection.
    $select.on('select2:select select2:unselect select2:clear', function () {
        this.dispatchEvent(new Event('change', { bubbles: true }));
    });

    return $select;
};

// Traduction française réutilisée par tous les tableaux DataTables de l'app.
window.dataTableFrLang = {
    processing: 'Traitement en cours…',
    search: 'Rechercher :',
    lengthMenu: 'Afficher _MENU_ éléments',
    info: 'Affichage de _START_ à _END_ sur _TOTAL_ éléments',
    infoEmpty: 'Affichage de 0 à 0 sur 0 élément',
    infoFiltered: '(filtré depuis _MAX_ éléments au total)',
    loadingRecords: 'Chargement…',
    zeroRecords: 'Aucun élément trouvé',
    emptyTable: 'Aucune donnée disponible',
    paginate: { first: 'Premier', previous: 'Précédent', next: 'Suivant', last: 'Dernier' },
};

/**
 * État du panier de l'écran de vente (POS). Le stock disponible est vérifié
 * côté client pour griser en amont (voir CLAUDE.md), le serveur revalide tout
 * de toute façon dans VenteService avant d'écrire le moindre mouvement.
 */
window.posApp = function (produits) {
    return {
        produits,
        panier: [],
        remiseTotaleType: '',
        remiseTotaleValeur: null,
        paiements: [{ moyen_paiement_id: '', montant: null }],
        libelleAttente: '',

        // La barre du haut ajoute une ligne « vierge » (aucune variante choisie,
        // aucun calcul) — l'utilisateur choisit ensuite pièce/lot directement
        // sur la ligne (voir changerVarianteDepuisSelect), et c'est CE choix qui
        // déclenche prix, réservation de stock et vérification de doublon.
        ajouterDepuisSelect(event) {
            const option = event.target.selectedOptions[0];
            if (!option || !option.value) return;

            const produit = this.produits.find((p) => p.id === Number(option.dataset.produitId));
            if (produit) {
                this.ajouterLigneVierge(produit);
            }

            window.jQuery(event.target).val('').trigger('change');
        },

        ajouterLigneVierge(produit) {
            // On ne fusionne que sur une ligne du même produit qui n'a pas
            // encore de variante choisie : une fois une variante assignée, une
            // nouvelle sélection du même produit ouvre une ligne indépendante.
            const existante = this.panier.find((l) => l.produit_id === produit.id && l.unite_vente_id === undefined);
            if (existante) {
                existante.quantite += 1;
                return;
            }

            this.panier.push({
                produit_id: produit.id,
                unite_vente_id: undefined,
                produitLibelle: produit.libelle_affichage,
                uniteLibelle: null,
                facteur: null,
                quantite: 1,
                prixUnitaire: null,
                remise_type: '',
                remise_valeur: null,
            });
        },

        dupliquerLigne(index) {
            const original = this.panier[index];
            this.panier.splice(index + 1, 0, {
                produit_id: original.produit_id,
                unite_vente_id: undefined,
                produitLibelle: original.produitLibelle,
                uniteLibelle: null,
                facteur: null,
                quantite: 1,
                prixUnitaire: null,
                remise_type: '',
                remise_valeur: null,
            });
        },

        piecesReservees(produitId) {
            return this.panier
                .filter((l) => l.produit_id === produitId)
                .reduce((somme, l) => somme + l.quantite * (l.facteur || 0), 0);
        },

        // Deux lignes ne doivent jamais partager le même produit ET la même
        // unité (pièce ou un lot précis) une fois choisie — ça peut arriver en
        // dupliquant une ligne puis en reprenant la même variante. Tant qu'une
        // ligne n'a pas encore de variante choisie, elle n'est jamais comptée
        // comme doublon (rien à comparer). On bloque l'enregistrement tant que
        // ce n'est pas corrigé (voir aUnDoublon / aUneLigneNonChoisie).
        estDoublon(index) {
            const ligne = this.panier[index];
            if (ligne.unite_vente_id === undefined) return false;
            return this.panier.some(
                (l, i) => i !== index && l.produit_id === ligne.produit_id && l.unite_vente_id === ligne.unite_vente_id
            );
        },

        get aUnDoublon() {
            return this.panier.some((l, i) => this.estDoublon(i));
        },

        get aUneLigneNonChoisie() {
            return this.panier.some((l) => l.unite_vente_id === undefined);
        },

        produitDe(ligne) {
            return this.produits.find((p) => p.id === ligne.produit_id);
        },

        changerVarianteDepuisSelect(ligne, valeur) {
            const produit = this.produitDe(ligne);
            if (!produit) return;

            if (valeur === '') {
                ligne.unite_vente_id = undefined;
                ligne.uniteLibelle = null;
                ligne.facteur = null;
                ligne.prixUnitaire = null;
                return;
            }

            const unite = valeur === 'piece' ? null : produit.unites.find((u) => u.id === Number(valeur));
            const facteur = unite ? unite.facteur : 1;
            const prixUnitaire = unite ? unite.prix : produit.prix_piece;

            const autresPieces = this.piecesReservees(ligne.produit_id) - ligne.quantite * (ligne.facteur || 0);
            if (autresPieces + ligne.quantite * facteur > produit.stock) return;

            ligne.unite_vente_id = unite ? unite.id : null;
            ligne.uniteLibelle = unite ? unite.libelle : null;
            ligne.facteur = facteur;
            ligne.prixUnitaire = prixUnitaire;
        },

        changerQuantite(ligne, delta) {
            const produit = this.produitDe(ligne);
            const nouvelle = ligne.quantite + delta;
            if (nouvelle < 1) {
                this.panier.splice(this.panier.indexOf(ligne), 1);
                return;
            }
            const autresPieces = this.piecesReservees(ligne.produit_id) - ligne.quantite * (ligne.facteur || 0);
            if (autresPieces + nouvelle * (ligne.facteur || 0) > produit.stock) return;
            ligne.quantite = nouvelle;
        },

        retirerLigne(index) {
            this.panier.splice(index, 1);
        },

        totalLigne(ligne) {
            if (ligne.prixUnitaire === null) return 0;
            const sousTotal = ligne.prixUnitaire * ligne.quantite;
            return sousTotal - this.calculerRemise(ligne.remise_type, ligne.remise_valeur, sousTotal);
        },

        calculerRemise(type, valeur, base) {
            if (!type || !valeur) return 0;
            const montant = type === 'pourcentage' ? Math.round((base * valeur) / 100) : Number(valeur);
            return Math.min(montant, base);
        },

        get sousTotal() {
            return this.panier.reduce((somme, l) => somme + this.totalLigne(l), 0);
        },

        get remiseTotaleMontant() {
            return this.calculerRemise(this.remiseTotaleType, this.remiseTotaleValeur, this.sousTotal);
        },

        get totalNet() {
            return this.sousTotal - this.remiseTotaleMontant;
        },

        get totalPaiements() {
            return this.paiements.reduce((somme, p) => somme + (Number(p.montant) || 0), 0);
        },

        ajouterPaiement() {
            this.paiements.push({ moyen_paiement_id: '', montant: null });
        },

        retirerPaiement(index) {
            if (this.paiements.length > 1) this.paiements.splice(index, 1);
        },

        completerPaiement(index) {
            const reste = this.totalNet - (this.totalPaiements - (Number(this.paiements[index].montant) || 0));
            this.paiements[index].montant = Math.max(reste, 0);
        },
    };
};

// Validation Bootstrap générique : chaque formulaire de l'app s'appuie sur les
// contraintes HTML natives (required, min, type=email, unique côté serveur…) et
// affiche le style Bootstrap (bordure rouge + .invalid-feedback) au lieu des bulles
// natives du navigateur. `noValidate` est posé en JS plutôt que dans chaque vue pour
// ne pas dupliquer l'attribut sur des dizaines de formulaires.
document.querySelectorAll('form').forEach((form) => {
    form.noValidate = true;
});

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
    }
    form.classList.add('was-validated');
}, true);

Alpine.start();

// Modal de confirmation de suppression partagé (voir partials/confirm-delete-modal.blade.php
// et components/delete-button.blade.php) : jamais de confirm() natif du navigateur.
document.addEventListener('show.bs.modal', (event) => {
    const modal = event.target;
    if (modal.id !== 'confirmDeleteModal') {
        return;
    }

    const trigger = event.relatedTarget;
    const formId = trigger?.getAttribute('data-form-id');
    const label = trigger?.getAttribute('data-label') || 'cet élément';

    modal.querySelector('#confirmDeleteLabel').textContent = label;

    const confirmButton = modal.querySelector('#confirmDeleteButton');
    confirmButton.onclick = () => {
        document.getElementById(formId)?.submit();
    };
});

// Modal de confirmation générique (Valider, Clôturer…) : voir
// partials/confirm-action-modal.blade.php et components/confirm-button.blade.php.
document.addEventListener('show.bs.modal', (event) => {
    const modal = event.target;
    if (modal.id !== 'confirmActionModal') {
        return;
    }

    const trigger = event.relatedTarget;
    const formId = trigger?.getAttribute('data-form-id');
    const message = trigger?.getAttribute('data-message') || 'Confirmer cette action ?';
    const buttonLabel = trigger?.getAttribute('data-button-label') || 'Confirmer';
    const buttonClass = trigger?.getAttribute('data-button-class') || 'btn-primary';

    modal.querySelector('#confirmActionMessage').textContent = message;

    const confirmButton = modal.querySelector('#confirmActionButton');
    confirmButton.className = 'btn ' + buttonClass;
    confirmButton.textContent = buttonLabel;
    confirmButton.onclick = () => {
        // requestSubmit() (contrairement à submit()) déclenche l'événement
        // 'submit' et la validation native — nécessaire pour les formulaires
        // avec des champs required (ex. valider un achat depuis sa création).
        document.getElementById(formId)?.requestSubmit();
    };
});
