# CLAUDE.md

Contexte projet pour agents de code (Claude Code). Ce fichier décrit **quoi construire**
et surtout **les règles à ne jamais violer**. Le détail fonctionnel complet est dans
`CAHIER_DES_CHARGES.md`.

---

## Vue d'ensemble

Application web **ERP (inspiration Odoo)** de **caisse (POS) + gestion de stock** pour un
vendeur de **vaisselle** exploitant **plusieurs magasins**. Cible : utilisateurs **non
techniques** — interface simple et intuitive.

- Web **responsive** (smartphone / tablette / desktop).
- **MVP connecté** : la caisse dialogue avec le serveur. Hors-ligne reporté —
  **ne pas** implémenter de synchronisation ni de base locale.

---

## Stack technique

- **Framework** : Laravel 13 (PHP). **Vues** : Blade.
- **UI** : Bootstrap. **Réactivité client** : Alpine.js (pas de SPA lourde).
- **Base de données** : moteur relationnel **ACID** (transactions obligatoires).

### Packages imposés
- `spatie/laravel-permission` — rôles et permissions **dynamiques**.
- `spatie/laravel-medialibrary` — images produits.
- `spatie/laravel-activitylog` — journaux d'activité.
- `yajra/laravel-datatables-oracle` — tableaux de données (mode serveur).

---

## Règles métier NON NÉGOCIABLES

1. **Le stock est dérivé, jamais écrasé.** Quantité disponible = somme des mouvements.
   Jamais `UPDATE stock SET quantite = X` ; toujours insérer un mouvement.
2. **Un mouvement de stock est immuable.** Pas de `UPDATE`/`DELETE` ; correction = mouvement inverse.
3. **Une vente est atomique.** Stock (mouvement) + paiements + session dans un seul
   `DB::transaction`. Tout ou rien.
4. **Le SKU identifie un produit**, jamais son nom (deux produits peuvent avoir le même nom).
5. **Le stock se compte en pièces.** Une unité de vente a un facteur ; vendre un lot de N
   décrémente N pièces.
6. **Vendre exige une session ouverte sur une caisse LIBRE** — pour TOUS les rôles, y
   compris gérant, admin et superadmin. Aucune vente hors session.
7. **Une caisse n'a qu'UNE session ouverte à la fois.** « Libre » = sans session ouverte.
8. **Interdit de clôturer OU fermer une session tant qu'il reste des ventes en attente**
   sur la caisse. Chaque panier en attente doit être finalisé ou **annulé** d'abord.
9. **La clôture se calcule par session**, pas par jour.
10. **Seules les espèces entrent dans le comptage du tiroir.** Autres moyens totalisés à part.
11. **Toute action sensible est tracée** via `activitylog`.

---

## Ventes en attente

- Un panier mis de côté : **aucun mouvement de stock, aucun encaissement**.
- **Rattachée au caissier** qui l'a créée ; un caissier ne voit que **ses** ventes en attente.
- Le **gérant** peut voir et **annuler** les ventes en attente d'une caisse de son magasin.
- Actions : reprendre (finaliser) ou annuler.
- Bloque la clôture/fermeture de la session tant qu'elle existe (règle 8).

---

## Remises (vente)

- **Par ligne** : montant OU pourcentage. **Sur le total** : montant OU pourcentage.
- Ordre : remises de ligne d'abord, puis remise sur le sous-total.
- Stocker le type (`montant`|`pourcentage`), la valeur saisie **et** le montant résolu
  appliqué, pour des tickets et rapports reproductibles.

---

## Argent et arrondis

- Devise **XOF**, **sans sous-unité** → montants en **entiers (francs)**. Jamais de flottant.
- Remise en pourcentage → montant arrondi à l'entier ; règle d'arrondi unique, appliquée partout.
- Pas de taxes dans le MVP (aucun taux, aucune ligne taxe sur le ticket).
- Clôture de session = `fond de caisse + total ventes espèces` (pas de mouvement de
  caisse hors-vente au MVP).

---

## Rôles et permissions (dynamiques, spatie/laravel-permission)

- **Rôles créés à la volée** par l'admin, avec attribution de permissions à la carte.
  Ne pas coder les autorisations en dur ; toujours vérifier via permission.
- **Convention de nommage : `module.action`** — ex. `vente.creer`, `achat.creer`,
  `achat.receptionner`, `produit.voir`, `produit.modifier`, `stock.ajuster`,
  `stock.transferer`, `inventaire.realiser`, `caisse.ouvrir`, `caisse.cloturer`,
  `rapport.voir`, `utilisateur.gerer`, `role.gerer`, `parametre.gerer`.
- **Superadmin** : bypass total via `Gate::before` (ne reçoit pas de permissions).
- **Noyau de permissions système protégé** : non attribuable aux rôles créés à la volée.
- Rôles par défaut seedés : Superadmin, Gérant, Caissier — **modifiables**.

---

## Modèle de domaine (entités clés)

- **Magasin** : point de vente.
- **Produit** : SKU (unique), nom, libellé distinctif, catégorie, **prix pièce** (prix de
  vente unitaire), seuil d'alerte, image (medialibrary).
- **UniteVente** (optionnelle, plusieurs par produit) : rattachée à un produit ; facteur
  (pièces) + prix total propre. Libellé UI retenu : « **Unité de vente** ». La pièce
  elle-même n'est pas une ligne `UniteVente` : c'est le `prix_piece` du produit.
