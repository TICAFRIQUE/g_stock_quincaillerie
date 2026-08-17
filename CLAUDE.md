# CLAUDE.md

Contexte projet pour agents de code (Claude Code). Ce fichier décrit **quoi construire**
et surtout **les règles à ne jamais violer**. Le détail fonctionnel complet est dans
`CAHIER_DES_CHARGES.md`.

---

## Vue d'ensemble

Application web **ERP (inspiration Odoo)** de **caisse (POS) + gestion de stock** pour un
vendeur de **matériaux et outillage de quincaillerie** exploitant **plusieurs magasins**.
Cible : utilisateurs **non techniques** — interface simple et intuitive.

Clientèle mixte : particuliers (vente comptant) **et** professionnels du bâtiment
(maçons, artisans, entrepreneurs) qui achètent régulièrement **à crédit** sur compte
client, réglé plus tard.

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
5. **Le stock se compte dans l'unité de base du produit** (« pièce » au sens générique :
   une unité physique, mais aussi le mètre, le kilo, le litre… selon comment le produit
   est défini). Une unité de vente a un facteur **entier** ; vendre un lot de N décrémente
   N unités de base. Pas de quantités décimales dans le MVP.
6. **Vendre exige une session ouverte sur une caisse LIBRE** — pour TOUS les rôles, y
   compris gérant, admin et superadmin. Aucune vente hors session.
7. **Une caisse n'a qu'UNE session ouverte à la fois.** « Libre » = sans session ouverte.
8. **Interdit de clôturer OU fermer une session tant qu'il reste des ventes en attente**
   sur la caisse. Chaque panier en attente doit être finalisé ou **annulé** d'abord.
9. **La clôture se calcule par session**, pas par jour.
10. **Seules les espèces entrent dans le comptage du tiroir** — ventes ET règlements
    clients confondus. Autres moyens totalisés à part.
11. **Toute action sensible est tracée** via `activitylog`.
12. **Le solde d'un compte client est dérivé, jamais écrasé.** Même principe que le
    stock (règle 1) : solde = somme des écritures (`EcritureCompteClient`). Jamais
    d'`UPDATE clients SET solde = X`.
13. **Une vente à crédit exige un client identifié.** Paiement partiel ou nul autorisé
    uniquement si un client est rattaché à la vente ; le reste dû devient une dette sur
    son compte. Une vente sans client doit être payée intégralement.
14. **Un règlement client s'effectue dans une session de caisse ouverte**, comme une
    vente — mêmes contraintes d'atomicité et de comptage du tiroir.
15. **Un devis ne mouvemente ni le stock ni la caisse.** Seule sa transformation en
    vente le fait, dans les mêmes conditions qu'une vente normale (règle 6) — avec les
    prix et remises **courants** du catalogue, jamais les montants indicatifs figés sur
    le devis.
16. **Le solde d'un compte fournisseur est dérivé, jamais écrasé.** Même principe que le
    stock (règle 1) et le compte client (règle 12) : solde = somme des écritures
    (`EcritureCompteFournisseur`). Jamais d'`UPDATE fournisseurs SET solde = X`.
17. **Un règlement fournisseur est indépendant de la caisse.** Contrairement au règlement
    client (règle 14), il n'exige aucune session de caisse ouverte et n'entre jamais dans
    le comptage du tiroir (règle 10 reste limitée aux espèces de vente et de règlement
    client) — c'est un flux de trésorerie distinct de la caisse magasin.

---

## Devis

- **Document commercial pré-vente**, à destination d'un **client identifié** (client
  obligatoire, contrairement à la vente comptant).
- **Création libre, hors caisse** : ne nécessite ni caisse ni session ouverte (ne
  mouvemente ni stock ni argent).
- **Lignes de devis** : produit + (pièce/unité de vente) + quantité + remise de ligne ;
  **aucun prix stocké** — montants indicatifs au catalogue courant, comme une ligne de
  vente en attente.
- **Durée de validité** configurable (ex. 30 jours), calculée à la création ; passée,
  statut **expiré** automatique, non transformable (dupliquer un nouveau devis).
- **Statuts** : `brouillon` → `transformé` (la transformation vaut acceptation), ou
  `refusé`/annulé ; `expiré` atteint automatiquement. Pas d'étape « envoyé »/« accepté »
  séparée pour le moment — pourra être réintroduite plus tard si besoin.
