<?php

use App\Http\Controllers\AppartementController;
use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\AvisRetardController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\CommissionController;
use App\Http\Controllers\ConversationController;
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
Route::prefix('/auth')->controller(AuthentificationController::class)->name('auth.')->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
    Route::post('/logout', 'logout')->name('logout');
});

Route::get('auth/showLogin', [AuthentificationController::class, 'showLogin'])->name('login');
Route::get('auth/showRegister', [AuthentificationController::class, 'showRegister'])->name('register');

/*
|--------------------------------------------------------------------------
| Routes Protégées
|--------------------------------------------------------------------------
*/
Route::middleware('authenticate')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');

    /*
    |--------------------------------------------------------------------------
    | Routes des Biens
    |--------------------------------------------------------------------------
    */
    Route::prefix('/biens')->controller(BienController::class)->name('biens.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/catalogue', 'catalogue')->name('catalogue');
        Route::get('/{bien}', 'show')->name('show');
        Route::get('/{bien}/edit', 'edit')->name('edit');
        Route::put('/{bien}', 'update')->name('update');
        Route::delete('/{bien}', 'destroy')->name('destroy');

        Route::post('/{bien}/valider', 'valider')->name('valider');
        Route::post('/{bien}/rejeter', 'rejeter')->name('rejeter');

        Route::prefix('/{bien}/mandat')->name('mandat.')->group(function () {
            Route::get('/download', 'downloadMandatPdf')->name('download');
            Route::get('/preview', 'previewMandatPdf')->name('preview');
            Route::post('/regenerate', 'regenerateMandatPdf')->name('regenerate');

            Route::get('/sign', 'showSignaturePage')->name('sign');
            Route::post('/sign/proprietaire', 'signByProprietaire')->name('sign-proprietaire');
            Route::post('/sign/agence', 'signByAgence')->name('sign-agence');
            Route::delete('/sign/{signatoryType}', 'cancelSignature')->name('cancel-signature');
            Route::get('/signature-status', 'getSignatureStatus')->name('signature-status');

            Route::get('/download-signed', 'downloadSignedMandatPdf')->name('download-signed');
            Route::get('/preview-signed', 'previewSignedMandatPdf')->name('preview-signed');
        });
    });

    Route::get('/debug/signature/{bien}', [BienController::class, 'debugSignatureData'])->name('debug.signature');

    Route::prefix('/biens/{bien}/appartements')
        ->controller(AppartementController::class)
        ->name('appartements.')
        ->group(function () {
            // Liste des appartements d'un bien
            Route::get('/', 'index')->name('index');

            // Afficher le formulaire d'édition
            Route::get('/{appartement}/edit', 'edit')->name('edit');

            // Mettre à jour un appartement
            Route::put('/{appartement}', 'update')->name('update');

            // Supprimer un appartement
            Route::delete('/{appartement}', 'destroy')->name('destroy');
        });
    /*
    |--------------------------------------------------------------------------
    | Routes des Propriétaires
    |--------------------------------------------------------------------------
    */
    Route::prefix('/proprietaire')->controller(ProprietaireController::class)->name('proprietaire.')->group(function () {
        Route::get('/demande', 'create')->name('demande');
        Route::post('/store', 'store')->name('store');
    });
    Route::get('/dashboard/proprietaire', [BienController::class, 'dashboardProprietaire'])
        ->name('dashboard.proprietaire');

    // Détails d'un bien pour le propriétaire
    Route::get('/biens/{bien}/details-proprietaire', [BienController::class, 'detailsBienProprietaire'])
        ->name('biens.details-proprietaire');


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
        Route::post('/{visite}/annuler', 'annuler')->name('annuler');
        // Routes admin
        Route::post('/{visite}/confirmer', 'confirmer')->name('confirmer');
        Route::post('/{visite}/rejeter', 'rejeter')->name('rejeter');
        Route::post('/{visite}/marquer-effectuee', 'marquerEffectuee')->name('marquer-effectuee');
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
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{vente}', 'show')->name('show');
        Route::get('/{vente}/edit', 'edit')->name('edit');
        Route::put('/{vente}', 'update')->name('update');
        Route::delete('/{vente}', 'destroy')->name('destroy');

        Route::prefix('/{vente}')->group(function () {
            Route::get('/contrat/download', 'downloadContract')->name('contract.download');
            Route::get('/contrat/preview', 'previewContract')->name('contract.preview');
            Route::get('/signature', 'showSignaturePage')->name('signature.show');
            Route::post('/signature/vendeur', 'signByVendeur')->name('signature.vendeur');
            Route::post('/signature/acheteur', 'signByAcheteur')->name('signature.acheteur');
            Route::delete('/signature/{signatoryType}', 'cancelSignature')
                ->name('signature.cancel')
                ->where('signatoryType', 'vendeur|acheteur');
        });
    });

