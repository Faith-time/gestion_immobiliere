<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaiementController;

Route::middleware('auth:sanctum')->group(function () {
    // Liste des paiements
    Route::get('/paiements', [PaiementController::class, 'index'])->name('paiements.index');

    // Créer un paiement
    Route::post('/paiements', [PaiementController::class, 'store'])->name('paiements.store');

    // Afficher un paiement spécifique
    Route::get('/paiements/{id}', [PaiementController::class, 'show'])->name('paiements.show');

    // Mettre à jour un paiement (ex: après confirmation CinetPay)
    Route::put('/paiements/{id}', [PaiementController::class, 'update'])->name('paiements.update');

    // Supprimer un paiement
    Route::delete('/paiements/{id}', [PaiementController::class, 'destroy'])->name('paiements.destroy');
});
