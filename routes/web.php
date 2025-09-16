<?php

use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\HomeController;
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

// Routes protégées
Route::middleware('authenticate')->group(function () {

    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Routes pour les biens - CORRIGÉ: utilise BienController
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
});
