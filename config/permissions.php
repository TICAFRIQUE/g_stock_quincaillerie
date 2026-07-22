<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Catalogue des permissions
    |--------------------------------------------------------------------------
    |
    | Convention de nommage : module.action. Source de vérité utilisée par
    | RolePermissionSeeder pour créer les permissions spatie/laravel-permission.
    |
    */
    'catalogue' => [
        'produit.voir',
        'produit.creer',
        'produit.modifier',
        'produit.supprimer',
        'categorie.gerer',
        'stock.voir',
        'stock.ajuster',
        'stock.transferer',
        'inventaire.voir',
        'inventaire.realiser',
        'inventaire.valider',
        'achat.voir',
        'achat.creer',
        'achat.valider',
        'achat.annuler',
        'fournisseur.gerer',
        'vente.voir',
        'vente.creer',
        'vente.remise',
        'vente.signaler',
        'vente.annuler',
        'ventenattente.gerer',
        'caisse.ouvrir',
        'caisse.cloturer',
        'caisse.fermer',
        'caisse.gerer',
        'rapport.voir',
        'utilisateur.gerer',
        'role.gerer',
        'parametre.gerer',
        'magasin.gerer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Noyau de permissions protégé
    |--------------------------------------------------------------------------
    |
    | Ces permissions ne sont jamais proposées à l'attribution sur les rôles
    | créés à la volée par l'admin (voir couche de gestion des rôles, future).
    | Décision volontairement code-owned : la protection elle-même ne doit pas
    | être modifiable via une simple UPDATE en base.
    |
    */
    'protected' => [
        'role.gerer',
        'utilisateur.gerer',
        'parametre.gerer',
        'magasin.gerer',
    ],

];
