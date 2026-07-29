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
window.posApp = function (produits, panierInitial = [], libelleInitial = '') {
    return {
        produits,
        panier: panierInitial,
        remiseTotaleType: '',
        remiseTotaleValeur: null,
        paiements: [{ moyen_paiement_id: '', montant: null }],
        libelleAttente: libelleInitial,

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

        // Un montant saisi sans moyen de paiement choisi échouerait silencieusement
        // à l'enregistrement (validation serveur), donc on bloque en amont.
        get aUnPaiementSansMoyen() {
            return this.paiements.some((p) => (Number(p.montant) || 0) > 0 && !p.moyen_paiement_id);
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

        // Le client peut donner plus que le net à payer (monnaie à rendre) :
        // ce qui est réellement enregistré comme encaissement ne doit jamais
        // dépasser le net à payer, sinon le tiroir-caisse serait faussé à la
        // clôture (voir CLAUDE.md, règle du comptage espèces). Les montants
        // saisis servent au calcul de la monnaie à rendre, mais c'est cette
        // liste "plafonnée" qui est réellement envoyée au serveur.
        get monnaieARendre() {
            return Math.max(this.totalPaiements - this.totalNet, 0);
        },

        get paiementsAppliques() {
            let restant = this.totalNet;
            const resultats = [];
            for (const p of this.paiements) {
                const montant = Number(p.montant) || 0;
                if (montant <= 0 || restant <= 0) continue;
                const applique = Math.min(montant, restant);
                resultats.push({ moyen_paiement_id: p.moyen_paiement_id, montant: applique });
                restant -= applique;
            }
            return resultats;
        },

        declencherFinalisation(event) {
            const form = document.getElementById('formVente');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                form.reportValidity();
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show(event.currentTarget);
        },

        declencherMiseEnAttente(event) {
            const form = document.getElementById('formAttente');
            if (!form.checkValidity()) {
                form.classList.add('was-validated');
                form.reportValidity();
                return;
            }
            window.bootstrap.Modal.getOrCreateInstance(document.getElementById('confirmActionModal')).show(event.currentTarget);
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

// Spinner générique + anti double-soumission — voir demarrerSpinner() plus bas.
// Retient le bouton qui a ouvert une popup de confirmation, pour lui appliquer
// le spinner (et pas au bouton "Confirmer" de la popup) une fois le formulaire
// réellement soumis via requestSubmit().
let dernierDeclencheurConfirmation = null;

document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
        form.classList.add('was-validated');
        return;
    }
    form.classList.add('was-validated');

    const bouton = event.submitter
        ?? (dernierDeclencheurConfirmation?.getAttribute('data-form-id') === form.id ? dernierDeclencheurConfirmation : null);
    dernierDeclencheurConfirmation = null;

    if (bouton instanceof HTMLElement) {
        demarrerSpinner(bouton);
        if (form.dataset.telechargement !== undefined) {
            surveillerTelechargement(bouton);
        }
    }
}, true);

// Formulaires de téléchargement de fichier (ex. sauvegarde) : la réponse est
// un fichier, pas une page — aucun rechargement ne vient jamais réinitialiser
// le spinner générique tout seul. Le contrôleur pose un cookie une fois le
// fichier prêt à être envoyé (ex. ParametreController::backup()) ; on le
// sonde pour arrêter le spinner dès que le téléchargement démarre vraiment,
// plutôt que d'attendre le délai de sécurité de 20 s (qui afficherait à tort
// une erreur de lenteur alors que le fichier a bien été livré).
function surveillerTelechargement(bouton) {
    const intervalle = window.setInterval(() => {
        if (!document.cookie.includes('telechargement_pret=1')) {
            return;
        }
        window.clearInterval(intervalle);
        document.cookie = 'telechargement_pret=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
        arreterSpinner(bouton);
    }, 300);
}

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
        dernierDeclencheurConfirmation = trigger ?? null;
        // requestSubmit() (contrairement à submit()) déclenche l'événement
        // 'submit', nécessaire pour que le spinner générique s'applique ici
        // aussi.
        document.getElementById(formId)?.requestSubmit();
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
        dernierDeclencheurConfirmation = trigger ?? null;
        // requestSubmit() (contrairement à submit()) déclenche l'événement
        // 'submit' et la validation native — nécessaire pour les formulaires
        // avec des champs required (ex. valider un achat depuis sa création).
        document.getElementById(formId)?.requestSubmit();
    };
});

// --- Spinner générique sur tout bouton qui soumet un formulaire ---
//
// Empêche un double-clic de renvoyer deux fois la même action (ex. finaliser
// deux fois la même vente) et donne un retour visuel immédiat. Comme les
// formulaires de l'app se soumettent classiquement (rechargement de page, pas
// de fetch), le navigateur ne prévient pas toujours JS en cas d'échec réseau
// pur (serveur injoignable) : un délai de sécurité réactive alors le bouton et
// signale l'erreur, plutôt que de laisser le spinner tourner indéfiniment. En
// cas d'erreur métier normale (validation, stock insuffisant…), Laravel
// recharge la page avec le message habituel — le spinner disparaît de
// lui-même puisque toute la page est remplacée.
const DELAI_SECURITE_SPINNER_MS = 20000;

function demarrerSpinner(bouton) {
    if (bouton.dataset.spinnerActif) {
        return;
    }

    bouton.dataset.spinnerActif = '1';
    bouton.dataset.libelleOriginal = bouton.innerHTML;
    bouton.disabled = true;

    const texte = bouton.textContent.trim();
    const spinner = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    bouton.innerHTML = texte ? `${spinner} ${texte}` : spinner;

    window.setTimeout(() => {
        if (bouton.isConnected && bouton.dataset.spinnerActif) {
            arreterSpinner(bouton);
            afficherToastErreur("La requête met trop de temps à répondre. Vérifiez votre connexion et réessayez.");
        }
    }, DELAI_SECURITE_SPINNER_MS);
}

function arreterSpinner(bouton) {
    bouton.disabled = false;
    if (bouton.dataset.libelleOriginal !== undefined) {
        bouton.innerHTML = bouton.dataset.libelleOriginal;
    }
    delete bouton.dataset.spinnerActif;
    delete bouton.dataset.libelleOriginal;
}

function afficherToastErreur(message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = 1080;
        document.body.appendChild(container);
    }

    const toastEl = document.createElement('div');
    toastEl.className = 'toast align-items-center text-bg-danger border-0';
    toastEl.setAttribute('role', 'alert');
    toastEl.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    container.appendChild(toastEl);

    const toast = window.bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 8000 });
    toast.show();
    toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
}
