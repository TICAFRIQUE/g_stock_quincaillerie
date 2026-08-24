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
9. **La clôture se calcule par session**, pas par jour — une session peut donc légitimement
   s'étendre sur plusieurs jours calendaires, jamais bloquée pour cette seule raison. Des
   rappels non bloquants existent néanmoins pour éviter qu'elle traîne par oubli, jamais un
   blocage de la vente (`SessionCaisse::estOuverteDepuisJourPrecedent()`, mémorisé par jour
   via `localStorage`) :
   - Tableau de bord **caissier** (`x-alerte-session-ancienne`) : uniquement **sa propre**
     session, si ouverte depuis un jour précédent.
   - Tableau de bord **gérant/superadmin** (`x-alerte-sessions-anciennes`,
     `DashboardController::donneesMagasin()`) : **toute** session du périmètre (magasin
     pour un gérant, tous magasins pour le superadmin) encore ouverte depuis un jour
     précédent, pas seulement celle de l'utilisateur connecté — un gérant/superadmin a
     l'autorité (`caisse.gerer`) pour clôturer une session qu'il n'a pas ouverte lui-même.
   - En complément, côté gérant/superadmin uniquement : une notification in-app après 12h
     sans clôture (commande planifiée horaire `app:alerter-sessions-ouvertes-trop-longtemps`,
     une seule fois par session, indépendante du jour calendaire).
10. **Seules les espèces entrent dans le comptage du tiroir** — ventes, règlements clients
    ET mouvements de caisse manuels (entrée/sortie, voir règle 19) confondus. Autres
    moyens totalisés à part.
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
17. **Un règlement fournisseur est indépendant de toute caisse de caissier, y compris
    pour la part payée en espèces.** Contrairement au règlement client (règle 14), il
    n'exige donc jamais de session de caisse ouverte et n'entre jamais dans le comptage
    du tiroir d'un caissier. La part payée en espèces sort directement de la **Caisse
    Générale** (règle 21, voir Trésorerie) — jamais du tiroir d'un vendeur.
18. **Un retour (client ou fournisseur) crédite toujours un avoir sur le compte
    concerné, jamais un remboursement en espèces.** Il est obligatoirement rattaché à
    une vente/commande d'achat précise, ligne par ligne, dans la limite du reste
    retournable de chaque ligne ; il remet le stock retourné directement en stock
    vendable (règle 1, via un mouvement immuable dédié) sans recalcul du CMP ; il
    n'exige aucune session de caisse et n'entre jamais dans le comptage du tiroir
    (même logique que le règlement fournisseur, règle 17). Comme un mouvement de stock
    ou une écriture de compte, un retour est immuable : une correction se fait par une
    nouvelle vente/un nouvel achat, jamais par l'annulation d'un retour.
19. **Un mouvement de caisse manuel (entrée/sortie) exige une session de caisse
    ouverte**, comme une vente (règle 6). Une sortie ne peut jamais dépasser le solde
    théorique du tiroir de cette session (fond de caisse + espèces déjà entrées − déjà
    sorties). Immuable comme tout mouvement (règle 2) : correction = mouvement inverse,
    jamais modification ni suppression.
20. **Un avoir (client ou fournisseur) peut être remboursé**, via une action distincte et
    postérieure au retour qui l'a créé (« remboursement d'avoir ») — ceci **n'est pas une
    exception à la règle 18** : le retour lui-même ne mouvemente toujours jamais la
    caisse, seul ce remboursement, explicitement déclenché plus tard, le fait. Plafonné à
    l'avoir disponible (jamais plus que le solde négatif actuel du compte). Immuable,
    comme un règlement. La part payée en espèces suit la règle 21 (Caisse Générale,
    aucune session de caisse de caissier requise) — en **sortie** pour un client (on lui
    rend son argent) et en **entrée** pour un fournisseur (il nous rembourse).
21. **La Caisse Générale est un compte de trésorerie permanent, indépendant des caisses
    des caissiers** (règle 6-19) : pas de session, pas de caissier assigné, solde dérivé
    d'écritures immuables comme un compte client/fournisseur (règle 12/16). Elle reçoit
    **automatiquement** le montant compté de chaque session de caissier clôturée (règle
    9), et alimente la part espèces d'un règlement fournisseur (règle 17) ou d'un
    remboursement d'avoir (règle 20). Un **Compte** (banque/autre) suit le même principe
    et se remplit par virement depuis la Caisse Générale.

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
- **Avoir client** : un solde négatif (après un ou plusieurs retours, règle 18) se déduit
  **automatiquement** de la dette d'une prochaine vente à crédit — le solde n'est qu'une
  somme d'écritures (règle 12), aucune logique spécifique requise. Affiché au caissier au
  moment de choisir le client sur l'écran de vente, pour qu'il en tienne compte.