/*
|--------------------------------------------------------------------------
| Routes des Locations - VERSION COMPLÈTE
|--------------------------------------------------------------------------
*/
Route::prefix('/locations')->controller(LocationController::class)->name('locations.')->group(function () {
    Route::get('/mes-loyers', 'mesLoyers')->name('mes-loyers');

    Route::get('/', 'index')->name('index');
    Route::get('/create', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{location}', 'show')->name('show');
    Route::get('/{location}/edit', 'edit')->name('edit');
    Route::put('/{location}', 'update')->name('update');
    Route::delete('/{location}', 'destroy')->name('destroy');

    // Routes pour le contrat PDF
    Route::get('/{location}/contrat/download', 'downloadContract')->name('contract.download');
    Route::get('/{location}/contrat/preview', 'previewContract')->name('contract.preview');

    // 🆕 ROUTES DE GESTION DES LOYERS MENSUELS
    Route::post('/{location}/payer-loyer', 'payerLoyer')->name('payer-loyer');
    Route::get('/{location}/loyer/{mois}', 'detailsLoyerMois')->name('loyer.details');

    // Routes admin
    Route::post('/{location}/valider-paiement', 'validerPaiementLocation')->name('valider-paiement');
    Route::post('/{location}/terminer', 'terminerLocation')->name('terminer');
});

// Routes de signature (déjà existantes - gardez-les telles quelles)
Route::prefix('/signature')->controller(LocationController::class)->name('signature.')->group(function () {
    Route::get('/{location}', 'showSignaturePage')->name('show');
    Route::post('/{location}/bailleur', 'signByBailleur')->name('bailleur');
    Route::post('/{location}/locataire', 'signByLocataire')->name('locataire');
    Route::delete('/{location}/{signatoryType}', 'cancelSignature')
        ->name('cancel')
        ->where('signatoryType', 'bailleur|locataire');
});

Route::post('/locations/{location}/test-notifications', [LocationController::class, 'testNotifications'])
    ->name('locations.test.notifications');
// ✅ Routes de signature HORS du groupe locations

    Route::post('/locations/{location}/test-notifications', [LocationController::class, 'testNotifications'])
        ->name('locations.test.notifications');

    /*
    |--------------------------------------------------------------------------
    | Routes des Documents Clients
    |--------------------------------------------------------------------------
    */
    Route::prefix('/client-documents')->controller(ClientDocumentController::class)->name('client-documents.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{document}', 'show')->name('show');
        Route::put('/{document}', 'update')->name('update'); // ✅ Route de mise à jour
        Route::delete('/{document}', 'destroy')->name('destroy');
        Route::get('/{document}/download', 'download')->name('download');

        // Routes admin
        Route::post('/{document}/valider', 'valider')->name('valider');
        Route::post('/{document}/refuser', 'refuser')->name('refuser');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes des Paiements (PayDunya)
    |--------------------------------------------------------------------------
    */
    Route::prefix('/paiement')->controller(PaiementController::class)->name('paiement.')->group(function () {
        // Routes CRUD
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');

        // ✅ CORRECTION : Route avec paramètres d'URL
        Route::get('/initier/{id}/{paiement_id}', 'showInitierPaiement')->name('initier.show');
        Route::post('/initier', 'initier')->name('initier');

        // Pages de résultat
        Route::get('/succes/{paiement}', 'showSucces')->name('succes');
        Route::get('/retour/{paiement}', 'retour')->name('retour');
        Route::get('/erreur', 'showErreur')->name('erreur');

        // Utilitaires
        Route::get('/options', 'getPaiementOptions')->name('options');

        // CRUD avec paramètres
        Route::get('/{paiement}', 'show')->name('show');
        Route::put('/{paiement}', 'update')->name('update');
        Route::delete('/{paiement}', 'destroy')->name('destroy');
    });
    Route::get('/paiement/{id}/info-fractionnement', [PaiementController::class, 'getInfoPaiementFractionne'])
        ->name('paiement.info.fractionnement');

    /*
    |--------------------------------------------------------------------------
    | Routes des Avis de Retard
    |--------------------------------------------------------------------------
    */
    Route::prefix('/avis-retard')->controller(AvisRetardController::class)->name('avis-retard.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{avisRetard}', 'show')->name('show');
        Route::post('/rappels', 'envoyerRappels')->name('rappels');
        Route::post('/retards', 'envoyerAvisRetards')->name('retards');
        Route::post('/traiter-automatique', 'traiterNotificationsAutomatiques')->name('traiter');
        Route::post('/{avisRetard}/paye', 'marquerPaye')->name('paye');
        Route::post('/test-mailtrap', 'testMailtrap')->name('test-mailtrap');
    });

    Route::get('/admin/dashboard-global', [BienController::class, 'dashboardAdminGlobal'])
        ->name('dashboard.admin.global');

    /*
    |--------------------------------------------------------------------------
    | Routes des Commissions
    |--------------------------------------------------------------------------
    */
    Route::prefix('/commissions')->controller(CommissionController::class)->name('commissions.')->group(function () {
        // Routes principales
        Route::get('/', 'index')->name('index');
        Route::get('/dashboard', 'dashboard')->name('dashboard');

        // Gestion des paiements
        Route::post('/{id}/marquer-payee', 'marquerPayee')->name('marquer-payee');

        // Calculs et répartitions
        Route::post('/calculer-repartition', 'calculerRepartition')->name('calculer-repartition');

        // Locations - Commissions mensuelles
        Route::get('/location/{locationId}/recapitulatif', 'recapitulatifLocation')->name('recapitulatif-location');
        Route::get('/location/{locationId}/mois-courant', 'commissionMoisCourant')->name('mois-courant');
        Route::get('/location/{locationId}/a-venir', 'commissionsAVenir')->name('a-venir');
        Route::post('/location/{locationId}/renouvellement', 'genererRenouvellement')->name('generer-renouvellement');

        // Rapports et exports
        Route::get('/rapport/periode', 'rapport')->name('rapport');
        Route::get('/export/csv', 'export')->name('export');

        // Détails d'une commission (à mettre en dernier pour éviter les conflits)
        Route::get('/{id}', 'show')->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Routes du Chat
    |--------------------------------------------------------------------------
    */
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat', [ChatController::class, 'store'])->name('chat.store');
    Route::get('/chat/{chat}', [ChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{chat}/message', [ChatController::class, 'sendMessage'])->name('chat.message');
    Route::put('/chat/{chat}', [ChatController::class, 'update'])->name('chat.update');
    Route::delete('/chat/{chat}', [ChatController::class, 'destroy'])->name('chat.destroy');
    Route::put('/chat/message/{message}', [ChatController::class, 'updateMessage'])->name('chat.message.update');
    Route::delete('/chat/message/{message}', [ChatController::class, 'destroyMessage'])->name('chat.message.destroy');

    Route::prefix('/conversations')->controller(ConversationController::class)->name('conversations.')->group(function () {
        // Liste des conversations
        Route::get('/', 'index')->name('index');

        // Créer une nouvelle conversation
        Route::post('/', 'store')->name('store');

        // Afficher une conversation
        Route::get('/{conversation}', 'show')->name('show');

        // Envoyer un message
        Route::post('/{conversation}/message', 'sendMessage')->name('message');

        // Marquer comme lu
        Route::post('/{conversation}/mark-read', 'markAsRead')->name('mark-read');

        // Mettre à jour le statut "en train d'écrire"
        Route::post('/{conversation}/typing', 'updateTyping')->name('typing');

        // Fermer une conversation
        Route::post('/{conversation}/close', 'close')->name('close');

        // Supprimer une conversation (admin uniquement)
        Route::delete('/{conversation}', 'destroy')->name('destroy');
    });

    Route::prefix('/client-dossiers')->controller(\App\Http\Controllers\ClientDossierController::class)->name('client-dossiers.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{dossier}', 'show')->name('show');
        Route::get('/{dossier}/edit', 'edit')->name('edit');
        Route::put('/{dossier}', 'update')->name('update');
        Route::delete('/{dossier}', 'destroy')->name('destroy');
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
