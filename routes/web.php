<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaiementController;
use App\Http\Controllers\ProprietaireController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Routes d'authentification
Route::prefix('/auth')->controller(AuthentificationController::class)->name('auth.')->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
    Route::post('/logout', 'logout')->name('logout');
});

Route::get('auth/showLogin', [AuthentificationController::class, 'showLogin'])->name('login');
Route::get('auth/showRegister', [AuthentificationController::class, 'showRegister'])->name('register');

// Routes publiques pour les paiements (webhooks et retours)
Route::prefix('/paiement')->controller(PaiementController::class)->name('paiement.')->group(function () {
    // Webhook de notification CinetPay (doit être accessible publiquement)
    Route::post('/notify', 'notify')->name('notify');

    // Page de retour après paiement (doit être accessible publiquement)
    Route::get('/retour/{paiement_id}', 'retour')->name('retour');
});

// Routes protégées
Route::middleware('authenticate')->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Routes pour les biens
    Route::prefix('/biens')->controller(BienController::class)->name('biens.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{bien}', 'show')->name('show');
        Route::get('/{bien}/edit', 'edit')->name('edit');
        Route::put('/{bien}', 'update')->name('update');
        Route::delete('/{bien}', 'destroy')->name('destroy');
    });

    // Route pour valider un bien
    Route::post('biens/{bien}/valider', [BienController::class, 'valider'])
        ->name('biens.valider');

    // Route pour rejeter un bien
    Route::post('biens/{bien}/rejeter', [BienController::class, 'rejeter'])
        ->name('biens.rejeter');

    Route::get('/proprietaire/demande', [ProprietaireController::class, 'create'])->name('proprietaire.demande');
    Route::post('/proprietaire/store', [ProprietaireController::class, 'store'])->name('proprietaire.store');


    // Routes pour les catégories
    Route::prefix('/categories')->controller(CategorieController::class)->name('categories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{category}/edit', 'edit')->name('edit');
        Route::put('/{category}', 'update')->name('update');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });

    // Routes pour les utilisateurs
    Route::prefix('/users')->controller(UtilisateurController::class)->name('users.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
    });

    // Routes Réservations
        Route::get('/reservations/create/{bien}', [ReservationController::class, 'create'])->name('reservations.create');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::get('/reservations/{reservation}', [ReservationController::class, 'show'])->name('reservations.show');
        Route::get('/reservations/{reservation}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');
        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations/{reservation}/valider', [ReservationController::class, 'valider'])->name('reservations.valider');
        Route::post('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler'])->name('reservations.annuler');
        Route::get('/reservations/{reservation}/initier-paiement', [ReservationController::class, 'initierPaiement'])->name('reservations.initier-paiement');


    Route::post('/client-documents', [ClientDocumentController::class, 'store'])->name('client-documents.store');
        Route::get('/client-documents', [ClientDocumentController::class, 'index'])->name('client-documents.index');
        Route::get('/client-documents/{document}', [ClientDocumentController::class, 'show'])->name('client-documents.show');
        Route::delete('/client-documents/{document}', [ClientDocumentController::class, 'destroy'])->name('client-documents.destroy');
        Route::get('/client-documents/{document}/download', [ClientDocumentController::class, 'download'])->name('client-documents.download');
        Route::post('/client-documents/{document}/valider', [ClientDocumentController::class, 'valider'])->name('client-documents.valider');
        Route::post('/client-documents/{document}/refuser', [ClientDocumentController::class, 'refuser'])->name('client-documents.refuser');


    // Routes protégées pour les paiements
    Route::prefix('/paiement')->controller(PaiementController::class)->name('paiement.')->group(function () {
        Route::get('/', 'index')->name('index');           // Liste tous les paiements
        Route::post('/', 'store')->name('store');          // Créer un nouveau paiement

        // IMPORTANT: Routes spécifiques AVANT les routes avec paramètres
        Route::get('/initier', 'showInitierPaiement')->name('initier');
        Route::get('/succes/{paiement}', 'showSucces')->name('succes');
        Route::get('/erreur', 'showErreur')->name('erreur');

        // Routes avec paramètres à la fin
        Route::get('/{paiement}', 'show')->name('show');   // Afficher un paiement spécifique
        Route::put('/{paiement}', 'update')->name('update'); // Mettre à jour un paiement
        Route::delete('/{paiement}', 'destroy')->name('destroy'); // Supprimer un paiement

        // Traitement de l'initiation du paiement (POST)
        Route::post('/initier', 'initier')->name('traiter');

        // Webhook de notification CinetPay (doit être accessible publiquement)
        Route::post('/notify', 'notify')->name('notify')->withoutMiddleware(['authenticate']);
        // Page de retour après paiement (doit être accessible publiquement)
        Route::get('/retour/{paiement_id}', 'retour')->name('retour')->withoutMiddleware(['authenticate']);
    });
});


// Route de fallback pour les erreurs 404 (optionnelle)
Route::fallback(function () {
    return response()->json([
        'message' => 'Route non trouvée'
    ], 404);
});