- **`Vente.avoir_applique`** : part de l'avoir déjà disponible sur le compte qui a couvert
  le solde à crédit posé par cette vente, figée à la création (comme `cout_applique` sur
  une `LigneVente`) — jamais recalculée après coup. Le « reste dû » affiché partout
  (ticket, historique client/session, export, bouton « Régler ») est **`soldeDuReel()`**
  (`soldeDu() - avoir_applique`, jamais 0 négatif), pas `soldeDu()` brut : sans ça, une
  facture intégralement compensée par un avoir préexistant continuait d'afficher un reste
  dû alors que le compte du client était déjà revenu à 0.
- **Remboursement d'un avoir client** (règle 20) : action séparée, à l'initiative du
  gérant/caissier habilité (`client.reglement`), qui rend effectivement l'argent au
  client — un ou plusieurs moyens de paiement, plafonné à l'avoir disponible. La part en
  espèces exige une session de caisse ouverte et génère une **sortie** de caisse liée
  (voir « Mouvements de caisse »). Immuable.

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
- **Règlement fournisseur** : encaissement ultérieur d'une dette, un ou plusieurs moyens de
  paiement, **jamais rattaché à une session de caisse de caissier** (règle 17, contrairement
  au règlement client) — la part payée en espèces sort directement de la Caisse Générale
  (voir « Trésorerie »), sans aucune session requise. Immuable, comme un règlement client.
  Deux modes, réservés chacun à un usage précis pour ne jamais laisser un règlement "en
  l'air" :
  - **Ciblé sur un bon d'achat précis** (bouton dédié depuis la fiche fournisseur ou le
    détail du bon d'achat) : **partiel ou total autorisé**, plafonné au reste dû de CE bon
    d'achat (`CommandeAchat::resteDu()`, indépendant de la dette totale du fournisseur qui
    peut inclure d'autres bons d'achat).
  - **Global** (bouton général de la fiche fournisseur, sans bon d'achat ciblé) :
    **intégral uniquement** — doit couvrir exactement le solde actuel du compte, jamais un
    montant partiel. `ReglementFournisseurService::reglerIntegralite()` répartit alors
    automatiquement ce montant en une `ReglementFournisseur` **par bon d'achat encore dû**,
    le plus ancien d'abord (répartition "en cascade", entière, sans arrondi) — jamais un
    règlement non imputé à un bon d'achat précis. Sans cette répartition, le solde du
    compte diminuait bien après un règlement global, mais le "reste dû" affiché sur chaque
    bon d'achat ne bougeait jamais, ce qui semblait un bug alors que c'était juste une
    dette non imputée.
- Pas de limite de crédit fournisseur ni de blocage associé dans le MVP.
- **Avoir fournisseur** : symétrique de l'avoir client (voir « Vente à crédit et comptes
  clients ») — un solde négatif se déduit automatiquement de la dette d'un prochain achat
  à crédit, affiché au gestionnaire au moment de choisir le fournisseur sur l'écran de
  saisie d'une commande d'achat.
