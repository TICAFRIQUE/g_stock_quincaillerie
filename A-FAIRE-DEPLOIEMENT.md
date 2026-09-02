# À faire — actions de déploiement en attente

Journal des actions à exécuter sur le poste serveur après une session de
travail, en plus du déploiement standard (voir `GUIDE-MISES-A-JOUR.md` pour
le détail de chaque commande — ce fichier-ci n'est qu'une liste concrète et
datée de ce qui a changé et de ce qu'il reste à faire).

Une entrée par session, la plus récente en haut. Cocher une fois fait, ne
pas supprimer les entrées cochées (historique).

---

## 2026-09-02

- [ ] **Sécurité (critique) : limitation des tentatives de connexion** —
  déploiement standard, aucune migration. Rien à configurer : le verrou (5
  tentatives, 60s d'attente, par identifiant + IP) est actif dès le
  déploiement.
  - [ ] **Tester après déploiement** : saisir un mauvais code PIN 5-6 fois
    de suite sur `/login` → le message doit passer à "Trop de tentatives.
    Réessayez dans N secondes." ; vérifier qu'un autre identifiant peut se
    connecter normalement pendant ce temps.

- [ ] **Sécurité (haute) : IDOR sur les ventes en attente** — déploiement
  standard, aucune migration. Un Gérant d'un magasin ne peut plus modifier/
  reprendre une vente en attente d'un autre magasin en devinant son ID.

- [ ] **Sécurité (haute + moyenne, coquille desktop) : restriction de
  navigation** — se met à jour automatiquement (v1.0.9) comme d'habitude,
  aucune action côté serveur. La fenêtre principale refuse désormais toute
  navigation hors de l'adresse du serveur configuré, et l'aperçu PDF refuse
  une URL qui ne vient pas de ce même serveur.
  - [ ] **Tester après mise à jour** : vérifier qu'imprimer une facture/un
    devis/un bon d'achat fonctionne toujours normalement (aucune régression
    attendue, ces PDF viennent déjà du même serveur).

- [ ] **Nouveau : alerte d'abonnement + téléphone utilisateur + confidentialité
  des comptes privilégiés**. Déploiement standard, plus :
  ```
  php artisan migrate --force
  ```
  Une migration (`users.telephone`, nullable), aucune donnée existante
  touchée. Aucune permission à seeder.
  - [ ] **Tester après déploiement** : activer une formule de 5 jours ou
    moins pour voir le bandeau d'alerte apparaître sur le dashboard (lien
    "Renouveler maintenant" pour Superadmin/développeur uniquement).
  - [ ] Vérifier que "Abonnement" apparaît bien en dernier dans le sous-menu
    Administration (Superadmin/développeur), et n'apparaît plus dans la
    barre horizontale directement.
  - [ ] Vérifier qu'un Gérant (ou tout compte non Superadmin/développeur) ne
    voit plus les comptes Superadmin/développeur sur `/utilisateurs`.
  - [ ] Créer un utilisateur avec un numéro de téléphone pour confirmer le
    nouveau champ.

- [ ] **Nouveau : option "Remplacer" à l'activation** — case à cocher
  (visible seulement s'il reste des jours) sur le formulaire d'activation,
  pour un vrai changement d'offre (ex. repasser un client en Essai malgré
  des jours restants) au lieu du renouvellement additif par défaut.
  Déploiement standard, aucune migration.

