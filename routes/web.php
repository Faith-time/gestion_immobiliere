<?php

use App\Http\Controllers\AppartementController;
use App\Http\Controllers\AuthentificationController;
use App\Http\Controllers\AvisRetardController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\CategorieController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ClientDocumentController;
use App\Http\Controllers\ClientDossierController;
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
| Routes Publiques (Accès Sans Authentification)
|--------------------------------------------------------------------------
*/

// Routes d'authentification (formulaires et actions)
Route::get('auth/showLogin', [AuthentificationController::class, 'showLogin'])->name('login');
Route::get('auth/showRegister', [AuthentificationController::class, 'showRegister'])->name('register');

Route::prefix('/auth')->controller(AuthentificationController::class)->name('auth.')->group(function () {
    Route::post('/login', 'login')->name('login');
    Route::post('/register', 'register')->name('register');
    Route::post('/logout', 'logout')->name('logout');
});

/*
|--------------------------------------------------------------------------
| Routes avec Authentification Optionnelle (Visiteurs Autorisés)
|--------------------------------------------------------------------------
| Ces routes sont accessibles aux visiteurs non inscrits
*/

Route::middleware('optional.auth')->group(function () {

    // Page d'accueil - Accessible aux visiteurs
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // Catalogue des biens - Accessible aux visiteurs
    Route::get('/biens/catalogue', [BienController::class, 'catalogue'])->name('biens.catalogue');

    // Détails d'un bien - Accessible aux visiteurs
    Route::get('/biens/{bien}', [BienController::class, 'show'])
        ->where('bien', '[0-9]+')
        ->name('biens.show');
});

/*
|--------------------------------------------------------------------------
| Routes Protégées (Authentification Obligatoire)
|--------------------------------------------------------------------------
| Toutes ces routes nécessitent un compte utilisateur authentifié
*/