- **Transformation en vente** : uniquement depuis `brouillon` non expiré, dans une
  **session de caisse ouverte** (règle 6), avec les prix/remises **courants** du
  catalogue (pas les montants indicatifs du devis). Transformation **totale uniquement**
  (pas ligne par ligne). Le devis transformé est figé et référence la vente créée.
- Un devis non transformé peut être **refusé/annulé** par son auteur ou le gérant.
- **Export** : consultable/imprimable en ligne (mise en page type facture : coordonnées
  de l'entreprise, du client, numéro, date, détail des lignes) et téléchargeable en
  **PDF** et **Excel** — même gabarit dans les trois cas.

---

## Ventes en attente

- Un panier mis de côté : **aucun mouvement de stock, aucun encaissement**.
- **Rattachée au caissier** qui l'a créée ; un caissier ne voit que **ses** ventes en attente.
- Le **gérant** peut voir et **annuler** les ventes en attente d'une caisse de son magasin.
- Actions : reprendre (finaliser) ou annuler.
- Bloque la clôture/fermeture de la session tant qu'elle existe (règle 8).

---

## Vente à crédit et comptes clients

- **Client** : fiche référentielle centrale (nom, téléphone, adresse, **limite de
  crédit** optionnelle, actif) — pas rattachée à un magasin, comme le catalogue produit.
- **Vente comptant** (client anonyme ou renseigné) : payée intégralement à l'encaissement,
  comme aujourd'hui.
- **Vente à crédit** : nécessite un client identifié ; le total encaissé (paiement mixte
  possible) peut être **inférieur** au net à payer. Le reste dû est porté au compte du
  client (règle 13).
- **Solde client dérivé** (règle 12) : jamais stocké/écrasé directement, calculé comme la
  somme des `EcritureCompteClient` (+dette à chaque vente à crédit, −dette à chaque
  règlement) — même logique que le stock (`MouvementStock`).
- **Limite de crédit** : si définie (> 0) et que la vente ferait dépasser le solde du
  client au-delà de cette limite, la vente à crédit est **bloquée**, sauf pour un
  utilisateur disposant de la permission `client.depasser_limite`. Limite vide/0 =
  illimitée.
- **Règlement client** : encaissement ultérieur d'une dette (partiel ou total), dans une
  **session de caisse ouverte** (règle 14), un ou plusieurs moyens de paiement. Immuable,
  comme une vente ; alimente le même tiroir (règle 10).
- Le ticket d'une vente à crédit affiche le montant réglé et le **solde restant dû**.

---

## Achat à crédit et comptes fournisseurs

- **Fournisseur** : fiche référentielle (nom, téléphone, e-mail, adresse, actif),
  symétrique du `Client` mais sans notion de limite de crédit.
- **Chaque ligne d'achat précise sa destination** (magasin ou dépôt, voir « Dépôts »
  ci-dessous) : une même commande d'achat peut livrer plusieurs sites en une fois. Le
  magasin porté par l'en-tête de la commande reste le magasin gestionnaire (qui passe la
  commande), pas nécessairement l'unique destinataire du stock.
- **Taxe par ligne d'achat** : optionnelle, choisie parmi un référentiel `Taxe`
  préenregistré (nom + taux %). `prix_achat` sur une ligne est **HT** ; le **TTC** est
  dérivé (jamais stocké), calculé et affiché par ligne et en récapitulatif de commande
  (total HT, total TTC) — voir règle d'arrondi unique.
- **Règlement à la validation** : à la validation d'une commande d'achat, un ou plusieurs
  paiements immédiats (`PaiementAchat`, paiement mixte comme pour une vente) peuvent être
  saisis ; le reste (total TTC − paiements) devient une dette portée au compte du
  fournisseur (règle 16). Aucun paiement saisi = dette intégrale.
- **Solde fournisseur dérivé** (règle 16) : somme des `EcritureCompteFournisseur`
  (+dette à chaque achat à crédit, −dette à chaque règlement) — même logique que le
  compte client.
- **Règlement fournisseur** : encaissement ultérieur d'une dette (partiel ou total, un ou
  plusieurs moyens de paiement), **indépendant de toute session de caisse** (règle 17,
  contrairement au règlement client). Immuable, comme un règlement client.
- Pas de limite de crédit fournisseur ni de blocage associé dans le MVP.

---

## Dépôts

