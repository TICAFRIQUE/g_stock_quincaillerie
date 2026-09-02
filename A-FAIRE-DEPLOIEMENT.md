# À faire — actions de déploiement en attente

Journal des actions à exécuter sur le poste serveur après une session de
travail, en plus du déploiement standard (voir `GUIDE-MISES-A-JOUR.md` pour
le détail de chaque commande — ce fichier-ci n'est qu'une liste concrète et
datée de ce qui a changé et de ce qu'il reste à faire).

Une entrée par session, la plus récente en haut. Cocher une fois fait, ne
pas supprimer les entrées cochées (historique).

---

## 2026-09-02

- [ ] **Nouveau : Système d'abonnement** — passé une date d'expiration, tout
  compte autre que Superadmin/développeur est redirigé vers "Mon abonnement"
  (lien dans le menu utilisateur, après "Guide d'utilisation") sur **toutes**
  les routes. Déploiement standard, plus :
  ```
  php artisan migrate --force
  ```
  Trois nouvelles tables (`formule_abonnements`, `abonnement_activations`,
  `configuration_abonnements`), aucune donnée existante touchée. **Aucune
  permission à seeder** — l'accès à Gestion abonnement est en dur sur le
  rôle Superadmin (+ compte développeur), jamais délégable via `/roles`.
  - [ ] Définir `ABONNEMENT_DEVELOPPEUR_USERNAME` dans le `.env` du poste
    serveur (voir `.env.example`) **si** un compte support côté éditeur doit
    pouvoir agir sans passer par le Superadmin du client — sinon laisser
    vide, rien ne change.
  - [ ] **Tant qu'aucune activation n'est enregistrée, rien n'est bloqué**
    (comportement volontaire, voir le plan) — l'écran Gestion abonnement
    (menu horizontal, visible uniquement en Superadmin) sert à créer les
    premières formules et à activer l'abonnement du client quand c'est
    voulu commercialement. Ne pas oublier de le faire, sinon l'app reste en
    accès libre indéfiniment.
  - [ ] **Tester après déploiement** : en Superadmin, créer une formule (ex.
    "30 jours"), l'activer, vérifier que jours restants/historique
    s'affichent sur Gestion abonnement **et** sur Mon abonnement (compte
    Caissier). Renseigner les coordonnées de contact. Vérifier qu'un compte
    Caissier/Gérant est bien redirigé vers Mon abonnement une fois la date
    de fin dépassée (peut se tester en activant une formule très courte),
    et que Superadmin garde un accès complet malgré l'expiration.

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