- **Remboursement d'un avoir fournisseur** (règle 20) : symétrique du remboursement d'un
  avoir client, mais c'est le fournisseur qui rembourse — la part en espèces génère une
  **entrée** de caisse liée (et non une sortie), le reste identique (session ouverte pour
  cette part, plafonné à l'avoir disponible, immuable, permission `fournisseur.reglement`).

---

## Retours (client et fournisseur)

- **Toujours rattaché à un document précis** : un retour client référence une `Vente`
  et des `LigneVente` précises ; un retour fournisseur référence une `CommandeAchat`
  **validée** et des `LigneCommandeAchat` précises. Jamais un avoir libre, non rattaché
  à un document (règle 18).
- **Ligne par ligne, avec plafond** : la quantité retournée sur une ligne ne peut jamais
  dépasser son reste retournable (quantité vendue/achetée moins déjà retournée sur cette
  ligne). Un même document peut faire l'objet de plusieurs retours partiels successifs.
- **Aucun remboursement en espèces** : un retour crédite un avoir sur le compte client
  (`EcritureCompteClient`, type `retour_client`) ou fournisseur
  (`EcritureCompteFournisseur`, type `retour_fournisseur`), qui peut faire passer le
  solde sous 0 (avoir utilisable sur un futur achat) — contrairement à un règlement, ce
  crédit n'est **jamais plafonné** par la dette actuelle.
- **Stock remis directement en stock vendable**, sans zone d'inspection séparée : le
  mouvement (type `retour_client` ou `retour_fournisseur`, immuable comme tout
  `MouvementStock`, règle 2) porte sur le magasin/dépôt d'origine de la ligne
  (`magasin_source_id` de la `LigneVente`, `magasin_destination_id` de la
  `LigneCommandeAchat`). Un retour fournisseur peut échouer (stock insuffisant) si la
  marchandise reçue a déjà été revendue ou transférée ailleurs.
- **CMP jamais recalculé** par un retour, comme tout mouvement hors réception. Le coût
  historique d'un retour client reprend `cout_applique` de la `LigneVente` d'origine
  (CMP figé au moment de la vente).
- **Vente sans client identifié (comptant anonyme)** : retour bloqué — un retour exige
  un compte à créditer.
- **Immuable** (règle 18) : pas d'édition ni de suppression applicative d'un retour déjà
  enregistré. Corriger un retour erroné se fait par une nouvelle vente/un nouvel achat,
  jamais par son annulation.
- Un document (`Vente`/`CommandeAchat`) ayant déjà fait l'objet d'un retour partiel ne
  peut plus être annulé en totalité (l'annulation totale restituerait une seconde fois
  le stock déjà repris par le retour) — utiliser un nouveau retour pour le reste, ou
  laisser le document tel quel.

---

## Mouvements de caisse (entrée/sortie)

- **Mécanisme générique** (`MouvementCaisse`, type `entree` | `sortie`) : un montant, un
  motif (libre, obligatoire), une session de caisse, un auteur — utilisable pour
  n'importe quelle raison (appoint, prélèvement, dépense diverse, paiement fournisseur en
  espèces…). Immuable comme tout mouvement (règle 2/19) : correction = mouvement inverse.
- **Session de caisse ouverte obligatoire** (règle 19), comme une vente. Le montant
  impacte directement le solde théorique du tiroir de cette session, en temps réel — pas
  seulement au moment de la clôture.
- **Une sortie ne peut jamais dépasser le solde théorique du tiroir** (fond de caisse +
  ventes espèces + règlements clients espèces + entrées − sorties déjà enregistrées) :
  échoue sinon (`SoldeCaisseInsuffisantException`), on ne peut pas sortir plus d'argent
  qu'il n'y en a physiquement dans le tiroir.
- **Règlement fournisseur en espèces → sortie de caisse automatique liée** (exception à la
  règle 17) : la part du règlement payée en espèces génère une sortie de caisse
  (`reference` = le `ReglementFournisseur`), avec un motif généré automatiquement. Un
  règlement 100% non-espèces reste totalement indépendant de la caisse, comme avant.
- **Clôture de session étendue** (règle 10) : théorique = fond de caisse + ventes espèces
  + règlements clients espèces + entrées de caisse − sorties de caisse. Calcul centralisé
  dans une seule méthode (`CaisseSessionService::calculerTheorique()`), réutilisée par la
  clôture, son aperçu, et le solde théorique temps réel — jamais recalculée deux fois.
- **Qui peut enregistrer un mouvement** : Caissier (sur sa propre session ouverte) et
  Gérant/Superadmin, via la permission `caisse.mouvement`.
- **Saisie directement sur l'écran de session** (`/sessions/{session}`, pas d'onglet
  séparé) : `sessions.index`/`sessions.show` couvraient déjà la liste des caisses et le
  détail d'une session (CA, décomposition, répartition, clôture) — un onglet « Mes
  caisses » distinct ne faisait plus que dupliquer cet écran pour n'y ajouter que le
  formulaire entrée/sortie et le solde théorique temps réel ; les deux ont été fusionnés
  dans `sessions/show.blade.php` pour ne pas faire cohabiter deux écrans qui montrent
  presque la même chose. Pour une session **ouverte** : KPI « Solde théorique du
  tiroir » (calculé en direct via `CaisseSessionService::calculerTheorique()`),
  formulaire entrée/sortie (visible si `caisse.mouvement`, réservé au propriétaire de la
  session ou à un titulaire de `caisse.gerer` — `SessionCaisseController::storeMouvement()`/
  `assurerProprietaireOuGerant()`), et l'historique des mouvements manuels de cette
  session. Une session **clôturée** garde son bloc « Clôture » existant (entrées/sorties
  y figurent déjà) ; le formulaire disparaît puisqu'aucun mouvement n'est plus possible.