- [ ] **Nouveau : Système d'abonnement** — passé une date d'expiration, tout
  compte autre que Superadmin/développeur est redirigé vers "Mon abonnement"
  (lien dans le menu utilisateur, après "Guide d'utilisation") sur **toutes**
  les routes. Déploiement standard, plus :
  ```
  php artisan migrate --force
  php artisan db:seed --class=FormuleAbonnementSeeder --force
  ```
  Trois nouvelles tables (`formule_abonnements`, `abonnement_activations`,
  `configuration_abonnements`), aucune donnée existante touchée. **Aucune
  permission à seeder** — l'accès à Gestion abonnement est en dur sur le
  rôle Superadmin (+ compte développeur), jamais délégable via `/roles`.
  - [ ] `FormuleAbonnementSeeder` crée 3 formules de départ (Essai/Mensuelle/
    Illimité, prix indicatifs à ajuster) — **modifiables** ensuite depuis
    Gestion abonnement (pas de bouton suppression pour l'instant, seulement
    activer/désactiver et en ajouter).
  - [ ] Définir `ABONNEMENT_DEVELOPPEUR_USERNAME` dans le `.env` du poste
    serveur (voir `.env.example`) **si** un compte support côté éditeur doit
    pouvoir agir sans passer par le Superadmin du client — sinon laisser
    vide, rien ne change.
  - [ ] **Tant qu'aucune activation n'est enregistrée, rien n'est bloqué**
    (comportement volontaire, voir le plan) — l'écran Gestion abonnement
    (menu horizontal, visible uniquement en Superadmin) sert à activer
    l'abonnement du client quand c'est voulu commercialement. Ne pas
    oublier de le faire, sinon l'app reste en accès libre indéfiniment.
  - [ ] **Tester après déploiement** : en Superadmin, créer une formule (ex.
    "30 jours"), l'activer, vérifier que jours restants/historique
    s'affichent sur Gestion abonnement **et** sur Mon abonnement (compte
    Caissier). Renseigner les coordonnées de contact. Vérifier qu'un compte
    Caissier/Gérant est bien redirigé vers Mon abonnement une fois la date
    de fin dépassée (peut se tester en activant une formule très courte),
    et que Superadmin garde un accès complet malgré l'expiration.

