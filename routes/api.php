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

// Webhook PayDunya (appelé par PayDunya) - SANS middleware CSRF et AUTH
Route::post('/paydunya/callback', [PaiementController::class, 'callback'])
    ->name('api.paydunya.callback');

// Route de retour après paiement (peut aussi être dans web.php)
Route::get('/paiement/retour/{paiement_id}', [PaiementController::class, 'retour'])
    ->name('api.paiement.retour');