- **Historique complet** : rapport dédié (`rapports.mouvements-caisse`, sous `rapport.voir`,
  comme les autres rapports) — tous les mouvements toutes sessions/périodes confondues,
  filtrable par date et magasin, exportable PDF/Excel.
- **Une vente encaissée en espèces apparaît dans ce même historique**, avec le libellé
  « Vente / Facture » — mais **une vente n'est jamais un `MouvementCaisse`** : elle est
  déjà comptabilisée via ses propres `Paiement` (voir « Clôture de session étendue »
  ci-dessus). Ce n'est qu'un assemblage en lecture seule pour l'affichage (mêmes filtres,
  mêmes exports), qui ne duplique jamais le calcul du solde théorique. Seule la part
  encaissée en espèces d'une vente y apparaît (règle 10) ; une vente annulée n'y apparaît
  pas.

---

## Trésorerie (Caisse Générale et Comptes)

- **`CompteTresorerie`** : compte de trésorerie de l'entreprise, `type` = `caisse_generale`
  | `banque` | `autre`. Un seul enregistrement `caisse_generale` existe (singleton créé
  par un seeder au déploiement, jamais créable/supprimable/renommable depuis l'UI) ;
  les comptes `banque`/`autre` se créent librement (nom + type). Solde **dérivé**, jamais
  écrasé : somme des `EcritureCompteTresorerie` (règle 21), exactement comme un compte
  client/fournisseur — aucune notion de session, de caissier assigné, ni d'ouverture/
  fermeture.
- **Volontairement indépendant des caisses de vente des caissiers** (`Caisse`/
  `SessionCaisse`/`MouvementCaisse`, voir « Mouvements de caisse » ci-dessus) : les deux
  univers ne se mélangent jamais comptablement, même s'ils apparaissent côte à côte dans
  l'écran « Trésorerie » (menu horizontal, entrée dédiée — voir « Interface »).
- **Dépôt automatique à la clôture d'une session** : dès qu'une session de caissier est
  clôturée (règle 9), le montant **compté** (pas le théorique) génère une écriture
  `depot_session_cloturee` sur la Caisse Générale — modélise le geste physique de vider
  le tiroir dans le coffre. Aucune étape manuelle à faire, aucun oubli possible.
  `CaisseSessionService::cloturer()` reste la seule méthode qui déclenche ce dépôt, dans
  la même transaction que la clôture.
- **Sorties/entrées manuelles** : motif (obligatoire) + montant, comme un mouvement de
  caisse manuel (règle 19) mais sur un `CompteTresorerie` au lieu d'une session — une
  sortie ne peut jamais dépasser le solde actuel du compte
  (`SoldeTresorerieInsuffisantException` sinon).
- **Virement interne** entre deux comptes de trésorerie (ex. Caisse Générale → compte
  bancaire) : une sortie sur la source et une entrée sur la destination dans la même
  transaction, sans valorisation supplémentaire — même principe qu'un transfert de stock
  inter-magasin.
- **Alimente automatiquement la part espèces d'un règlement fournisseur** (règle 17) et
  **d'un remboursement d'avoir client/fournisseur** (règle 20) : sortie pour un
  règlement fournisseur ou un remboursement d'avoir client (l'argent sort), entrée pour
  un remboursement d'avoir fournisseur (il nous rembourse) — aucune session de caisse de
  caissier requise pour ces trois flux, contrairement à un règlement **client** (règle 14)
  qui reste, lui, toujours rattaché à la session du caissier qui l'encaisse.
- **Un `Compte` n'a aucun lien avec les `MoyenPaiement`** de la vente (Espèces, Mobile
  Money, Carte…) : c'est une poche de trésorerie interne, jamais une destination
  automatique d'un encaissement à la vente.
