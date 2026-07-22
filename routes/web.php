<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CaisseController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\CommandeAchatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FournisseurController;
use App\Http\Controllers\InventaireController;
use App\Http\Controllers\JournalActiviteController;
use App\Http\Controllers\MagasinController;
use App\Http\Controllers\MoyenPaiementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProduitController;
use App\Http\Controllers\RapportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionCaisseController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMouvementController;
use App\Http\Controllers\TransfertController;
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

    Route::middleware('can:magasin.gerer')->group(function () {
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

    Route::middleware('can:parametre.gerer')->group(function () {
        Route::resource('moyens-paiement', MoyenPaiementController::class)
            ->except(['show'])
            ->parameters(['moyens-paiement' => 'moyenPaiement']);
    });

    Route::middleware('can:fournisseur.gerer')->group(function () {
        Route::resource('fournisseurs', FournisseurController::class)->except(['show']);
    });

    Route::middleware('can:achat.voir')->group(function () {
        Route::resource('commande-achats', CommandeAchatController::class)
            ->only(['index', 'create', 'store', 'show', 'destroy'])
            ->parameters(['commande-achats' => 'commandeAchat']);
        Route::post('commande-achats/{commandeAchat}/valider', [CommandeAchatController::class, 'valider'])->name('commande-achats.valider');
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

    Route::middleware('can:caisse.gerer')->group(function () {
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
        // withTrashed() : une vente annulée reste consultable (ticket avec
        // mention "Annulée"), jamais un 404.
        Route::get('ventes/{vente}/ticket', [VenteController::class, 'ticket'])->name('ventes.ticket')->withTrashed();
    });

    Route::middleware('can:vente.signaler')->group(function () {
        Route::post('ventes/{vente}/signaler', [VenteController::class, 'signaler'])->name('ventes.signaler');
    });

    // Pas de withTrashed() ici : tenter d'annuler une vente déjà annulée doit
    // échouer (404 sur la liaison de route), pas la re-traiter.
    Route::middleware('can:vente.annuler')->group(function () {
        Route::post('ventes/{vente}/annuler', [VenteController::class, 'annuler'])->name('ventes.annuler');
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
