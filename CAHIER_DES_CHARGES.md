# Cahier des charges — Application POS & gestion de stock

**Secteur :** vente de vaisselle
**Type :** application ERP (inspiration Odoo)
**Version :** 0.4 (MVP)
**Statut :** hypothèses tranchées — prêt pour conception technique (ERD)

---

## 1. Contexte et objectif

Le client vend de la vaisselle dans plusieurs magasins et veut un logiciel de caisse
(type supermarché) couplé à une gestion de stock, dans une application **ERP simple et
intuitive**, utilisable par des personnes non techniques. L'application doit faire tourner
deux boucles cohérentes en permanence :

- **Boucle marchandise** — la quantité disponible d'un produit augmente
  (réception d'achat) et diminue (vente, casse, transfert), et se contrôle par inventaire.
- **Boucle argent** — chaque vente encaisse dans une caisse ouverte, et à la clôture on
  doit retrouver : *fond de caisse + encaissements espèces = espèces comptées*.

Objectif principal : **vendre depuis une caisse et faire le point** (stock et argent).

---

## 2. Périmètre

### Inclus dans le MVP
- Tableau de bord (dashboard) avec indicateurs et graphiques, adapté au rôle.
- Multi-magasin (référentiel produit central, stock par magasin).
- Multi-caisse (plusieurs caisses ouvertes simultanément dans un magasin).
- Application web **responsive** (smartphone, tablette, desktop).
- Vente POS avec remises ligne/total, paiement mixte et ventes en attente.
- Gestion de stock par mouvements (réception, vente, casse, transfert, ajustement).
- **Inventaire** (comptage physique et régularisation des écarts).
- Approvisionnement (fournisseurs, commandes, réception).
- Gestion des caisses (ouverture, clôture, fermeture, historique).
- Moyens de paiement configurables.
- **Rôles et permissions dynamiques** (création de rôles, attribution fine de permissions).
- Traçabilité : journaux d'activité, historique connexions/déconnexions.
- **Rapports**.
- Ticket de caisse (reçu POS).

### Hors périmètre du MVP (évolutions futures)
- **Fonctionnement hors-ligne** et synchronisation (la caisse est connectée au serveur).
- **Facture électronique normalisée (FNE)** — la donnée de vente est structurée pour
  pouvoir la brancher plus tard sans refonte.
- Programme de fidélité, cartes clients, promotions avancées.
- Comptabilité complète, paie, RH.
- **Taxes** (TVA ou autre) — aucune gestion de taux dans le MVP.
- **Retours et avoirs clients** — aucun flux de retour dans le MVP.
- **Mouvements de caisse hors-vente** (appoint, prélèvement) — la clôture ne considère que
  le fond de caisse et les ventes en espèces.
- **Entité « Réception » distincte de la commande d'achat** — un achat est directement une
  entrée de stock : pas de processus commande → réception en deux temps.

---

## 3. Acteurs et rôles

Les rôles ci-dessous sont les **rôles par défaut** (créés au démarrage), mais le système
permet de **créer d'autres rôles** et de leur attribuer des permissions à la carte
(voir §4.11).

| Rôle par défaut | Périmètre |
|------|-----------|
| **Superadmin (développeur)** | Accès **total** contournant toutes les restrictions, tous les magasins, paramétrage système et journaux techniques. Réservé au développeur/mainteneur. N'a pas besoin qu'on lui attribue des permissions. |
| **Gérant / Admin** | Paramétrage (produits, prix, magasins, caisses, moyens de paiement, utilisateurs, rôles), consultation de tous les rapports, gestion des approvisionnements et inventaires. Peut voir et annuler les ventes en attente d'une caisse de son magasin. |
| **Caissier** | Ouvre une session sur une caisse libre de son magasin, vend, encaisse, gère **ses** ventes en attente, clôture sa propre session. Ne voit que le catalogue et le stock de son magasin. |

**Règle de vente commune à tous les rôles** : pour vendre, un utilisateur (y compris
gérant, admin ou superadmin) doit d'abord **ouvrir une session sur une caisse libre**.
Toute vente appartient à une session de caisse.

---

## 4. Modules fonctionnels

### 4.1 Tableau de bord (dashboard)
Page d'accueil après connexion, **adaptée au rôle** :
- **Caissier** : sa session en cours (ventes du jour, nombre de tickets, encaissements par
  moyen de paiement, panier moyen), ses ventes en attente.
- **Gérant** : indicateurs de son magasin — chiffre d'affaires (jour / semaine / mois),
  panier moyen, top produits, produits sous seuil d'alerte, valeur du stock (au CMP),
  écarts de caisse par session, caisses actuellement ouvertes.
- **Superadmin** : vue **consolidée multi-magasin** + comparatif par magasin.
- **Graphiques** : évolution des ventes dans le temps, répartition des moyens de paiement,
  top produits, ventes par magasin.

### 4.2 Catalogue produits
- Référentiel **central** partagé par tous les magasins.
- Chaque produit possède un **code unique (SKU) obligatoire** — c'est lui l'identifiant,
  jamais le nom.
- Champs : nom, **libellé distinctif court** (qualité, motif, provenance…), catégorie,
  SKU, code-barres, **image**, **prix pièce** (prix de vente unitaire), seuil d'alerte
  de rupture.
- Deux produits peuvent porter le même **nom** : ils restent distingués par leur SKU et
  leur libellé distinctif, affichés ensemble à la vente (nom + libellé + prix).
- **Variantes** (coloris / motif / taille) : chaque variante est une référence stockable
  distincte avec son propre SKU.

### 4.3 Unités de vente
- Chaque produit a un **prix pièce** (prix de vente unitaire), porté directement par sa
  fiche produit — c'est l'unité de base, toujours vendable.
- Un produit peut en plus avoir **plusieurs unités de vente** additionnelles (libellé UI
  retenu : « **Unité de vente** », ex. « Lot de 5 »), chacune avec :
  - un facteur de conversion vers la pièce (lot de 5 = 5, lot de 20 = 20),
  - un **prix de vente propre** (prix total de l'unité de vente, indépendant du prix pièce).
- Le stock est **toujours compté en unité de base : la pièce**.
- À la vente, chaque ligne du panier est vendue **soit à la pièce, soit via une unité de
  vente** ; le stock est décrémenté du nombre de pièces correspondant
  (ex. « 1 lot de 20 » → −20 pièces).
- **Prix de vente unique** : un produit a le même prix dans tous les magasins (pas de
  variation par magasin dans le MVP).

### 4.4 Gestion multi-magasin
- Le stock est une paire **(produit × magasin)** : quantité disponible par magasin.
- Rapports lisibles soit consolidés, soit par magasin.
- Transfert de stock inter-magasin (voir 4.6).

### 4.5 Approvisionnement / achats
- Gestion des **fournisseurs**.
- **Commande d'achat** (bon de commande) auprès d'un fournisseur : numéro, date, statut,
  lignes (produit + quantité + **prix d'achat**).
- Pas de « réception » distincte : **à la validation** de la commande, le stock augmente
  directement (mouvements d'entrée) pour chaque ligne.
- **Valorisation du stock : coût moyen pondéré (CMP)**, par (produit × magasin), recalculé
  à chaque validation de commande d'achat. Un produit acheté à des prix différents dans le
  temps est ainsi lissé en une seule valeur de coût de référence.

### 4.6 Gestion de stock (mouvements)
- **Principe fondamental** : toute variation de stock passe par un **mouvement typé et
  immuable**. On ne modifie ni ne supprime jamais un mouvement ; on le corrige par un
  mouvement inverse.
- Types de mouvements :
  - **Entrée** : réception, ajustement d'inventaire positif.
  - **Sortie** : vente, **casse / perte** (motif dédié, fréquent en vaisselle),
    ajustement d'inventaire négatif.
  - **Transfert** : sortie du magasin A + entrée dans le magasin B (mouvement simple,
    sans valorisation).
- **Seuil d'alerte** de rupture par produit.

### 4.7 Inventaire
- Création d'une **fiche d'inventaire** pour un magasin (total ou partiel par catégorie).
- Saisie des **quantités physiquement comptées** par produit.
- Le système calcule automatiquement l'**écart** (compté − théorique) par produit.
- À la **validation**, génération automatique des **mouvements d'ajustement** correspondants.
- Historique des inventaires consultable ; un inventaire validé est figé.

### 4.8 Vente / POS
- Écran caisse type supermarché : recherche produit (scan code-barres ou nom/SKU),
  panier, sélection **pièce ou unité de vente**.
- **Différenciation claire** des produits de même nom : affichage nom + libellé
  distinctif + prix.
- **Stock insuffisant** : le produit (ou l'unité de vente concernée) est affiché **grisé**
  avec la mention « rupture de stock », non sélectionnable dans le panier.
- **Remises** :
  - **par ligne produit** : en **montant** ou en **pourcentage** ;
  - **sur le total** de la vente : en **montant** ou en **pourcentage** ;
  - récapitulatif clair : sous-total, remises appliquées, net à payer.
- **Paiement mixte** : une vente peut être réglée en plusieurs moyens
  (ex. 3000 F espèces + 2000 F Mobile Money).
- **Ventes en attente** :
  - permet de mettre de côté un panier **pas encore finalisé**, le temps de servir un
    autre client déjà prêt à payer, sans mouvement de stock ni encaissement ;
  - une vente en attente peut être **reprise** pour continuer/finaliser le panier
    (ajout de lignes, paiement), au **prix courant** des produits au moment de la
    reprise (pas de figeage des prix à la mise en attente) ;
  - **chaque caissier ne voit que ses propres ventes en attente** ;
  - le **gérant** peut voir et **annuler** les ventes en attente d'une caisse de son magasin ;
  - une vente en attente peut être **reprise** (finalisée) ou **annulée**.
- **Ticket de caisse** imprimé/affiché avec numéro séquentiel.
- **Règle d'atomicité** : une vente décrémente le stock du bon magasin + enregistre le(s)
  paiement(s) + se rattache à la session de caisse, en une seule transaction. Tout ou rien.
- Chaque ligne de vente **fige le coût (CMP)** du produit au moment de la vente, pour un
  rapport de marge fiable et reproductible même si le CMP évolue ensuite.

### 4.9 Gestion des caisses
Trois notions distinctes :
- **Caisse** : entité créée et gérée en admin, rattachée à un magasin. Possède un historique.
- **Session de caisse** : une ouverture précise (caissier, fond de caisse, date/heure).
- **Caissier** : l'utilisateur qui ouvre la session.

Règles :
- Une caisse ne peut avoir **qu'une seule session ouverte à la fois**. Une caisse « libre »
  = sans session ouverte.
- Plusieurs caisses peuvent être ouvertes simultanément dans un magasin, chacune indépendante.

Cycle de vie d'une session :
1. **Ouverture** avec saisie du fond de caisse (sur une caisse libre).
2. **Ventes** (chaque vente encaisse dans la session).
3. **Clôture** : comptage physique des espèces → calcul de l'écart.
4. **Fermeture** de la session.

- **On ne peut ni clôturer ni fermer une session tant qu'il reste des ventes en attente**
  sur la caisse : chaque panier en attente doit d'abord être **finalisé** ou **annulé**.
- Calcul de clôture : `fond de caisse + total espèces encaissées = théorique`, comparé au
  comptage → écart. **Seules les espèces entrent dans le tiroir** ; les autres moyens de
  paiement sont totalisés séparément. Pas de mouvement de caisse hors-vente (appoint,
  prélèvement) dans le MVP.

### 4.10 Moyens de paiement
- **Liste configurable** en admin (activer / désactiver).
- **Espèces** actif par défaut.
- Autres (Mobile Money, carte, virement…) activables selon les besoins.
- Chaque vente peut combiner plusieurs moyens (paiement mixte).

### 4.11 Utilisateurs, rôles et permissions
- Authentification par identifiant / mot de passe.
- **Rôles dynamiques** : l'admin peut **créer un rôle** et lui **attribuer des permissions**
  à la carte (ex. un rôle « Responsable achats » avec `achat.voir`, `achat.creer`,
  `rapport.voir`).
- **Permissions granulaires** nommées selon la convention **`module.action`** :
  - Exemples : `vente.creer`, `achat.creer`, `achat.receptionner`,
    `produit.voir`, `produit.creer`, `produit.modifier`, `stock.ajuster`, `stock.transferer`,
    `inventaire.realiser`, `caisse.ouvrir`, `caisse.cloturer`, `rapport.voir`,
    `utilisateur.gerer`, `role.gerer`, `parametre.gerer`.
- **Superadmin** : accès total contournant toutes les permissions (pas d'attribution nécessaire).
- Un **noyau de permissions système** reste protégé et non attribuable aux rôles créés à la volée.
- Un caissier est rattaché à un magasin.

### 4.12 Rapports
- **Ventes** par période (par magasin et consolidé), par caissier, par moyen de paiement.
- **Marge** (prix de vente − coût appliqué à la vente).
- **Quantité et valeur du stock** (au CMP, par magasin et consolidé).
- **Écarts de caisse** par caissier et par session.
- **Produits sous seuil d'alerte**.
- **Casse / pertes** par période et par magasin.
- **Historique des inventaires** et de leurs écarts.
- Exports (impression / téléchargement) des principaux rapports.

### 4.13 Traçabilité et journaux
- **Journal d'activité** : créations / modifications / suppressions sur les entités
  sensibles (produits, prix, mouvements de stock, ventes, sessions de caisse, utilisateurs,
  rôles), avec auteur, date/heure et valeurs avant/après.
- **Historique de connexion** : login / logout, date/heure, utilisateur, adresse IP.
- **Traçabilité des actions critiques** (annulations, remises importantes, ajustements de
  stock, ouvertures/clôtures de caisse), consultable par le gérant et le superadmin.

---

## 5. Règles de gestion (invariants)

1. **Stock = somme de mouvements.** La quantité disponible n'est jamais écrasée directement.
2. **Un mouvement est immuable.** Toute correction passe par un mouvement inverse.
3. **Une vente est atomique.** Stock + paiements + session en une seule transaction.
4. **Le SKU identifie le produit**, jamais le nom.
5. **Le stock se compte en pièces**, les unités de vente se convertissent en pièces.
6. **Vendre exige une session ouverte sur une caisse libre** (tous rôles).
7. **Une caisse n'a qu'une session ouverte à la fois.**
8. **Pas de clôture/fermeture tant qu'il reste des ventes en attente** sur la caisse.
9. **La clôture se calcule par session**, pas par jour ni par caisse.
10. **Seules les espèces sont comptées dans le tiroir.**
11. **Toute action sensible est tracée** (journal d'activité et historique de connexion).

---

## 6. Ergonomie et interface

- Application de type **ERP moderne, inspiration Odoo** : pensée pour des utilisateurs
  **non techniques**, intuitive.
- Navigation par **sidebar** avec menu organisé par module (Tableau de bord, Ventes,
  Caisses, Stock, Inventaire, Achats, Catalogue, Rapports, Administration).
- **Palette** sobre et professionnelle rappelant un ERP : primaire violet/prune, accents
  turquoise, surfaces claires, gris neutres.
- Formulaires clairs, retours d'action explicites, cohérence visuelle.
- **Tableaux de données** riches et filtrables (recherche, tri, pagination) sur les listes.
- Interface **responsive** confortable sur mobile, tablette et desktop.

---

## 7. Contraintes techniques

- **Backend** : Laravel 13 (PHP).
- **Frontend** : Blade + **Bootstrap** (UI) + **Alpine.js** (réactivité côté client).
- **Packages** :
  - `spatie/laravel-permission` — rôles et permissions dynamiques.
  - `spatie/laravel-medialibrary` — images produits.
  - `spatie/laravel-activitylog` — journaux d'activité.
  - `yajra/laravel-datatables-oracle` — tableaux de données côté serveur.
- **Performance** : cache Laravel (config, routes, vues, données de référence),
  eager loading anti-N+1, pagination systématique, index DB, DataTables serveur.
- **Application web responsive** ; **MVP connecté** (hors-ligne reporté).
- Base de données **transactionnelle (ACID)** pour l'atomicité des ventes.
- Numérotation des tickets par caisse (ex. `M1-C03-000147`).

---

## 8. Décisions actées (simplification MVP)

Toutes les hypothèses de la version précédente sont tranchées :

1. **Prix des unités de vente** = prix **total** de l'unité de vente (confirmé).
2. **Casse** modélisée comme type de mouvement dédié dès le départ (confirmé).
3. **Taxes** : supprimées du MVP, aucune gestion de taux.
4. **Retours / avoirs** : supprimés du MVP, aucun flux de retour pour l'instant.
5. **Cumul des remises** : remise ligne + remise total cumulables, ordre = remises de
   ligne puis remise sur le sous-total, arrondi entier unique appliqué partout (confirmé).
6. **Libellé UI de l'unité de vente** : « **Unité de vente** » (confirmé).
7. **Coût d'achat / CMP / marge** : suivis dès le MVP. Chaque ligne de commande d'achat
   porte un prix d'achat ; le stock est valorisé au **coût moyen pondéré (CMP)** par
   (produit × magasin), recalculé à chaque commande validée. Le CMP est figé sur chaque
   ligne de vente pour un rapport de marge reproductible. Pas de « Réception » séparée :
   la validation de la commande d'achat impacte directement le stock.
8. **Mouvements de caisse hors-vente** (appoint, prélèvement) : supprimés du MVP —
   la clôture se limite à `fond de caisse + ventes espèces`.
9. **Vente en attente** : sert à mettre de côté un panier non finalisé pour servir un
   autre client, puis le reprendre pour continuer/finaliser ; prix courant appliqué à
   la reprise, pas de figeage.
10. **Prix pièce** : champ direct sur la fiche produit (pas une ligne « unité de vente »
    dédiée).
11. **Stock insuffisant** : produit/unité grisé avec mention « rupture de stock »,
    non sélectionnable côté caisse (blocage dans l'UI, pas d'erreur après coup).
12. **Transfert de stock inter-magasin** : reste simple — sortie source + entrée
    destination, sans valorisation.

*Résolus dans une version antérieure : ventes en attente = par caissier (gérant peut
annuler) ; clôture bloquée par les ventes en attente ; vente = caisse libre pour tous
les rôles ; rôles/permissions dynamiques.*

---

## 9. Découpage indicatif en tranches livrables

- **Tranche 1 — Socle & référentiel** : auth, rôles/permissions dynamiques (superadmin,
  gérant, caissier par défaut), magasins, catalogue produits + images + unités de vente,
  moyens de paiement, layout ERP (sidebar), journaux d'activité et connexions.
- **Tranche 2 — Stock & inventaire** : mouvements, réception, casse, transfert, inventaire,
  seuils.
- **Tranche 3 — Caisse & vente** : sessions de caisse, écran POS, remises ligne/total,
  paiement mixte, ventes en attente (règles de clôture), ticket, clôture.
- **Tranche 4 — Achats, dashboard & rapports** : fournisseurs, commandes, tableau de bord,
  rapports.
- **Évolutions** : hors-ligne + synchronisation, facture normalisée FNE.