- **Permissions dédiées** `tresorerie.voir`/`tresorerie.gerer`, distinctes de
  `caisse.mouvement`/`caisse.gerer` — accordées au Gérant et au Superadmin, jamais au
  Caissier (« la Caisse Générale n'a rien à voir avec les caisses des caissiers »).
- **Rapport dédié** (`rapports.tresorerie`) : tous les mouvements de trésorerie, filtrable
  par compte/type/période, exportable PDF/Excel — **séparé** de
  `rapports.mouvements-caisse` qui reste scopé aux tiroirs des caissiers.
- **Écran « Trésorerie » (`comptabilite.caisses.index`, menu horizontal, entrée dédiée)** :
  répertoire listant la Caisse Générale, les Comptes, et **toutes** les caisses de vente
  (pas seulement celles actuellement ouvertes — un répertoire de trésorerie, pas l'écran
  opérationnel du jour, voir `/sessions`). Cliquer sur une caisse de vente ouvre un
  **rapport dédié à cette caisse** (`comptabilite.caisses-vente.show`), toutes sessions
  confondues (contrairement à `/sessions/{session}`, scopé à une session précise) : mêmes
  KPI et même journal (ventes + mouvements manuels) que `rapports.mouvements-caisse`,
  juste pré-filtré sur cette caisse, avec export PDF/Excel qui réutilise ces mêmes routes
  (`caisse_id` forcé). Nommé "Comptabilité" en interne (routes, contrôleur) mais jamais
  affiché ainsi à l'écran, pour ne pas laisser croire à une comptabilité générale (hors
  périmètre, voir « Hors périmètre »).

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
  espèces + total entrées de caisse − total sorties de caisse` (voir « Mouvements de
  caisse » et règle 19 ; un règlement fournisseur n'entre dans ce calcul que pour sa part
  payée en espèces, via la sortie de caisse liée qu'elle génère — voir règle 17).

---

## Rôles et permissions (dynamiques, spatie/laravel-permission)

- **Rôles créés à la volée** par l'admin, avec attribution de permissions à la carte.
  Ne pas coder les autorisations en dur ; toujours vérifier via permission.
- **Convention de nommage : `module.action`** — ex. `vente.creer`, `vente.credit`,
  `vente.retour`, `devis.creer`, `devis.transformer`, `achat.creer`,
  `achat.receptionner`, `achat.retour`, `produit.voir`, `produit.modifier`,
  `stock.ajuster`, `stock.transferer`, `inventaire.realiser`, `caisse.ouvrir`,
  `caisse.cloturer`, `caisse.mouvement`, `tresorerie.voir`, `tresorerie.gerer`,
  `client.gerer`, `client.reglement`, `client.depasser_limite`, `fournisseur.voir`,
  `fournisseur.gerer`, `fournisseur.reglement`, `taxe.gerer`, `typeclient.gerer`,
  `rapport.voir`, `utilisateur.gerer`, `role.gerer`, `parametre.gerer`.
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
- **MouvementStock** : immuable ; type = réception | vente | casse | transfert |
  ajustement | annulation | retour_client | retour_fournisseur ; quantité en pièces ;
  magasin ; référence source.
- **Inventaire** : fiche par magasin (date, statut brouillon/validé).
- **LigneInventaire** : produit + quantité comptée + écart calculé.
- **Fournisseur** : nom, **code** (identifiant type `FRN-000001`, saisi ou généré
  automatiquement, comme le SKU produit), téléphone, e-mail, adresse, actif. Référentiel
  central, comme `Client`.
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
- **SessionCaisse** : caissier + fond de caisse + ouverture/clôture + écart ; totaux
  espèces stockés à la clôture (ventes, règlements clients, entrées et sorties de caisse
  — voir « Mouvements de caisse »).
- **MouvementCaisse** : immuable (règle 19) ; type = entree | sortie ; montant (toujours
  positif, direction portée par `type`) ; motif (obligatoire) ; session de caisse ;
  auteur. Réservé aux mouvements manuels d'un caissier sur sa propre session — la part
  espèces d'un règlement fournisseur ou d'un remboursement d'avoir passe désormais par
  `EcritureCompteTresorerie` (Caisse Générale, règle 21), jamais par ici.
- **CompteTresorerie** : compte de trésorerie de l'entreprise (voir « Trésorerie ») ;
  nom, type (`caisse_generale` | `banque` | `autre`), actif. Un seul enregistrement
  `caisse_generale` (singleton), indépendant des `Caisse`/`SessionCaisse` des caissiers.
- **EcritureCompteTresorerie** : immuable ; compte de trésorerie, type
  (`depot_session_cloturee` | `sortie_manuelle` | `entree_manuelle` |
  `reglement_fournisseur` | `remboursement_avoir_client` | `remboursement_avoir_fournisseur`
  | `virement_sortant` | `virement_entrant`), montant **signé** (+ entrée / − sortie,
  contrairement à `MouvementCaisse`), motif (nullable), référence optionnelle
  (`SessionCaisse`, `ReglementFournisseur`, `RemboursementAvoirClient`,
  `RemboursementAvoirFournisseur` ou un autre `CompteTresorerie` pour un virement),
  auteur. Le solde d'un compte est la somme de ses écritures (règle 21).
- **Vente** : numéro (`M{magasin}-C{caisse}-{séquence}`), lignes, remise total, session,
  magasin, **client** (nullable — obligatoire si la vente est à crédit), **avoir_applique**
  (part de l'avoir client déjà déduite du solde à crédit posé, figée à la création — voir
  « Vente à crédit et comptes clients »).
- **LigneVente** : produit + (pièce **ou** unité de vente) + quantité + prix appliqué +
  remise de ligne + **coût appliqué** (CMP figé au moment de la vente, pour un rapport de
  marge reproductible dans le temps).
- **Paiement** : rattaché à une vente ; moyen + montant (plusieurs = paiement mixte). Une
  vente à crédit peut avoir un total de paiements **inférieur** au net à payer.
- **MoyenPaiement** : configurable, actif/inactif ; espèces par défaut.
- **VenteEnAttente** : panier non finalisé + caissier propriétaire ; sans mouvement ni
  paiement ; reprise = continuation du panier au **prix courant** (pas de figeage de prix).
- **Client** : nom, **code** (identifiant type `CLI-000001`, saisi ou généré
  automatiquement, comme le SKU produit), **type de client** (`TypeClient`, optionnel),
  téléphone, adresse, **limite de crédit** (nullable = illimitée), actif. Référentiel
  central, comme le catalogue.
- **TypeClient** : nom + actif. Référentiel libre (ex. particulier, maçon, entrepreneur),
  associé à un client pour catégorisation/reporting — aucune logique métier attachée dans
  le MVP.
- **EcritureCompteClient** : immuable ; type = vente_credit | reglement | retour_client |
  remboursement_avoir ; montant signé ; client ; référence source (`Vente`,
  `ReglementClient`, `RetourVente` ou `RemboursementAvoirClient`) ; auteur. Le solde du
  client est la somme de ses écritures (règle 12).
- **ReglementClient** : encaissement d'une dette client ; client + montant + moyen de
  paiement + session de caisse + caissier ; immuable, comme une vente (règle 14).
- **RemboursementAvoirClient** : remboursement d'un avoir client (règle 20) ; client +
  montant + auteur ; immuable. La part espèces alimente la Caisse Générale (règle 21),
  jamais une session de caisse de caissier.
- **RemboursementAvoirClientPaiement** : rattaché à un remboursement d'avoir client ;
  moyen + montant (paiement mixte possible).
- **EcritureCompteFournisseur** : immuable ; type = achat_credit | reglement |
  retour_fournisseur | remboursement_avoir ; montant signé ; fournisseur ; référence
  source (`CommandeAchat`, `ReglementFournisseur`, `RetourAchat` ou
  `RemboursementAvoirFournisseur`) ; auteur. Le solde du fournisseur est la somme de ses
  écritures (règle 16).
- **ReglementFournisseur** : encaissement d'une dette fournisseur ; fournisseur + montant
  + auteur ; jamais de session de caisse de caissier (règle 17, contrairement à
  `ReglementClient` où la session est toujours obligatoire) — la part espèces alimente
  la Caisse Générale (règle 21). Immuable.
- **ReglementFournisseurPaiement** : rattaché à un règlement fournisseur ; moyen + montant
  (paiement mixte possible).
- **RemboursementAvoirFournisseur** : remboursement d'un avoir fournisseur, symétrique de
  `RemboursementAvoirClient` (règle 20) ; fournisseur + montant + auteur ; immuable. La
  part espèces entre dans la Caisse Générale (règle 21).
- **RemboursementAvoirFournisseurPaiement** : rattaché à un remboursement d'avoir
  fournisseur ; moyen + montant (paiement mixte possible).
- **RetourVente** : retour client, immuable (règle 18) ; numéro, `Vente` + `Client`
  d'origine, motif optionnel, montant total (avoir crédité), auteur.
- **LigneRetourVente** : `LigneVente` d'origine + produit + magasin (source du mouvement
  de retour) + quantité en pièces + montant + coût appliqué (repris de la ligne de
  vente, pour la marge).
- **RetourAchat** : retour fournisseur, symétrique de `RetourVente` ; `CommandeAchat`
  (validée) + `Fournisseur` d'origine, motif optionnel, montant total, auteur.
- **LigneRetourAchat** : `LigneCommandeAchat` d'origine + produit + magasin (destination
  reprise par le mouvement de retour) + quantité en pièces + montant.
- **Devis** : client (obligatoire), magasin, auteur, statut (brouillon | refusé |
  transformé | expiré), remise totale, date de validité, référence vers la `Vente` une
  fois transformé. Modifiable tant que non transformé/expiré.
- **LigneDevis** : produit + (pièce ou unité de vente) + quantité + remise de ligne ;
  **aucun prix stocké** (indicatif au catalogue courant, comme `LigneVenteEnAttente`).
- **Utilisateur** : rôle(s), magasin de rattachement.

---

## Interface (ERP type Odoo)

- **Layout à menu horizontal sur desktop** : une seule barre en haut de page ; sections
  regroupées en menus déroulants (Vente, Stock, Catalogue, Rapports, Administration) plus
  une entrée simple « Trésorerie » (Caisse Générale/Comptes, Gérant/Superadmin uniquement,
  voir « Trésorerie »). Les entrées/sorties de tiroir des caissiers ne sont **pas** un
  menu séparé : elles se saisissent directement sur l'écran de session de vente
  (`/sessions/{session}`, voir « Mouvements de caisse ») — `sessions.index`/
  `sessions.show` couvraient déjà l'essentiel de ce qu'un onglet « Mes caisses » dédié
  aurait montré, donc pas de duplication.
  Sur mobile/tablette (< lg), le même menu s'ouvre en **sidebar coulissante** (offcanvas)
  plutôt que replié en ligne sous la barre — plus confortable à parcourir sur petit
  écran ; mêmes regroupements/permissions, seule la présentation change selon la taille
  d'écran.
- **Dashboard adapté au rôle** : caissier → sa session ; gérant → son magasin (CA, panier
  moyen, top produits, stock sous seuil, écarts de caisse, **total des créances clients**,
  clients en dépassement de limite) ; superadmin → consolidé multi-magasin. Graphiques
  (ventes dans le temps, moyens de paiement, top produits).
- **Aperçu financier du mois** (dashboard gérant/superadmin) : quatre chiffres volontairement
  côte à côte pour qu'ils ne se confondent jamais — total ventes (`total_net`, inclut crédit
  et avoir), total dû (créances clients, solde positif **par client**, jamais net entre
  clients, tous magasins confondus comme la dette fournisseurs), avoirs appliqués du mois
  (`Vente.avoir_applique`), et total en caisse du mois (espèces réellement encaissées —
  paiements à la vente **et** règlements clients, règle 10 ; jamais confondu avec le total
  ventes). Le graphique « Moyens de paiement » compte lui aussi les deux sources.
- Palette ERP quincaillerie : primaire **orange chantier**, accents mélangés
  (bleu industriel, ambre foncé, gris acier), surfaces claires chaudes.
  Définie dans `resources/sass/app.scss` (variables Bootstrap `$primary`
  et suivantes) — toujours modifier la palette à cet endroit, jamais de
  couleur codée en dur dans une vue. `$warning` est un **ambre foncé**
  (`#92650a`), pas un jaune franc : le jaune sécurité d'origine (`#f5b800`)
  tombait sous le seuil WCAG AA (~1.6:1 de contraste sur les surfaces
  claires du thème) — quasi illisible en texte comme en libellé de bouton.
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
  règlements fournisseurs, taxes, comptes de trésorerie**) : auteur, date, avant/après.