- [ ] **Nouveau : quantités décimales (ex. 1.5 mètre de câble)** — dernière et
  plus large des trois étapes d'ouverture hors zone FCFA (après devise et
  TVA). Toutes les colonnes de quantité transactionnelle (stock, mouvements
  de stock, lignes de vente/achat/devis/vente-en-attente, retours,
  inventaire, transferts, bons de livraison) passent de entier à
  `decimal(12,3)`. **`unite_ventes.facteur` reste volontairement entier**
  (multiplicateur d'un lot, ex. carton de 12 — pas une quantité). Aucune
  bascule dans Paramètres : la saisie décimale est **toujours autorisée**,
  décision prise avec l'utilisateur (une quantité entière saisie aujourd'hui
  se comporte à l'identique — 5 reste "5" à l'affichage, jamais "5.000").
  Déploiement standard, plus :
  ```
  php artisan migrate --force
  npm run build
  ```
  La migration modifie le type de 15 colonnes en SQL brut (`ALTER TABLE
  MODIFY COLUMN`, pas de `->change()` — doctrine/dbal n'est pas installé sur
  ce projet, voir le commentaire de la migration) ; **aucune donnée
  existante perdue ou tronquée** (testé migration + rollback + remigration
  sur les données réelles avant ce déploiement). `npm run build` est
  **indispensable** cette fois : la saisie décimale (virgule ou point
  acceptés, normalisés côté serveur) dépend de `resources/js/app.js`
  recompilé — sans ce rebuild, les écrans de vente/devis continueraient de
  tronquer toute quantité décimale tapée (comportement JS inchangé tant que
  le bundle n'est pas reconstruit).
  - [ ] **Tester après déploiement** : sur l'écran de vente, ajouter une
    ligne et taper une quantité avec virgule (ex. "1,5") → doit s'afficher
    "1.5" dans le panier et le total se recalculer correctement (ex. 1.5 ×
    280 F = 420 F) ; finaliser la vente et vérifier le ticket ("1.5" propre,
    pas "1.500") ; vérifier qu'une vente à quantité entière (ex. "3") reste
    identique à avant (affichage "3", pas "3.0"). Même test côté devis,
    achat (bon d'achat), transfert de stock, mouvement de stock manuel
    (casse/ajustement), et comptage d'inventaire.
  - [ ] Vérifier qu'un retour (client ou fournisseur) sur une ligne à
    quantité décimale plafonne bien au reste retournable (ex. vendu 1.5,
    déjà retourné 0.5 → max retournable 1.0 sur le formulaire).

- [ ] **Nouveau : TVA optionnelle sur la vente et le devis** — deuxième étape
  de l'ouverture hors zone FCFA (après la devise), même principe que côté
  achat : `taxe_id` optionnel par ligne (référentiel `Taxe` déjà existant,
  Administration → Taxes), TTC dérivé, jamais stocké. Déploiement standard,
  plus :
  ```
  php artisan migrate --force
  ```
  Deux nouvelles migrations (`ligne_ventes.taxe_id`, `ligne_devis.taxe_id`,
  toutes deux nullables), aucune donnée existante touchée — une vente/un
  devis sans taxe choisie se comporte exactement comme avant (0 F de taxe,
  mêmes montants qu'aujourd'hui). Aucune nouvelle permission (réutilise
  `taxe.gerer`, déjà seedé côté achat).
  - [ ] **Changement de comportement à connaître** : `Vente.total_net` (le
    montant réellement dû/encaissé/porté en dette) inclut désormais la taxe
    quand une ligne en porte une — la remise sur le total s'applique au
    montant TTC (HT + taxes), pas seulement au HT. Sans taxe configurée sur
    aucune ligne, ce calcul reste identique à avant.
  - [ ] Le sélecteur "Taxe" par ligne (écran de vente, devis) ne s'affiche
    que si au moins une taxe active existe — invisible par défaut pour tout
    client qui n'utilise pas cette fonctionnalité.
  - [ ] **Tester après déploiement** : créer une taxe (ex. "TVA", 18%) dans
    Administration → Taxes ; sur l'écran de vente, ajouter une ligne avec
    cette taxe → vérifier que le récapitulatif affiche "Total taxes" et que
    le ticket/la facture affichent la ventilation HT/Taxes/Net ; même
    vérification côté devis (création + transformation en vente, la taxe
    choisie doit être reprise telle quelle) ; vérifier qu'une vente sans
    taxe reste identique à avant (aucune ligne "Total taxes" affichée).

- [ ] **Nouveau : Devise configurable (affichage uniquement, pas de
  conversion)** — permet d'utiliser l'application hors zone FCFA (Euro,
  Dollar…). Un référentiel `Devise` (nom + abréviation) remplace le "F"
  codé en dur partout (tickets, factures, dashboard, écran de vente/achat/
  devis en direct via `window.DEVISE_ABREVIATION`). **Aucune conversion de
  montant** : changer la devise ne fait que changer le libellé affiché, les
  montants restent des entiers identiques. Déploiement standard, plus :
  ```
  php artisan migrate --force
  php artisan db:seed --class=DeviseSeeder --force
  ```
  Deux nouvelles migrations (table `devises`, colonne `parametres.devise_id`
  nullable), aucune donnée existante touchée. `DeviseSeeder` crée FCFA/Euro/
  Dollar et active FCFA par défaut si aucune devise n'est déjà configurée.
  - [ ] **Permission à seeder manuellement sur les installs existantes** :
    `devise.gerer` est nouvelle dans `config/permissions.php` — comme pour
    toute nouvelle permission, le rôle Gérant d'une install déjà en
    production ne la reçoit pas automatiquement (`RolePermissionSeeder` ne
    resynchronise que les rôles nouvellement créés). Accorder `devise.gerer`
    au rôle voulu depuis `/roles` si le Gérant doit pouvoir changer la
    devise (sinon seul Superadmin, qui a le bypass total, le peut).
  - [ ] **Tester après déploiement** : dans Administration → Devises,
    vérifier que FCFA/Euro/Dollar sont bien présents ; changer la devise
    active depuis Paramètres → vérifier que le nouveau symbole apparaît
    immédiatement (sans redéploiement) sur le dashboard, un ticket de vente
    existant, ET l'écran de vente en direct (totaux calculés en JS) ; remettre
    FCFA par défaut ensuite.

## 2026-09-01

- [ ] **Nouveau : Prix personnalisé à la vente** — le caissier peut taper un
  prix différent du catalogue sur une ligne (enregistré comme une remise
  "montant" classique, permission `vente.remise` déjà existante). Déploiement
  standard, plus :
  ```
  php artisan migrate --force
  ```
  Une seule migration (`add_prix_personnalise_to_ligne_ventes_table`), une
  colonne booléenne `default(false)` — aucune donnée existante touchée,
  aucune permission nouvelle, rien à cocher sur `/roles`.
  **Tester après déploiement** : sur une vente, choisir "Prix personnalisé"
  dans la colonne Remise du panier, taper un prix inférieur au catalogue →
  vérifier que le ticket/la facture affichent ce prix sans mention de remise.
  Egalement : `npm run build` (passe de style globale — fond blanc des
  cartes, bordure orange des champs, formulaires centrés).

- [ ] **Nouveau : Bon de livraison** (une vente peut être livrée en plusieurs
  fois, suivi ligne par ligne — voir `resources/views/ventes/ticket.blade.php`,
  carte "Livrer des articles"). Déploiement standard, plus :
  ```
  php artisan migrate --force
  php artisan db:seed --class=RolePermissionSeeder --force
  ```
  Les deux migrations créent `bon_livraisons`/`ligne_bon_livraisons` (aucune
  donnée existante touchée). Le seeder enregistre la nouvelle permission
  `vente.livrer` — comme pour toute nouvelle permission (voir
  `GUIDE-MISES-A-JOUR.md`), elle n'est **cochée pour aucun rôle existant**
  automatiquement : l'accorder manuellement sur `/roles` au(x) rôle(s) qui
  doivent pouvoir enregistrer une livraison (ex. Caissier).

- [ ] **Tester après déploiement** : sur une vente existante, enregistrer un
  bon de livraison partiel (carte "Livrer des articles" du ticket) → vérifier
  que le reste à livrer se met à jour, que le PDF individuel du bon
  s'imprime sans prix, et que le badge de statut de livraison apparaît bien
  dans la Facture, le rapport des ventes, la fiche client et le détail de
  session de caisse.

## 2026-08-28

- [ ] **Déployer le code sur le poste serveur** (copie/zip du projet, ou
  `git pull`), puis vider les caches :
  ```
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  ```
  Aucune migration nécessaire.

- [ ] **Coquille desktop** : rien à faire manuellement. Chaque poste (déjà en
  v1.0.7 ou avant) doit se mettre à jour tout seul vers **v1.0.8** au
  prochain démarrage, une fois `public/desktop-updates/` accessible depuis
  le réseau du poste serveur (déjà rempli dans ce dépôt). Vérifier ensuite
  via Fichier → Vérifier les mises à jour… sur un poste si besoin de forcer.

- [ ] **Tester après déploiement** : imprimer une facture, un devis, le
  bouton "Facture" du ticket — doit maintenant ouvrir le PDF réel dans une
  fenêtre de l'app, identique au bouton "Télécharger en PDF".

- [ ] **Nouveau : Guide d'utilisation** — page accessible via le menu
  utilisateur (à côté de "Se déconnecter") → `/guide`. Chaque section n'est
  visible que si l'utilisateur a la permission correspondante (un caissier
  ne voit pas Trésorerie/Administration, par ex.) — vérifier après
  déploiement avec un compte Caissier et un compte Gérant. Rien à seeder,
  juste le déploiement de code ci-dessus. À relire/compléter au fil de l'eau
  si des sections manquent.

- [ ] `RolePermissionSeeder` corrigé pour ne plus écraser les permissions
  personnalisées de Gérant/Caissier — **aucune action requise
  immédiatement**, ce correctif ne change de comportement qu'au **prochain**
  ajout de permission dans `config/permissions.php` (voir
  `GUIDE-MISES-A-JOUR.md`, section dédiée).
