<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CommandeAchatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DevisController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\InventaireController;
use App\Http\Controllers\JournalActiviteController;
use App\Http\Controllers\MagasinController;
use App\Http\Controllers\MoyenPaiementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParametreController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\ReglementClientController;
use App\Http\Controllers\ReglementFournisseurController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionCaisseController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMouvementController;
use App\Http\Controllers\TaxeController;
use App\Http\Controllers\TransfertController;
use App\Http\Controllers\TypeClientController;
use App\Http\Controllers\UniteController;
use App\Http\Controllers\UniteVenteController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\VenteController;
use App\Http\Controllers\VenteEnAttenteController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('notifications/marquer-lues', [NotificationController::class, 'marquerLues'])->name('notifications.marquer-lues');
    Route::get('notifications/{notification}/ouvrir', [NotificationController::class, 'ouvrir'])->name('notifications.ouvrir');

    Route::middleware('can:administration.gerer')->group(function () {
        Route::resource('magasins', MagasinController::class)->except(['show']);
    });

    Route::middleware('can:categorie.gerer')->group(function () {
        Route::resource('categories', CategorieController::class)
            ->except(['show'])
            ->parameters(['categories' => 'categorie']);
    });

    // Lecture ouverte à produit.voir ; les actions de mutation sont vérifiées
    // finement dans ProduitController (produit.creer/modifier/supprimer).
    Route::middleware('can:produit.voir')->group(function () {
        Route::resource('produits', ProduitController::class)->except(['show']);

        Route::post('produits/{produit}/unite-ventes', [UniteVenteController::class, 'store'])->name('produits.unite-ventes.store');
        Route::put('produits/{produit}/unite-ventes/{uniteVente}', [UniteVenteController::class, 'update'])->name('produits.unite-ventes.update');
        Route::delete('produits/{produit}/unite-ventes/{uniteVente}', [UniteVenteController::class, 'destroy'])->name('produits.unite-ventes.destroy');
    });

    Route::middleware('can:administration.gerer')->group(function () {
        Route::resource('moyens-paiement', MoyenPaiementController::class)
            ->except(['show'])
            ->parameters(['moyens-paiement' => 'moyenPaiement']);

        Route::resource('unites', UniteController::class)->except(['show']);
    });

    Route::middleware('can:taxe.gerer')->group(function () {
        Route::resource('taxes', TaxeController::class)
            ->except(['show'])
            ->parameters(['taxes' => 'taxe']);
    });

    Route::middleware('can:parametre.gerer')->group(function () {
        Route::get('parametres', [ParametreController::class, 'edit'])->name('parametres.edit');
        Route::put('parametres', [ParametreController::class, 'update'])->name('parametres.update');
        Route::post('parametres/backup', [ParametreController::class, 'backup'])->name('parametres.backup');
    });

    Route::middleware('can:fournisseur.voir')->group(function () {
        Route::get('fournisseurs', [FournisseurController::class, 'index'])->name('fournisseurs.index');
    });

    // /fournisseurs/creer avant /fournisseurs/{fournisseur} : sinon "creer"
    // serait capturé comme un {fournisseur} par la route de consultation
    // ci-dessous (même précaution que pour clients).
    Route::middleware('can:fournisseur.gerer')->group(function () {
        Route::get('fournisseurs/creer', [FournisseurController::class, 'create'])->name('fournisseurs.create');
        Route::post('fournisseurs', [FournisseurController::class, 'store'])->name('fournisseurs.store');
        Route::get('fournisseurs/{fournisseur}/modifier', [FournisseurController::class, 'edit'])->name('fournisseurs.edit');
        Route::put('fournisseurs/{fournisseur}', [FournisseurController::class, 'update'])->name('fournisseurs.update');
        Route::delete('fournisseurs/{fournisseur}', [FournisseurController::class, 'destroy'])->name('fournisseurs.destroy');
    });

    Route::middleware('can:fournisseur.voir')->group(function () {
        Route::get('fournisseurs/{fournisseur}', [FournisseurController::class, 'show'])->name('fournisseurs.show');
    });

    Route::middleware('can:fournisseur.reglement')->group(function () {
        Route::get('fournisseurs/{fournisseur}/reglements/creer', [ReglementFournisseurController::class, 'create'])->name('reglements-fournisseur.create');
        Route::post('fournisseurs/{fournisseur}/reglements', [ReglementFournisseurController::class, 'store'])->name('reglements-fournisseur.store');
    });

    Route::middleware('can:typeclient.gerer')->group(function () {
        Route::resource('type-clients', TypeClientController::class)
            ->except(['show'])
            ->parameters(['type-clients' => 'typeClient']);
    });

    Route::middleware('can:client.voir')->group(function () {
        Route::get('clients', [ClientController::class, 'index'])->name('clients.index');
    });

    // /clients/creer avant /clients/{client} : sinon "creer" serait capturé
    // comme un {client} par la route de consultation ci-dessous.
    Route::middleware('can:client.gerer')->group(function () {
        Route::get('clients/creer', [ClientController::class, 'create'])->name('clients.create');
        Route::post('clients', [ClientController::class, 'store'])->name('clients.store');
        Route::post('clients/rapide', [ClientController::class, 'storeRapide'])->name('clients.rapide');
        Route::get('clients/{client}/modifier', [ClientController::class, 'edit'])->name('clients.edit');
        Route::put('clients/{client}', [ClientController::class, 'update'])->name('clients.update');
        Route::delete('clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy');
    });

    Route::middleware('can:client.voir')->group(function () {
        Route::get('clients/{client}', [ClientController::class, 'show'])->name('clients.show');
        Route::get('clients/{client}/ventes/excel', [ClientController::class, 'exporterVentes'])->name('clients.ventes.excel');
        Route::get('clients/{client}/devis/excel', [ClientController::class, 'exporterDevis'])->name('clients.devis.excel');
    });

    Route::middleware('can:achat.voir')->group(function () {
        Route::resource('commande-achats', CommandeAchatController::class)
            ->only(['index', 'create', 'store', 'destroy'])
            ->parameters(['commande-achats' => 'commandeAchat']);
        // withTrashed() : une commande annulée reste consultable (détail avec
        // bandeau d'annulation), voir CommandeAchatController::show().
        Route::get('commande-achats/{commandeAchat}', [CommandeAchatController::class, 'show'])->name('commande-achats.show')->withTrashed();
        Route::post('commande-achats/{commandeAchat}/valider', [CommandeAchatController::class, 'valider'])->name('commande-achats.valider');
        // Pas de withTrashed() ici : tenter d'annuler une commande déjà
        // annulée doit échouer (404 sur la liaison de route), pas la re-traiter.
        Route::post('commande-achats/{commandeAchat}/annuler', [CommandeAchatController::class, 'annuler'])->name('commande-achats.annuler');
    });

    Route::middleware('can:stock.voir')->group(function () {
        Route::get('stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('stock/mouvements', [StockMouvementController::class, 'index'])->name('stock.mouvements.index');
    });

    Route::middleware('can:stock.transferer')->group(function () {
        Route::resource('transferts', TransfertController::class)->only(['index', 'create', 'store']);
    });

    Route::middleware('can:stock.ajuster')->group(function () {
        Route::get('stock/mouvements/creer', [StockMouvementController::class, 'create'])->name('stock.mouvements.create');
        Route::post('stock/mouvements', [StockMouvementController::class, 'store'])->name('stock.mouvements.store');
    });

    Route::middleware('can:inventaire.voir')->group(function () {
        Route::resource('inventaires', InventaireController::class)->only(['index', 'create', 'store', 'show']);
        Route::post('inventaires/{inventaire}/saisir', [InventaireController::class, 'saisir'])->name('inventaires.saisir');
        Route::post('inventaires/{inventaire}/valider', [InventaireController::class, 'valider'])->name('inventaires.valider');
    });

    // La gestion (CRUD) des caisses passe par administration.gerer, distinct
    // de la permission caisse.gerer qui donne au gérant une vue d'ensemble
    // sur les sessions/ventes de son équipe (voir SessionCaisseController,
    // VenteController, VenteEnAttenteController, AutoriseVenteEnAttente).
    Route::middleware('can:administration.gerer')->group(function () {
        Route::resource('caisses', CaisseController::class)
            ->except(['show'])
            ->parameters(['caisses' => 'caisse']);
    });

    Route::middleware('can:caisse.ouvrir')->group(function () {
        Route::get('sessions', [SessionCaisseController::class, 'index'])->name('sessions.index');
        Route::get('caisses/{caisse}/ouvrir', [SessionCaisseController::class, 'create'])->name('sessions.create');
        Route::post('caisses/{caisse}/ouvrir', [SessionCaisseController::class, 'store'])->name('sessions.store');
        Route::get('sessions/{session}', [SessionCaisseController::class, 'show'])->name('sessions.show');
        Route::get('sessions/{session}/rapport', [SessionCaisseController::class, 'rapport'])->name('sessions.rapport');
    });

    Route::middleware('can:caisse.cloturer')->group(function () {
        Route::get('sessions/{session}/cloturer', [SessionCaisseController::class, 'cloturerForm'])->name('sessions.cloturer.form');
        Route::post('sessions/{session}/cloturer', [SessionCaisseController::class, 'cloturer'])->name('sessions.cloturer');
    });

    Route::middleware('can:caisse.fermer')->group(function () {
        Route::post('sessions/{session}/fermer', [SessionCaisseController::class, 'fermer'])->name('sessions.fermer');
    });

    Route::middleware('can:vente.creer')->group(function () {
        Route::get('sessions/{session}/vente', [VenteController::class, 'create'])->name('ventes.create');
        Route::post('sessions/{session}/vente', [VenteController::class, 'store'])->name('ventes.store');
        Route::get('produits/{produit}/stock-magasins', [ProduitController::class, 'stockParMagasin'])->name('produits.stock-magasins');
        // withTrashed() : une vente annulée reste consultable (ticket avec
        // mention "Annulée"), jamais un 404.
        Route::get('ventes/{vente}/ticket', [VenteController::class, 'ticket'])->name('ventes.ticket')->withTrashed();
        Route::get('ventes/{vente}/facture', [VenteController::class, 'facture'])->name('ventes.facture')->withTrashed();
        Route::get('ventes/{vente}/pdf', [VenteController::class, 'pdf'])->name('ventes.pdf')->withTrashed();
        Route::get('ventes/{vente}/excel', [VenteController::class, 'excel'])->name('ventes.excel')->withTrashed();
    });

    Route::middleware('can:vente.signaler')->group(function () {
        Route::post('ventes/{vente}/signaler', [VenteController::class, 'signaler'])->name('ventes.signaler');
    });

    // Pas de withTrashed() ici : tenter d'annuler une vente déjà annulée doit
    // échouer (404 sur la liaison de route), pas la re-traiter.
    Route::middleware('can:vente.annuler')->group(function () {
        Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])->name('ventes.annuler');
    });

    Route::middleware('can:client.reglement')->group(function () {
        Route::get('sessions/{session}/reglements/creer', [ReglementClientController::class, 'create'])->name('reglements.create');
        Route::post('sessions/{session}/reglements', [ReglementClientController::class, 'store'])->name('reglements.store');
    });

    Route::middleware('can:devis.voir')->group(function () {
        Route::get('devis', [DevisController::class, 'index'])->name('devis.index');
    });

    // /devis/creer avant /devis/{devis} : sinon "creer" serait capturé comme
    // un {devis} par la route de consultation ci-dessous.
    Route::middleware('can:devis.gerer')->group(function () {
        Route::get('devis/creer', [DevisController::class, 'create'])->name('devis.create');
        Route::post('devis', [DevisController::class, 'store'])->name('devis.store');
        Route::get('devis/{devis}/modifier', [DevisController::class, 'edit'])->name('devis.edit');
        Route::put('devis/{devis}', [DevisController::class, 'update'])->name('devis.update');
        Route::post('devis/{devis}/annuler', [DevisController::class, 'annuler'])->name('devis.annuler');
    });

    Route::middleware('can:devis.voir')->group(function () {
        Route::get('devis/{devis}', [DevisController::class, 'show'])->name('devis.show');
        Route::get('devis/{devis}/facture', [DevisController::class, 'facture'])->name('devis.facture');
        Route::get('devis/{devis}/pdf', [DevisController::class, 'pdf'])->name('devis.pdf');
        Route::get('devis/{devis}/excel', [DevisController::class, 'excel'])->name('devis.excel');
    });

    Route::middleware('can:devis.transformer')->group(function () {
        Route::get('sessions/{session}/devis/{devis}/transformer', [VenteController::class, 'transformerDevisForm'])->name('devis.transformer.form');
        Route::post('sessions/{session}/devis/{devis}/transformer', [VenteController::class, 'transformerDevis'])->name('devis.transformer');
    });

    Route::middleware('can:ventenattente.gerer')->group(function () {
        Route::get('sessions/{session}/ventes-en-attente', [VenteEnAttenteController::class, 'index'])->name('ventes-en-attente.index');
        Route::post('sessions/{session}/ventes-en-attente', [VenteEnAttenteController::class, 'store'])->name('ventes-en-attente.store');
        Route::put('ventes-en-attente/{venteEnAttente}', [VenteEnAttenteController::class, 'update'])->name('ventes-en-attente.update');
        Route::get('ventes-en-attente/{venteEnAttente}/reprendre', [VenteController::class, 'reprendre'])->name('ventes-en-attente.reprendre.form');
        Route::post('ventes-en-attente/{venteEnAttente}/reprendre', [VenteEnAttenteController::class, 'reprendre'])->name('ventes-en-attente.reprendre');
        Route::delete('ventes-en-attente/{venteEnAttente}', [VenteEnAttenteController::class, 'annuler'])->name('ventes-en-attente.annuler');
    });

    Route::middleware('can:utilisateur.gerer')->group(function () {
        Route::resource('utilisateurs', UtilisateurController::class)
            ->except(['show'])
            ->parameters(['utilisateurs' => 'utilisateur']);
        Route::post('utilisateurs/{utilisateur}/reinitialiser-mot-de-passe', [UtilisateurController::class, 'reinitialiserMotDePasse'])
            ->name('utilisateurs.reinitialiser-mot-de-passe');
    });

    Route::middleware('can:role.gerer')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('can:rapport.voir')->group(function () {
        Route::get('rapports', [RapportController::class, 'index'])->name('rapports.index');
        Route::get('rapports/ventes', [RapportController::class, 'ventes'])->name('rapports.ventes');
        Route::get('rapports/marge', [RapportController::class, 'marge'])->name('rapports.marge');
        Route::get('rapports/stock', [RapportController::class, 'stock'])->name('rapports.stock');
        Route::get('rapports/ecarts-caisse', [RapportController::class, 'ecartsCaisse'])->name('rapports.ecarts-caisse');
        Route::get('rapports/casse', [RapportController::class, 'casse'])->name('rapports.casse');
        Route::get('rapports/inventaires', [RapportController::class, 'inventaires'])->name('rapports.inventaires');
        Route::get('journal', [JournalActiviteController::class, 'index'])->name('journal.index');
    });
});