- **Historique connexions** : login/logout, date, utilisateur, IP (écouter les événements d'auth).

---

## Conventions

- Terminologie métier en **français** (magasin, caisse, mouvement, unité de vente,
  client, règlement…).
- Écritures stock/vente/**compte client**/**compte fournisseur**/**compte de trésorerie**
  **uniquement via une couche service** encapsulant la transaction ; pas d'écriture
  directe depuis les contrôleurs.
- Rapports = agrégats/vues, sans dupliquer la logique métier.

---

## Hors périmètre (ne pas implémenter dans le MVP)

- Hors-ligne, base locale, synchronisation.
- Facture normalisée FNE — mais **structurer la vente** pour la brancher plus tard.
- Fidélité, promotions avancées, paie.
- **Comptabilité générale** (plan comptable, journal général, bilan, liasse fiscale) —
  seul un périmètre de **trésorerie** est couvert (Caisse Générale, comptes bancaires/
  autres, virements internes, voir « Trésorerie »), pas une comptabilité complète.
- **Taxes côté vente** — aucun taux, aucune ligne taxe sur le ticket (les taxes existent
  côté achat, voir « Achat à crédit et comptes fournisseurs »).
- **Annulation ou modification d'un retour** — un retour, une fois enregistré, est
  immuable comme un règlement ou un mouvement de stock (règle 18) ; toute correction se
  fait par une nouvelle vente/un nouvel achat, jamais par une modification a posteriori
  (voir « Retours »).
- **Remboursement en espèces sur un retour** — un retour crédite toujours un avoir sur
  le compte client/fournisseur, jamais un mouvement de tiroir (règle 18).
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
de validité ; taxes vente exclues du MVP (voir « Hors périmètre » ci-dessus).

Extension post-MVP (retours utilisateur) : **dépôts** ajoutés au référentiel magasin
(champ `type`, sans caisse) ; **taxes** introduites côté achat uniquement (prix d'achat
HT, TTC dérivé par ligne, jamais côté vente) ; **destination par ligne d'achat**
(magasin ou dépôt, une commande peut livrer plusieurs sites) ; **compte fournisseur**
symétrique du compte client (solde dérivé, règlement encaissé à la validation de l'achat
ou ultérieurement) mais **volontairement hors caisse pour la part non-espèces** —
aucune session requise, aucun impact sur le tiroir ; **types de client** en référentiel
libre, sans logique métier attachée ; **menu horizontal** en remplacement de la sidebar ;
**retours client et fournisseur** ajoutés (initialement hors périmètre MVP), sur le même
principe avoir crédité/ligne par ligne/immuable que le reste des écritures de compte,
également hors caisse comme le règlement fournisseur ; **codes client/fournisseur**
ajoutés (identifiant type SKU, saisi ou généré) ; **bug corrigé** : l'annulation d'une
vente/d'un achat à crédit reverse désormais la dette posée à la validation (nouveaux
types d'écriture `annulation_vente`/`annulation_achat`), qui restait auparavant gonflée
indéfiniment ; **mouvements de caisse manuels** (entrée/sortie) ajoutés pour les
caissiers.

Nouvelle extension (trésorerie) : une **Caisse Générale** (compte de trésorerie
permanent, solde dérivé d'écritures immuables, sans session ni caissier assigné — voir
« Trésorerie ») remplace désormais les caisses de caissier comme source/destination de
la part espèces d'un règlement fournisseur et d'un remboursement d'avoir (règle 17/20
amendées en conséquence : ces trois flux n'exigent plus aucune session de caisse
ouverte, contrairement à avant) ; elle reçoit aussi automatiquement le montant compté de
chaque session de caissier clôturée (règle 21). Des **Comptes** (banque/autre)
s'alimentent par virement depuis la Caisse Générale, sans aucun lien avec les moyens de
paiement de la vente. Nouvel écran **Trésorerie** (`tresorerie.voir`/`tresorerie.gerer`,
Gérant/Superadmin uniquement, entrée dédiée du menu horizontal) : répertoire listant
Caisse Générale + Comptes + **toutes** les caisses de vente (pas seulement celles
ouvertes), chacune menant à son propre rapport historique ; et un rapport dédié
(`rapports.tresorerie`) séparé de `rapports.mouvements-caisse`. L'ancien onglet « Mes
caisses » (écran séparé pour saisir un mouvement de tiroir) a été retiré : redondant
avec `sessions.show` qui affichait déjà l'essentiel des mêmes informations pour une
session de caissier, le formulaire entrée/sortie et le solde théorique temps réel ont
été fusionnés directement dans `/sessions/{session}` (voir « Mouvements de caisse »).