- Un **dépôt** est un lieu de stockage sans vente : même entité `Magasin`, différenciée
  par un champ `type` (`magasin` | `depot`).
- Un dépôt ne peut **jamais** avoir de caisse ni de session — la création d'une caisse ne
  propose que les magasins de type `magasin`.
- Partout ailleurs (stock, mouvements, transferts, destination de ligne d'achat, devis,
  inventaire, rapports), un dépôt se comporte comme un magasin normal : il détient du
  stock et peut être source/destination d'un transfert.

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
- **Pas de taxes côté vente** dans le MVP (aucun taux, aucune ligne taxe sur le ticket).
  Les taxes existent **uniquement côté achat** (voir « Achat à crédit et comptes
  fournisseurs ») : `prix_achat` = HT, TTC dérivé et arrondi avec la même règle unique.
- Clôture de session = `fond de caisse + total ventes espèces + total règlements clients
  espèces` (pas de mouvement de caisse hors-vente/hors-règlement au MVP ; un règlement
  fournisseur n'entre jamais dans ce calcul, voir règle 17).

---

## Rôles et permissions (dynamiques, spatie/laravel-permission)

- **Rôles créés à la volée** par l'admin, avec attribution de permissions à la carte.
  Ne pas coder les autorisations en dur ; toujours vérifier via permission.
- **Convention de nommage : `module.action`** — ex. `vente.creer`, `vente.credit`,
  `devis.creer`, `devis.transformer`, `achat.creer`, `achat.receptionner`, `produit.voir`,
  `produit.modifier`, `stock.ajuster`, `stock.transferer`, `inventaire.realiser`,
  `caisse.ouvrir`, `caisse.cloturer`, `client.gerer`, `client.reglement`,
  `client.depasser_limite`, `fournisseur.voir`, `fournisseur.gerer`,
  `fournisseur.reglement`, `taxe.gerer`, `typeclient.gerer`, `rapport.voir`,
  `utilisateur.gerer`, `role.gerer`, `parametre.gerer`.
- **Superadmin** : bypass total via `Gate::before` (ne reçoit pas de permissions).
- **Noyau de permissions système protégé** : non attribuable aux rôles créés à la volée.
- Rôles par défaut seedés : Superadmin, Gérant, Caissier — **modifiables**.

---

## Modèle de domaine (entités clés)

- **Magasin** : point de vente ou dépôt de stockage, distingués par un champ `type`
  (`magasin` | `depot`, voir « Dépôts »). Un dépôt n'a jamais de caisse.
- **Produit** : SKU (unique), nom, libellé distinctif, catégorie, **prix pièce** (prix de
  vente unitaire de l'unité de base — pas nécessairement une pièce physique : peut être le
  mètre, le kilo, le litre… selon le produit), seuil d'alerte, image (medialibrary).
- **UniteVente** (optionnelle, plusieurs par produit) : rattachée à un produit ; facteur
  entier (multiple de l'unité de base) + prix total propre. Libellé UI retenu :
  « **Unité de vente** » (ex. rouleau de 50 m pour un câble dont l'unité de base = 1 m,
  sac de 25 kg pour un produit dont l'unité de base = 1 kg, carton de 12 pour des vis à
  la pièce). L'unité de base elle-même n'est pas une ligne `UniteVente` : c'est le
  `prix_piece` du produit.
- **Stock** : quantité par (produit × magasin), *dérivée des mouvements* ; porte aussi le
  **coût moyen pondéré (CMP)**, recalculé à chaque entrée d'achat.
- **MouvementStock** : immuable ; type = réception | vente | casse | transfert | ajustement ;
  quantité en pièces ; magasin ; référence source.
- **Inventaire** : fiche par magasin (date, statut brouillon/validé).
- **LigneInventaire** : produit + quantité comptée + écart calculé.
- **Fournisseur** : nom, téléphone, e-mail, adresse, actif. Référentiel central, comme
  `Client`.
- **CommandeAchat** : pas d'entité « Réception » séparée — un numéro, un fournisseur, un
  magasin gestionnaire, une date, des lignes ; à sa **validation**, le stock est
  directement impacté (mouvements d'entrée, un par ligne vers sa propre destination) et
  le CMP recalculé. Un produit peut être acheté à des prix différents dans le temps : le
  CMP lisse ça en une seule valeur de référence par (produit × magasin).
- **LigneCommandeAchat** : produit + unité d'achat (pièce ou `UniteVente`, même
  référentiel qu'à la vente) + quantité + **prix d'achat HT** + **taxe** optionnelle
  (`Taxe`) + **destination** (`Magasin`, magasin ou dépôt) ; TTC dérivé, jamais stocké.
- **Taxe** : nom + taux (%) + actif. Référentiel préenregistré, utilisé uniquement côté
  achat (voir « Argent et arrondis »).
- **PaiementAchat** : rattaché à une commande d'achat ; moyen + montant (paiement mixte
  possible), saisi à la validation. Le reste (total TTC − paiements) devient une dette
  fournisseur (voir « Achat à crédit et comptes fournisseurs »).
- **Caisse** : rattachée à un magasin ; au plus une session ouverte à la fois.
- **SessionCaisse** : caissier + fond de caisse + ouverture/clôture + écart. Pas de
  mouvement de caisse hors-vente (appoint/prélèvement) au MVP ; clôture = fond de caisse +
  total ventes espèces.
- **Vente** : numéro (`M{magasin}-C{caisse}-{séquence}`), lignes, remise total, session,
  magasin, **client** (nullable — obligatoire si la vente est à crédit).
- **LigneVente** : produit + (pièce **ou** unité de vente) + quantité + prix appliqué +
  remise de ligne + **coût appliqué** (CMP figé au moment de la vente, pour un rapport de
  marge reproductible dans le temps).
- **Paiement** : rattaché à une vente ; moyen + montant (plusieurs = paiement mixte). Une
  vente à crédit peut avoir un total de paiements **inférieur** au net à payer.
- **MoyenPaiement** : configurable, actif/inactif ; espèces par défaut.
- **VenteEnAttente** : panier non finalisé + caissier propriétaire ; sans mouvement ni
  paiement ; reprise = continuation du panier au **prix courant** (pas de figeage de prix).
- **Client** : nom, **type de client** (`TypeClient`, optionnel), téléphone, adresse,
  **limite de crédit** (nullable = illimitée), actif. Référentiel central, comme le
  catalogue.
- **TypeClient** : nom + actif. Référentiel libre (ex. particulier, maçon, entrepreneur),
  associé à un client pour catégorisation/reporting — aucune logique métier attachée dans
  le MVP.
- **EcritureCompteClient** : immuable ; type = vente_credit | reglement ; montant signé ;
  client ; référence source (`Vente` ou `ReglementClient`) ; auteur. Le solde du client
  est la somme de ses écritures (règle 12).
- **ReglementClient** : encaissement d'une dette client ; client + montant + moyen de
  paiement + session de caisse + caissier ; immuable, comme une vente (règle 14).
- **EcritureCompteFournisseur** : immuable ; type = achat_credit | reglement ; montant
  signé ; fournisseur ; référence source (`CommandeAchat` ou `ReglementFournisseur`) ;
  auteur. Le solde du fournisseur est la somme de ses écritures (règle 16).
- **ReglementFournisseur** : encaissement d'une dette fournisseur ; fournisseur + montant
  + auteur ; **pas** de session de caisse (règle 17, contrairement à `ReglementClient`).
  Immuable.
- **ReglementFournisseurPaiement** : rattaché à un règlement fournisseur ; moyen + montant
  (paiement mixte possible).
- **Devis** : client (obligatoire), magasin, auteur, statut (brouillon | refusé |
  transformé | expiré), remise totale, date de validité, référence vers la `Vente` une
  fois transformé. Modifiable tant que non transformé/expiré.
- **LigneDevis** : produit + (pièce ou unité de vente) + quantité + remise de ligne ;
  **aucun prix stocké** (indicatif au catalogue courant, comme `LigneVenteEnAttente`).
- **Utilisateur** : rôle(s), magasin de rattachement.

---

## Interface (ERP type Odoo)

- **Layout à menu horizontal sur desktop** : une seule barre en haut de page ; sections
  regroupées en menus déroulants (Ventes, Stock, Catalogue, Rapports, Administration).
  Sur mobile/tablette (< lg), le même menu s'ouvre en **sidebar coulissante** (offcanvas)
  plutôt que replié en ligne sous la barre — plus confortable à parcourir sur petit
  écran ; mêmes regroupements/permissions, seule la présentation change selon la taille
  d'écran.
- **Dashboard adapté au rôle** : caissier → sa session ; gérant → son magasin (CA, panier
  moyen, top produits, stock sous seuil, écarts de caisse, **total des créances clients**,
  clients en dépassement de limite) ; superadmin → consolidé multi-magasin. Graphiques
  (ventes dans le temps, moyens de paiement, top produits).
- Palette ERP quincaillerie : primaire **orange chantier**, accents mélangés
  (bleu industriel, jaune sécurité, gris acier), surfaces claires chaudes.
  Définie dans `resources/sass/app.scss` (variables Bootstrap `$primary`
  et suivantes) — toujours modifier la palette à cet endroit, jamais de
  couleur codée en dur dans une vue.
- Listes en **DataTables serveur** (jamais charger toute une table en mémoire).
- Feedback explicite (toasts), sentence case, responsive (menu repliable sur mobile).
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
  utilisateurs, rôles, **clients, limites de crédit, règlements, devis, fournisseurs,
  règlements fournisseurs, taxes**) : auteur, date, avant/après.
- **Historique connexions** : login/logout, date, utilisateur, IP (écouter les événements d'auth).

---

## Conventions

- Terminologie métier en **français** (magasin, caisse, mouvement, unité de vente,
  client, règlement…).
- Écritures stock/vente/**compte client**/**compte fournisseur** **uniquement via une
  couche service** encapsulant la transaction ; pas d'écriture directe depuis les
  contrôleurs.
- Rapports = agrégats/vues, sans dupliquer la logique métier.

---

## Hors périmètre (ne pas implémenter dans le MVP)

- Hors-ligne, base locale, synchronisation.
- Facture normalisée FNE — mais **structurer la vente** pour la brancher plus tard.
- Fidélité, promotions avancées, comptabilité, paie.
- **Taxes côté vente** — aucun taux, aucune ligne taxe sur le ticket (les taxes existent
  côté achat, voir « Achat à crédit et comptes fournisseurs »).
- **Retours et avoirs clients** — aucun flux de retour (distinct de la vente à crédit :
  un client à crédit doit une somme d'argent, il ne rapporte pas de marchandise).
- **Mouvements de caisse hors-vente** (appoint, prélèvement) — un règlement fournisseur
  n'y fait pas exception : il est volontairement tenu hors caisse (règle 17), pas traité
  comme un mouvement de tiroir.
- **Limite de crédit fournisseur** — pas de blocage, contrairement au client.
- Entité « Réception » séparée d'une commande d'achat — la validation d'une commande
  d'achat impacte le stock directement (voir modèle de domaine).
- **Transformation partielle d'un devis** — un devis se transforme en bloc, pas ligne
  par ligne.

---

## Décisions actées (voir CAHIER_DES_CHARGES §8 pour le détail)

Toutes les hypothèses initiales sont tranchées : prix des unités de vente = prix total ;
casse = mouvement dédié ; remises cumulables (ligne puis sous-total) ; libellé UI
« Unité de vente » ; prix pièce = champ direct sur le produit (unité de base générique,
pas forcément une pièce physique) ; vente en attente reprise au prix courant ; transfert
de stock inter-magasin simple (sortie + entrée, sans valorisation) ; achat = entrée de
stock directe à la validation (pas de « Réception » séparée), coût suivi via **CMP**
(recalculé à chaque achat, figé sur chaque ligne de vente pour la marge) ; **vente à
crédit** sur compte client, solde dérivé d'écritures immuables, limite de crédit
optionnelle bloquante, règlement encaissé en session de caisse ; **devis** hors caisse à
montants indicatifs, transformable en vente (totalité, prix courants) pendant sa durée
de validité ; taxes vente, retours et mouvements de caisse hors-vente exclus du MVP (voir
« Hors périmètre » ci-dessus).

Extension post-MVP (retours utilisateur) : **dépôts** ajoutés au référentiel magasin
(champ `type`, sans caisse) ; **taxes** introduites côté achat uniquement (prix d'achat
HT, TTC dérivé par ligne, jamais côté vente) ; **destination par ligne d'achat**
(magasin ou dépôt, une commande peut livrer plusieurs sites) ; **compte fournisseur**
symétrique du compte client (solde dérivé, règlement encaissé à la validation de l'achat
ou ultérieurement) mais **volontairement hors caisse** — aucune session requise, aucun
impact sur le tiroir ; **types de client** en référentiel libre, sans logique métier
attachée ; **menu horizontal** en remplacement de la sidebar.
