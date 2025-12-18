<?php

use App\Http\Controllers\PaiementController;
use Illuminate\Support\Facades\Route;

Route::post('/paydunya/callback', [PaiementController::class, 'callback'])
    ->name('api.paydunya.callback');

Route::get('/paiement/retour/{paiement_id}', [PaiementController::class, 'retour'])
    ->name('api.paiement.retour');
