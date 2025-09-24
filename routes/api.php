<?php

use App\Http\Controllers\PaiementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Routes publiques pour CinetPay (webhooks) - SANS middleware CSRF
Route::post('/paiement/notify', [PaiementController::class, 'notify'])->name('api.paiement.notify');
Route::get('/paiement/retour/{paiement_id}', [PaiementController::class, 'retour'])->name('api.paiement.retour');
