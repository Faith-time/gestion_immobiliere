<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProprietaireController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UtilisateurController;
use App\Http\Controllers\VenteController;
use App\Http\Controllers\VisiteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Routes d'Authentification (Publiques)
|--------------------------------------------------------------------------
*/

// Routes d'authentification POST
Route::prefix('/auth')->controller(AuthentificationController::class)->name('auth.')->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
    Route::post('/logout', 'logout')->name('logout');
});

// Pages d'authentification GET
Route::get('auth/showLogin', [AuthentificationController::class, 'showLogin'])->name('login');
Route::get('auth/showRegister', [AuthentificationController::class, 'showRegister'])->name('register');

/*
|--------------------------------------------------------------------------
| Routes Protégées
|--------------------------------------------------------------------------
*/

Route::middleware('authenticate')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Route d'Accueil
    |--------------------------------------------------------------------------
    */
    Route::get('/', [HomeController::class, 'index'])->name('home');

    /*
    |--------------------------------------------------------------------------
    | Routes des Biens Immobiliers
    |--------------------------------------------------------------------------
    */
    Route::prefix('/biens')->controller(BienController::class)->name('biens.')->group(function () {
        // CRUD des biens
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{bien}', 'show')->name('show');
        Route::get('/{bien}/edit', 'edit')->name('edit');
        Route::put('/{bien}', 'update')->name('update');
        Route::delete('/{bien}', 'destroy')->name('destroy');

        // Actions administratives
        Route::post('/{bien}/valider', 'valider')->name('valider');
        Route::post('/{bien}/rejeter', 'rejeter')->name('rejeter');

        // Gestion des mandats
        Route::prefix('/{bien}/mandat')->name('mandat.')->group(function () {
            // PDF du mandat
            Route::get('/download', 'downloadMandatPdf')->name('download');
            Route::get('/preview', 'previewMandatPdf')->name('preview');
            Route::post('/regenerate', 'regenerateMandatPdf')->name('regenerate');

            // Signature du mandat
            Route::get('/sign', 'showSignaturePage')->name('sign');
            Route::post('/sign/proprietaire', 'signByProprietaire')->name('sign-proprietaire');
            Route::post('/sign/agence', 'signByAgence')->name('sign-agence');
            Route::delete('/sign/{signatoryType}', 'cancelSignature')->name('cancel-signature');
            Route::get('/signature-status', 'getSignatureStatus')->name('signature-status');

            // PDF signé du mandat
            Route::get('/download-signed', 'downloadSignedMandatPdf')->name('download-signed');
            Route::get('/preview-signed', 'previewSignedMandatPdf')->name('preview-signed');
        });
    });

    // Route de debug (à retirer en production)
    Route::get('/debug/signature/{bien}', [BienController::class, 'debugSignatureData'])->name('debug.signature');

    /*
    |--------------------------------------------------------------------------
    | Routes des Propriétaires
    |--------------------------------------------------------------------------
    */
    Route::prefix('/proprietaire')->controller(ProprietaireController::class)->name('proprietaire.')->group(function () {
        Route::get('/demande', 'create')->name('demande');
        Route::post('/store', 'store')->name('store');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Catégories
    |--------------------------------------------------------------------------
    */
    Route::prefix('/categories')->controller(CategorieController::class)->name('categories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{category}/edit', 'edit')->name('edit');
        Route::put('/{category}', 'update')->name('update');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Utilisateurs
    |--------------------------------------------------------------------------
    */
    Route::prefix('/users')->controller(UtilisateurController::class)->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Visites
    |--------------------------------------------------------------------------
    */
    Route::prefix('/visites')->controller(VisiteController::class)->name('visites.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{visite}', 'show')->name('show');
        Route::get('/{visite}/edit', 'edit')->name('edit');
        Route::put('/{visite}', 'update')->name('update');
        Route::delete('/{visite}', 'destroy')->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Réservations
    |--------------------------------------------------------------------------
    */
    Route::prefix('/reservations')->controller(ReservationController::class)->name('reservations.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create/{bien_id}', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::get('/{id}/edit', 'edit')->name('edit');
        Route::post('/{id}', 'update')->name('update');
        Route::post('/{id}/annuler', 'annuler')->name('annuler');
        Route::get('/{reservation}/initier-paiement', 'initierPaiement')->name('initier-paiement');
    });

    // Routes admin pour les réservations
    Route::prefix('/admin')->name('admin.')->group(function () {
        Route::get('/reservations', [ReservationController::class, 'adminIndex'])->name('reservations.index');
        Route::post('/reservations/{reservation}/valider', [ReservationController::class, 'valider'])->name('reservations.valider');
        Route::post('/reservations/{reservation}/rejeter', [ReservationController::class, 'rejeter'])->name('reservations.rejeter');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Ventes
    |--------------------------------------------------------------------------
    */
    Route::prefix('/ventes')->controller(VenteController::class)->name('ventes.')->group(function () {
        // CRUD des ventes
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{vente}', 'show')->name('show');
        Route::get('/{vente}/edit', 'edit')->name('edit');
        Route::put('/{vente}', 'update')->name('update');
        Route::delete('/{vente}', 'destroy')->name('destroy');

        // Contrats et signatures de vente
        Route::prefix('/{vente}')->group(function () {
            // Gestion des contrats
            Route::get('/contrat/download', 'downloadContract')->name('contract.download');
            Route::get('/contrat/preview', 'previewContract')->name('contract.preview');

            // Page de signature
            Route::get('/signature', 'showSignaturePage')->name('signature.show');

            // Actions de signature
            Route::post('/signature/vendeur', 'signByVendeur')->name('signature.vendeur');
            Route::post('/signature/acheteur', 'signByAcheteur')->name('signature.acheteur');

            // Annulation de signature
            Route::delete('/signature/{signatoryType}', 'cancelSignature')
                ->name('signature.cancel')
                ->where('signatoryType', 'vendeur|acheteur');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Locations
    |--------------------------------------------------------------------------
    */
    Route::prefix('/locations')->controller(LocationController::class)->name('locations.')->group(function () {
        // CRUD des locations
        Route::get('/', 'index')->name('index');

        Route::get('/create', [LocationController::class, 'create'])
            ->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{location}', 'show')->name('show');
        Route::get('/{location}/edit', 'edit')->name('edit');
        Route::put('/{location}', 'update')->name('update');
        Route::delete('/{location}', 'destroy')->name('destroy');

        // Contrats et signatures de location
        Route::prefix('/{location}')->group(function () {
            // Gestion des contrats
            Route::get('/contrat/download', 'downloadContract')->name('contract.download');
            Route::get('/contrat/preview', 'previewContract')->name('contract.preview');

            // Page de signature
            Route::get('/signature', 'showSignaturePage')->name('signature.show');

            // Actions de signature
            Route::post('/signature/bailleur', 'signByBailleur')->name('signature.bailleur');
            Route::post('/signature/locataire', 'signByLocataire')->name('signature.locataire');

            // Annulation de signature
            Route::delete('/signature/{signatoryType}', 'cancelSignature')
                ->name('signature.cancel')
                ->where('signatoryType', 'bailleur|locataire');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Documents Clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('/client-documents')->controller(ClientDocumentController::class)->name('client-documents.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{document}', 'show')->name('show');
        Route::delete('/{document}', 'destroy')->name('destroy');
        Route::get('/{document}/download', 'download')->name('download');
        Route::post('/{document}/valider', 'valider')->name('valider');
        Route::post('/{document}/refuser', 'refuser')->name('refuser');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Paiements
    |--------------------------------------------------------------------------
    */
    Route::prefix('/paiement')->controller(PaiementController::class)->name('paiement.')->group(function () {
        // Routes sans paramètres (à placer AVANT les routes avec paramètres)
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/initier', 'showInitierPaiement')->name('initier');
        Route::post('/initier', 'initier')->name('traiter');
        Route::get('/erreur', 'showErreur')->name('erreur');

        // Routes avec paramètres (à placer APRÈS)
        Route::get('/succes/{paiement}', 'showSucces')->name('succes');
        Route::get('/{paiement}', 'show')->name('show');
        Route::put('/{paiement}', 'update')->name('update');
        Route::delete('/{paiement}', 'destroy')->name('destroy');
    });

});

/*
|--------------------------------------------------------------------------
| Route de Fallback
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json([
        'message' => 'Route non trouvée'
    ], 404);
});
