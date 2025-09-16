<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaiementController;
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

    // Routes protégées pour les paiements
    Route::prefix('/paiements')->controller(PaiementController::class)->name('paiements.')->group(function () {
        // Routes CRUD pour les paiements
        Route::get('/', 'index')->name('index');           // Liste tous les paiements
        Route::post('/', 'store')->name('store');          // Créer un nouveau paiement
        Route::get('/{paiement}', 'show')->name('show');   // Afficher un paiement spécifique
        Route::put('/{paiement}', 'update')->name('update'); // Mettre à jour un paiement
        Route::delete('/{paiement}', 'destroy')->name('destroy'); // Supprimer un paiement

        // Route pour initier un paiement avec CinetPay
        Route::post('/initier', 'initier')->name('initier');
    });
});

// Routes API optionnelles (si vous voulez une API séparée)
Route::prefix('api/v1')->middleware('authenticate')->group(function () {

    // API pour les paiements
    Route::prefix('/paiements')->controller(PaiementController::class)->name('api.paiements.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{paiement}', 'show')->name('show');
        Route::put('/{paiement}', 'update')->name('update');
        Route::delete('/{paiement}', 'destroy')->name('destroy');
        Route::post('/initier', 'initier')->name('initier');
    });

    // API pour les biens avec filtres
    Route::prefix('/biens')->controller(BienController::class)->name('api.biens.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/search', 'search')->name('search'); // Pour les recherches avec filtres
        Route::get('/{bien}', 'show')->name('show');
    });

    // API pour les catégories
    Route::prefix('/categories')->controller(CategorieController::class)->name('api.categories.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{category}', 'show')->name('show');
    });
});

// Route de fallback pour les erreurs 404 (optionnelle)
Route::fallback(function () {
    return response()->json([
        'message' => 'Route non trouvée'
    ], 404);
});
