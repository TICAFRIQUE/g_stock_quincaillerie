<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Compte développeur (éditeur)
    |--------------------------------------------------------------------------
    |
    | Nom d'utilisateur exempté du blocage d'abonnement, au même titre que le
    | Superadmin — voir User::estGestionnaireAbonnement(). Fixé uniquement
    | côté .env (jamais configurable depuis l'interface) : seul quelqu'un
    | ayant accès au fichier .env du serveur peut le changer, pas un simple
    | Superadmin via l'UI.
    |
    */
    'developpeur_username' => env('ABONNEMENT_DEVELOPPEUR_USERNAME'),

];