Route::middleware('authenticate')->group(function () {

    Route::prefix('/biens')->controller(BienController::class)->name('biens.')->group(function () {
        // ✅ Routes statiques protégées
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');  // ✅ Maintenant protégée
        Route::post('/', 'store')->name('store');

        // Routes avec paramètres
        Route::get('/{bien}/edit', 'edit')->name('edit');
        Route::put('/{bien}', 'update')->name('update');
        Route::delete('/{bien}', 'destroy')->name('destroy');

        // Validation admin
        Route::post('/{bien}/valider', 'valider')->name('valider');
        Route::post('/{bien}/rejeter', 'rejeter')->name('rejeter');

        // Gestion des mandats
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



    // Debug signature
    Route::get('/debug/signature/{bien}', [BienController::class, 'debugSignatureData'])->name('debug.signature');

    /*
    |--------------------------------------------------------------------------
    | Routes des Appartements (Sous-ensembles de biens)
    |--------------------------------------------------------------------------
    */
    Route::prefix('/biens/{bien}/appartements')
        ->controller(AppartementController::class)
        ->name('appartements.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{appartement}/edit', 'edit')->name('edit');
            Route::put('/{appartement}', 'update')->name('update');
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

    // Dashboard propriétaire
    Route::get('/dashboard/proprietaire/{proprietaireId?}', [BienController::class, 'dashboardProprietaire'])
        ->name('dashboard.proprietaire');

    // Détails d'un bien pour le propriétaire
    Route::get('/biens/{bien}/details-proprietaire', [BienController::class, 'detailsBienProprietaire'])
        ->name('biens.details-proprietaire');

    /*
    |--------------------------------------------------------------------------
    | Routes des Catégories (Admin uniquement)
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
    | Routes des Utilisateurs (Admin uniquement)
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

// ✅ ROUTES DES VISITES
    Route::prefix('/visites')->controller(VisiteController::class)->name('visites.')->group(function () {

        Route::post('/action-confirmer', 'confirmer')->name('action.confirmer');
        Route::post('/action-rejeter', 'rejeter')->name('action.rejeter');
        Route::post('/action-marquer-effectuee', 'marquerEffectuee')->name('action.marquer-effectuee');

        // Routes standards (après les routes d'actions)
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{visite}', 'show')->name('show');
        Route::get('/{visite}/edit', 'edit')->name('edit');
        Route::put('/{visite}', 'update')->name('update');
        Route::delete('/{visite}', 'destroy')->name('destroy');
        Route::post('/{visite}/annuler', 'annuler')->name('annuler');
    });

    Route::prefix('/reservations')
        ->controller(ReservationController::class)
        ->name('reservations.')
        ->group(function () {
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
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::post('/{vente}/valider-paiement', 'validerPaiementVente')->name('valider-paiement');
        Route::get('/{vente}', 'show')->name('show');
        Route::get('/{vente}/edit', 'edit')->name('edit');
        Route::put('/{vente}', 'update')->name('update');
        Route::delete('/{vente}', 'destroy')->name('destroy');

        Route::get('/{vente}/signature', 'showSignaturePage')->name('signature');
        Route::post('/{vente}/sign-vendeur', 'signByVendeur')->name('sign-vendeur');
        Route::post('/{vente}/sign-acheteur', 'signByAcheteur')->name('sign-acheteur');
        Route::delete('/{vente}/cancel-signature/{type}', 'cancelSignature')
            ->name('cancel-signature')
            ->where('type', 'vendeur|acheteur');

        // Contrats PDF
        Route::get('/{vente}/download-contract', 'downloadContract')->name('download-contract');
        Route::get('/{vente}/preview-contract', 'previewContract')->name('preview-contract');
    });

    Route::prefix('/locations')->controller(LocationController::class)->name('locations.')->group(function () {
        Route::get('/mes-loyers', 'mesLoyers')->name('mes-loyers');

        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{location}', 'show')->name('show');
        Route::get('/{location}/edit', 'edit')->name('edit');
        Route::put('/{location}', 'update')->name('update');
        Route::delete('/{location}', 'destroy')->name('destroy');

        // ✅ SIGNATURES - AJOUTEZ CES LIGNES
        Route::get('/{location}/signature', 'showSignaturePage')->name('signature');
        Route::post('/{location}/sign-locataire', 'signByLocataire')->name('sign-locataire');
        Route::post('/{location}/sign-bailleur', 'signByBailleur')->name('sign-bailleur');
        Route::delete('/{location}/cancel-signature/{type}', 'cancelSignature')
            ->name('cancel-signature')
            ->where('type', 'bailleur|locataire');

        // Contrats PDF
        Route::get('/{location}/download-contract', 'downloadContract')->name('download-contract');
        Route::get('/{location}/preview-contract', 'previewContract')->name('preview-contract');

        // Gestion des loyers mensuels
        Route::post('/{location}/payer-loyer', 'payerLoyer')->name('payer-loyer');
        Route::get('/{location}/loyer/{mois}', 'detailsLoyerMois')->name('loyer.details');

        // Actions admin
        Route::post('/{location}/valider-paiement', 'validerPaiementLocation')->name('valider-paiement');
        Route::post('/{location}/terminer', 'terminerLocation')->name('terminer');

        // Test notifications
        Route::post('/{location}/test-notifications', 'testNotifications')->name('test.notifications');
    });
    /*
    |--------------------------------------------------------------------------
    | Routes des Documents Clients (BLOQUÉ pour visiteurs)
    |--------------------------------------------------------------------------
    */
    Route::prefix('/client-documents')
        ->controller(ClientDocumentController::class)
        ->name('client-documents.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{document}', 'show')->name('show');
            Route::put('/{document}', 'update')->name('update');
            Route::delete('/{document}', 'destroy')->name('destroy');
            Route::get('/{document}/download', 'download')->name('download');

            // Actions admin
            Route::post('/{document}/valider', 'valider')->name('valider');
            Route::post('/{document}/refuser', 'refuser')->name('refuser');
        });

    /*
    |--------------------------------------------------------------------------
    | Routes des Dossiers Clients (BLOQUÉ pour visiteurs)
    |--------------------------------------------------------------------------
    */
    Route::prefix('/client-dossiers')
        ->controller(ClientDossierController::class)
        ->name('client-dossiers.')
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{dossier}', 'show')->name('show');
            Route::get('/{dossier}/edit', 'edit')->name('edit');
            Route::put('/{dossier}', 'update')->name('update');
            Route::delete('/{dossier}', 'destroy')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Routes des Paiements
    |--------------------------------------------------------------------------
    */
    Route::prefix('/paiement')->controller(PaiementController::class)->name('paiement.')->group(function () {
        // CRUD
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{paiement}', 'show')->name('show');
        Route::put('/{paiement}', 'update')->name('update');
        Route::delete('/{paiement}', 'destroy')->name('destroy');

        // Actions spécifiques
        Route::post('/{paiement}/confirmer-loyer', 'confirmerPaiementLoyer')->name('confirmer-loyer');
        Route::post('/{paiement}/renvoyer-document', 'renvoyerDocument')->name('renvoyer-document');

        // Initiation de paiement
        Route::get('/initier/{id}/{paiement_id}', 'showInitierPaiement')->name('initier.show');
        Route::post('/initier', 'initier')->name('initier');

        // Pages de résultat
        Route::get('/succes/{paiement}', 'showSucces')->name('succes');
        Route::get('/retour/{paiement}', 'retour')->name('retour');
        Route::get('/erreur', 'showErreur')->name('erreur');

        // Utilitaires
        Route::get('/options', 'getPaiementOptions')->name('options');
        Route::get('/{id}/info-fractionnement', 'getInfoPaiementFractionne')->name('info.fractionnement');
    });

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

    /*
    |--------------------------------------------------------------------------
    | Dashboards
    |--------------------------------------------------------------------------
    */
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

        // Détails (à mettre en dernier pour éviter les conflits)
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

    /*
    |--------------------------------------------------------------------------
    | Routes des Conversations
    |--------------------------------------------------------------------------
    */
    Route::prefix('/conversations')->controller(ConversationController::class)->name('conversations.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{conversation}', 'show')->name('show');
        Route::post('/{conversation}/message', 'sendMessage')->name('message');
        Route::post('/{conversation}/mark-read', 'markAsRead')->name('mark-read');
        Route::post('/{conversation}/typing', 'updateTyping')->name('typing');
        Route::post('/{conversation}/close', 'close')->name('close');
        Route::delete('/{conversation}', 'destroy')->name('destroy');
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