- **Stock** : quantité par (produit × magasin), *dérivée des mouvements* ; porte aussi le
  **coût moyen pondéré (CMP)**, recalculé à chaque entrée d'achat.
- **MouvementStock** : immuable ; type = réception | vente | casse | transfert | ajustement ;
  quantité en pièces ; magasin ; référence source.
- **Inventaire** : fiche par magasin (date, statut brouillon/validé).
- **LigneInventaire** : produit + quantité comptée + écart calculé.
- **Fournisseur**, **CommandeAchat** : pas d'entité « Réception » séparée — une commande
  d'achat a un numéro, une date, des lignes (produit + quantité + **prix d'achat**) ; à sa
  **validation**, le stock est directement impacté (mouvements d'entrée) et le CMP
  recalculé. Un produit peut être acheté à des prix différents dans le temps : le CMP
  lisse ça en une seule valeur de référence par (produit × magasin).
- **Caisse** : rattachée à un magasin ; au plus une session ouverte à la fois.
- **SessionCaisse** : caissier + fond de caisse + ouverture/clôture + écart. Pas de
  mouvement de caisse hors-vente (appoint/prélèvement) au MVP ; clôture = fond de caisse +
  total ventes espèces.
- **Vente** : numéro (`M{magasin}-C{caisse}-{séquence}`), lignes, remise total, session,
  magasin.
- **LigneVente** : produit + (pièce **ou** unité de vente) + quantité + prix appliqué +
  remise de ligne + **coût appliqué** (CMP figé au moment de la vente, pour un rapport de
  marge reproductible dans le temps).
- **Paiement** : rattaché à une vente ; moyen + montant (plusieurs = paiement mixte).
- **MoyenPaiement** : configurable, actif/inactif ; espèces par défaut.
- **VenteEnAttente** : panier non finalisé + caissier propriétaire ; sans mouvement ni
  paiement ; reprise = continuation du panier au **prix courant** (pas de figeage de prix).
- **Utilisateur** : rôle(s), magasin de rattachement.

---

## Interface (ERP type Odoo)

- **Layout à sidebar** : Tableau de bord, Ventes, Caisses, Stock, Inventaire, Achats,
  Catalogue, Rapports, Administration.
- **Dashboard adapté au rôle** : caissier → sa session ; gérant → son magasin (CA, panier
  moyen, top produits, stock sous seuil, écarts de caisse) ; superadmin → consolidé
  multi-magasin. Graphiques (ventes dans le temps, moyens de paiement, top produits).
- Palette ERP : primaire violet/prune, accents turquoise, surfaces claires, gris neutres.
- Listes en **DataTables serveur** (jamais charger toute une table en mémoire).
- Feedback explicite (toasts), sentence case, responsive (sidebar repliable sur mobile).
- **Rupture de stock** : produit ou unité de vente en stock insuffisant affiché **grisé**
  avec mention « rupture de stock », non sélectionnable au panier (blocage en amont dans
  l'UI, pas d'erreur après coup).

---

## Performance (obligatoire)

- **Cache Laravel** : `config/route/view:cache` en prod ; `Cache::remember` pour les
  données de référence (catalogue, moyens de paiement) avec **invalidation** aux
  mises à jour.
- **Eager loading** (`with()`) systématique — zéro N+1.
- **Index** DB sur FK et colonnes filtrées (SKU, magasin_id, dates).
- **Pagination** partout ; DataTables mode serveur.
- Rapports lourds → vues SQL / agrégats mis en cache.

---

## Traçabilité

- **activitylog** sur entités sensibles (produits, prix, mouvements, ventes, sessions,
  utilisateurs, rôles) : auteur, date, avant/après.
- **Historique connexions** : login/logout, date, utilisateur, IP (écouter les événements d'auth).

---

## Conventions

- Terminologie métier en **français** (magasin, caisse, mouvement, unité de vente…).
- Écritures stock/vente **uniquement via une couche service** encapsulant la transaction ;
  pas d'écriture directe depuis les contrôleurs.
- Rapports = agrégats/vues, sans dupliquer la logique métier.

---

## Hors périmètre (ne pas implémenter dans le MVP)

- Hors-ligne, base locale, synchronisation.
- Facture normalisée FNE — mais **structurer la vente** pour la brancher plus tard.
- Fidélité, promotions avancées, comptabilité, paie.
- **Taxes** — aucun taux, aucune ligne taxe.
- **Retours et avoirs clients** — aucun flux de retour.
- **Mouvements de caisse hors-vente** (appoint, prélèvement).
- Entité « Réception » séparée d'une commande d'achat — la validation d'une commande
  d'achat impacte le stock directement (voir modèle de domaine).

---

## Décisions actées (voir CAHIER_DES_CHARGES §8 pour le détail)

Toutes les hypothèses initiales sont tranchées : prix des unités de vente = prix total ;
casse = mouvement dédié ; remises cumulables (ligne puis sous-total) ; libellé UI
« Unité de vente » ; prix pièce = champ direct sur le produit ; vente en attente reprise
au prix courant ; transfert de stock inter-magasin simple (sortie + entrée, sans
valorisation) ; achat = entrée de stock directe à la validation (pas de « Réception »
séparée), coût suivi via **CMP** (recalculé à chaque achat, figé sur chaque ligne de
vente pour la marge) ; taxes, retours et mouvements de caisse hors-vente exclus du MVP
(voir « Hors périmètre » ci-dessus).
