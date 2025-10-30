<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
use App\Models\Bien;
use App\Models\Location;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Services\ContractNotificationService;
use App\Services\ContractPdfService;
use App\Services\ContractElectronicSignatureService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class LocationController extends Controller
{
    protected $contractPdfService;
    protected $contractSignatureService;
    protected $contractNotificationService;

    public function __construct(
        ContractPdfService $contractPdfService,
        ContractElectronicSignatureService $contractSignatureService,
        ContractNotificationService $contractNotificationService
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractSignatureService = $contractSignatureService;
        $this->contractNotificationService = $contractNotificationService;
    }

    public function create(Request $request)
    {
        $bien = null;
        $reservation = null;
        $appartements = collect();

        if ($request->has('reservation_id')) {
            // ✅ CHARGER LA RÉSERVATION AVEC TOUTES LES RELATIONS
            $reservation = Reservation::with([
                'bien.category',
                'bien.proprietaire',
                'bien.mandat',
                'bien.appartements' => function($query) {
                    $query->where('statut', 'disponible')->orderBy('etage');
                },
                'appartement', // ✅ Charger l'appartement spécifique si réservation d'appartement
                'client'
            ])->find($request->reservation_id);

            if (!$reservation) {
                return redirect()->route('home')
                    ->with('error', '❌ Réservation introuvable.');
            }

            // ✅ RÉCUPÉRER LE BIEN (via réservation)
            $bien = $reservation->bien;

            if (!$bien) {
                return redirect()->route('home')
                    ->with('error', '❌ Le bien associé à cette réservation est introuvable.');
            }

            // ✅ VÉRIFIER LE PRIX DU BIEN
            if (!$bien->price || $bien->price <= 0) {
                return redirect()->route('home')
                    ->with('error', '❌ Le prix du bien n\'est pas défini. Veuillez contacter l\'administrateur.');
            }

            if ($reservation->client_id !== auth()->id()) {
                return redirect()->route('home')
                    ->with('error', '❌ Cette réservation ne vous appartient pas.');
            }

            $statutNormalise = str_replace('é', 'e', strtolower($reservation->statut));

            if ($statutNormalise !== 'confirmee') {
                return redirect()->route('reservations.show', $reservation->id)
                    ->with('error', '❌ La réservation doit être confirmée avant de créer une location.');
            }

            if ($bien->proprietaire_id === auth()->id()) {
                return redirect()->route('biens.show', $bien->id)
                    ->with('error', '❌ Vous ne pouvez pas louer votre propre bien.');
            }

            $locationExistante = Location::where('reservation_id', $reservation->id)
                ->whereIn('statut', ['active', 'finalisée', 'en_retard'])
                ->first();

            if ($locationExistante) {
                return redirect()->route('locations.show', $locationExistante->id)
                    ->with('info', 'Une location existe déjà pour cette réservation.');
            }

            // ✅ CHARGER LES APPARTEMENTS DISPONIBLES
            // Si la réservation concerne un appartement spécifique, ne charger que celui-là
            if ($reservation->appartement_id && $reservation->appartement) {
                $appartements = collect([$reservation->appartement]);
            } else {
                // Sinon, charger tous les appartements disponibles du bien
                $appartements = $bien->appartements;
            }

        } elseif ($request->has('bien_id')) {
            $bien = Bien::with([
                'category',
                'proprietaire',
                'mandat',
                'appartements' => function($query) {
                    $query->where('statut', 'disponible')->orderBy('etage');
                }
            ])->find($request->bien_id);

            if (!$bien) {
                return redirect()->route('home')
                    ->with('error', '❌ Bien introuvable.');
            }

            if (!$bien->price || $bien->price <= 0) {
                return redirect()->route('home')
                    ->with('error', '❌ Le prix du bien n\'est pas défini.');
            }

            if ($bien->proprietaire_id === auth()->id()) {
                return redirect()->route('biens.show', $bien->id)
                    ->with('error', '❌ Vous ne pouvez pas louer votre propre bien.');
            }

            $reservation = Reservation::where('bien_id', $bien->id)
                ->where('client_id', auth()->id())
                ->whereRaw("LOWER(REPLACE(statut, 'é', 'e')) = 'confirmee'")
                ->first();

            if (!$reservation) {
                return redirect()->route('reservations.create', $bien->id)
                    ->with('error', '❌ Vous devez d\'abord faire une réservation confirmée.');
            }

            $appartements = $bien->appartements;

        } else {
            return redirect()->route('home')
                ->with('error', '❌ Paramètre manquant : reservation_id ou bien_id requis.');
        }

        return Inertia::render('Locations/Create', [
            'bien' => $bien,
            'reservation' => $reservation,
            'appartements' => $appartements,
            'isImmeuble' => $bien->isImmeuble(),
            'typesContrat' => Location::getTypesContrat(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'appartement_id' => 'nullable|exists:appartements,id',
            'date_debut' => 'required|date|after:today',
            'duree_mois' => 'required|integer|min:1|max:120',
            'type_contrat' => 'required|in:bail_classique,bail_meuble,bail_commercial',
        ]);

        $user = Auth::user();

        // ✅ CHARGER LA RÉSERVATION AVEC TOUTES LES RELATIONS
        $reservation = Reservation::with([
            'bien.mandat',
            'bien.proprietaire',
            'bien.category',
            'bien.appartements',
            'appartement',
            'client'
        ])->find($request->reservation_id);

        if (!$reservation) {
            return back()->withErrors(['message' => '❌ Réservation introuvable.']);
        }

        // ✅ RÉCUPÉRER LE BIEN
        $bien = $reservation->bien;

        if (!$bien) {
            return back()->withErrors(['message' => '❌ Le bien associé à cette réservation est introuvable.']);
        }

        // ✅ VÉRIFIER LE PRIX
        if (!$bien->price || $bien->price <= 0) {
            return back()->withErrors(['message' => '❌ Le prix du bien n\'est pas défini.']);
        }

        if ($reservation->client_id !== $user->id) {
            return back()->withErrors(['message' => '❌ Cette réservation ne vous appartient pas.']);
        }

        $statutNormalise = str_replace('é', 'e', strtolower($reservation->statut));

        if ($statutNormalise !== 'confirmee') {
            return back()->withErrors([
                'message' => '❌ La réservation doit être confirmée (statut : ' . $reservation->statut . ')'
            ]);
        }

        if ($bien->proprietaire_id === $user->id) {
            return back()->withErrors([
                'message' => '❌ Vous ne pouvez pas louer votre propre bien.'
            ]);
        }

        // Vérifier la durée
        $typeInfo = Location::getTypesContrat()[$request->type_contrat];
        if ($request->duree_mois < $typeInfo['duree_min']) {
            return back()->withErrors([
                'duree_mois' => "❌ Durée minimale : {$typeInfo['duree_min']} mois pour un {$typeInfo['label']}."
            ]);
        }

        $locationExistante = Location::where('reservation_id', $request->reservation_id)
            ->whereIn('statut', ['active', 'finalisée', 'en_retard'])
            ->first();

        if ($locationExistante) {
            return redirect()->route('paiement.initier.show', [
                'id' => $locationExistante->id,
                'paiement_id' => $locationExistante->paiement->id
            ])->with('warning', 'Une location existe déjà.');
        }

        if (!$bien->mandat || $bien->mandat->type_mandat !== 'gestion_locative' || $bien->mandat->statut !== 'actif') {
            return back()->withErrors(['message' => '❌ Ce bien n\'est pas disponible à la location.']);
        }

        // ✅ VALIDATION DE L'APPARTEMENT
        $appartement = null;

        // Si la réservation concerne un appartement spécifique
        if ($reservation->appartement_id) {
            $appartement = $reservation->appartement;

            if (!$appartement) {
                return back()->withErrors([
                    'message' => '❌ L\'appartement réservé est introuvable.'
                ]);
            }

            if ($appartement->bien_id !== $bien->id) {
                return back()->withErrors([
                    'message' => '❌ L\'appartement ne correspond pas au bien réservé.'
                ]);
            }

            if (!$appartement->isDisponible()) {
                return back()->withErrors([
                    'message' => '❌ L\'appartement n\'est plus disponible.'
                ]);
            }
        }
        // Si un appartement_id est fourni dans la requête (changement)
        elseif ($request->appartement_id) {
            $appartement = Appartement::find($request->appartement_id);

            if (!$appartement || $appartement->bien_id !== $bien->id) {
                return back()->withErrors([
                    'appartement_id' => '❌ Appartement invalide pour ce bien.'
                ]);
            }

            if (!$appartement->isDisponible()) {
                return back()->withErrors([
                    'appartement_id' => '❌ Cet appartement n\'est pas disponible.'
                ]);
            }
        }
        // Si c'est un immeuble mais aucun appartement sélectionné
        elseif ($bien->isImmeuble()) {
            return back()->withErrors([
                'appartement_id' => '❌ Vous devez sélectionner un appartement.'
            ]);
        }

        try {
            $location = DB::transaction(function () use ($request, $user, $bien, $appartement, $reservation) {
                $dateDebut = Carbon::parse($request->date_debut);
                $dateFin = $dateDebut->copy()->addMonths((int) $request->duree_mois);

                Log::info('💰 Calcul du loyer - AVANT division', [
                    'bien_id' => $bien->id,
                    'prix_bien' => $bien->price,
                    'est_immeuble' => $bien->isImmeuble(),
                    'a_appartement' => $appartement ? 'OUI' : 'NON',
                    'appartement_numero' => $appartement ? $appartement->numero : 'N/A'
                ]);

                $loyerMensuel = $bien->price;

                Log::info('💰 Calcul du loyer', [
                    'bien_id' => $bien->id,
                    'prix_bien' => $bien->price,
                    'loyer_mensuel' => $loyerMensuel,
                    'appartement' => $appartement ? $appartement->numero : 'Bien complet'
                ]);

                if (!$loyerMensuel || $loyerMensuel <= 0) {
                    throw new \Exception('Le montant du loyer ne peut pas être calculé.');
                }

                Log::info('💰 Calcul du loyer - APRÈS calcul', [
                    'loyer_mensuel_final' => $loyerMensuel,
                    'montant_paiement_initial' => $loyerMensuel * 2
                ]);

                if (!$loyerMensuel || $loyerMensuel <= 0) {
                    throw new \Exception('Le montant du loyer ne peut pas être calculé.');
                }

                // ✅ CRÉER LA LOCATION
                $location = Location::create([
                    'reservation_id' => $reservation->id,
                    'client_id' => $user->id,
                    'loyer_mensuel' => $loyerMensuel,
                    'type_contrat' => $request->type_contrat,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'statut' => 'en_attente_paiement',
                    'signature_status' => 'non_signe',
                ]);

                // ✅ MARQUER L'APPARTEMENT COMME RÉSERVÉ
                if ($appartement) {
                    $appartement->update(['statut' => 'reserve']);

                    Log::info('🏠 Appartement réservé pour location', [
                        'appartement_id' => $appartement->id,
                        'numero' => $appartement->numero,
                        'location_id' => $location->id
                    ]);
                }

                // ✅ GÉNÉRER LE PDF
                try {
                    $pdfPath = $this->contractPdfService->generatePdf($location, 'location');
                    if ($pdfPath) {
                        Log::info('✅ Contrat PDF généré', [
                            'location_id' => $location->id,
                            'pdf_path' => $pdfPath
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Erreur génération PDF', [
                        'location_id' => $location->id,
                        'error' => $e->getMessage()
                    ]);
                }

                $montantPaiementInitial = $loyerMensuel * 2;

                Log::info('💰 Paiement location créé', [
                    'loyer_mensuel' => $loyerMensuel,
                    'montant_initial' => $montantPaiementInitial,
                    'detail' => 'Caution + 1er mois',
                    'appartement' => $appartement ? $appartement->numero : 'Bien complet'
                ]);

                // ✅ CRÉER LE PAIEMENT
                $paiement = Paiement::create([
                    'type' => 'location',
                    'location_id' => $location->id,
                    'montant_total' => $montantPaiementInitial,
                    'montant_paye' => 0,
                    'montant_restant' => $montantPaiementInitial,
                    'commission_agence' => $montantPaiementInitial * 0.05,
                    'mode_paiement' => 'orange_money',
                    'transaction_id' => 'LOC_' . $location->id . '_' . time(),
                    'statut' => 'en_attente',
                    'date_transaction' => now(),
                ]);

                Log::info('✅ Location et paiement créés', [
                    'location_id' => $location->id,
                    'paiement_id' => $paiement->id,
                    'montant' => $montantPaiementInitial
                ]);

                return $location;
            });

            $paiement = $location->paiement;

            $message = '✅ Location créée avec succès !';
            if ($appartement) {
                $message .= ' Appartement ' . $appartement->numero . ' réservé.';
            }
            $message .= ' Veuillez procéder au paiement.';

            return redirect()->route('paiement.initier.show', [
                'id' => $location->id,
                'paiement_id' => $paiement->id
            ])->with('success', $message);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création location', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['message' => 'Erreur : ' . $e->getMessage()]);
        }
    }
    public function validerPaiementLocation(Location $location)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403);
        }

        if ($location->statut !== 'en_attente_paiement') {
            return back()->with('error', 'Cette location n\'est pas en attente de paiement');
        }

        $paiement = $location->paiement;

        if (!$paiement || $paiement->statut === 'valide') {
            return back()->with('error', 'Paiement introuvable ou déjà validé');
        }

        try {
            DB::transaction(function () use ($location, $paiement) {
                // Mettre à jour le paiement
                $paiement->update([
                    'statut' => 'reussi',
                    'montant_paye' => $paiement->montant_total,
                    'montant_restant' => 0,
                ]);

                // Mettre à jour la location
                $location->update([
                    'statut' => 'active',
                ]);

                // ✅ MARQUER L'APPARTEMENT COMME LOUÉ
                if ($location->appartement_id && $location->appartement) {
                    $location->appartement->update(['statut' => 'loue']);

                    Log::info('🏠 Appartement marqué comme loué', [
                        'appartement_id' => $location->appartement->id,
                        'numero' => $location->appartement->numero,
                        'location_id' => $location->id
                    ]);
                }

                // ✅ METTRE À JOUR LE STATUT GLOBAL DU BIEN
                if ($location->bien) {
                    $location->bien->updateStatutGlobal();

                    Log::info('🏢 Statut du bien mis à jour', [
                        'bien_id' => $location->bien->id,
                        'nouveau_statut' => $location->bien->fresh()->status
                    ]);
                }

                // Mettre à jour la réservation
                $location->reservation->update([
                    'statut' => 'finalisée',
                ]);
            });

            return redirect()->route('locations.show', $location->id)
                ->with('success', 'Paiement validé avec succès ! Location activée.');

        } catch (\Exception $e) {
            Log::error('❌ Erreur validation paiement location', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la validation du paiement');
        }
    }

    /**
     * Terminer une location et libérer l'appartement
     */
    public function terminerLocation(Location $location)
    {
        $user = auth()->user();

        if ($location->bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        if ($location->statut === 'terminee') {
            return back()->with('info', 'Cette location est déjà terminée');
        }

        try {
            DB::transaction(function () use ($location) {
                $location->update(['statut' => 'terminee']);

                // ✅ LIBÉRER L'APPARTEMENT
                if ($location->appartement_id && $location->appartement) {
                    $location->appartement->update(['statut' => 'disponible']);

                    Log::info('🏠 Appartement libéré', [
                        'appartement_id' => $location->appartement->id,
                        'numero' => $location->appartement->numero,
                        'location_id' => $location->id
                    ]);
                }

                // ✅ METTRE À JOUR LE STATUT GLOBAL DU BIEN
                if ($location->bien) {
                    $location->bien->updateStatutGlobal();

                    Log::info('🏢 Statut du bien mis à jour après fin de location', [
                        'bien_id' => $location->bien->id,
                        'nouveau_statut' => $location->bien->fresh()->status
                    ]);
                }
            });

            return back()->with('success', 'Location terminée avec succès. Appartement libéré.');

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la fin de location', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la terminaison de la location');
        }
    }


    public function show(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé à consulter cette location.');
        }

        $location->load(['bien.category', 'client', 'bien.proprietaire']);
        $signatureStats = $this->contractSignatureService->getSignatureStats($location, 'location');

        return Inertia::render('Locations/Show', [
            'location' => [
                ...$location->toArray(),
                'type_contrat_info' => $location->getTypeContratInfo(),
            ],
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isLocataire' => $location->client_id === $user->id,
            'isBailleur' => $location->bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    /**
     * Télécharger le contrat PDF
     */
    public function downloadContract(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        return $this->contractPdfService->downloadPdf($location, 'location');
    }

    /**
     * Prévisualiser le contrat PDF
     */
    public function previewContract(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        return $this->contractPdfService->previewPdf($location, 'location');
    }

    /**
     * Afficher la page de signature
     */
    public function showSignaturePage(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        $location->load(['bien.category', 'client', 'bien.proprietaire']);

        return Inertia::render('Contrats/SignatureLocation', [
            'location' => [
                ...$location->toArray(),
                'type_contrat_info' => $location->getTypeContratInfo(),
            ],
            'signatureStats' => $this->contractSignatureService->getSignatureStats($location, 'location'),
            'isLocataire' => $location->client_id === $user->id,
            'isBailleur' => $location->bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    /**
     * Signature par le bailleur
     */
    public function signByBailleur(Request $request, Location $location)
    {
        $user = Auth::user();

        if ($location->bien->proprietaire_id !== $user->id && !$user->hasRole('admin')) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        if (!$location->canBeSignedByBailleur()) {
            return response()->json(['error' => 'Cette location ne peut plus être signée'], 422);
        }

        $request->validate([
            'signature' => 'required|string'
        ]);

        $result = $this->contractSignatureService->signContract(
            $location,
            'bailleur',
            $request->signature,
            $request->ip()
        );

        if ($result['success']) {
            // Régénérer le PDF avec la nouvelle signature
            $this->contractPdfService->regeneratePdf($location, 'location');

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'signatureStats' => $this->contractSignatureService->getSignatureStats($location, 'location')
            ]);
        }

        return response()->json(['error' => $result['message']], 422);
    }

    /**
     * Signature par le locataire
     */
    public function signByLocataire(Request $request, Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id) {
            return response()->json(['error' => 'Non autorisé'], 403);
        }

        if (!$location->canBeSignedByLocataire()) {
            return response()->json(['error' => 'Cette location ne peut plus être signée'], 422);
        }

        $request->validate([
            'signature' => 'required|string'
        ]);

        $result = $this->contractSignatureService->signContract(
            $location,
            'locataire',
            $request->signature,
            $request->ip()
        );

        if ($result['success']) {
            // Régénérer le PDF avec la nouvelle signature
            $this->contractPdfService->regeneratePdf($location, 'location');

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'signatureStats' => $this->contractSignatureService->getSignatureStats($location, 'location')
            ]);
        }

        return response()->json(['error' => $result['message']], 422);
    }

    /**
     * Annuler une signature
     */
    public function cancelSignature(Location $location, string $signatoryType)
    {
        $user = Auth::user();

        if (($signatoryType === 'bailleur' && $location->bien->proprietaire_id !== $user->id) ||
            ($signatoryType === 'locataire' && $location->client_id !== $user->id)) {

            if (!$user->hasRole('admin')) {
                return response()->json(['error' => 'Non autorisé'], 403);
            }
        }

        $result = $this->contractSignatureService->cancelSignature($location, $signatoryType);

        if ($result['success']) {
            // Régénérer le PDF sans cette signature
            $this->contractPdfService->regeneratePdf($location, 'location');

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'signatureStats' => $this->contractSignatureService->getSignatureStats($location, 'location')
            ]);
        }

        return response()->json(['error' => $result['message']], 422);
    }

// ✅ CORRECTION DE LA MÉTHODE canUserSign (ligne ~492)

    private function canUserSign(Location $location, $user)
    {
        // Vérifier si le locataire peut signer
        if ($location->client_id === $user->id && $location->canBeSignedByLocataire()) {
            return true;
        }

        // ✅ CORRECTION : Charger le bien avant d'accéder à proprietaire_id
        // Charger la relation 'bien' via 'reservation.bien' si elle n'est pas déjà chargée
        if (!$location->relationLoaded('bien')) {
            $location->load('reservation.bien');
        }

        // ✅ Vérifier que le bien existe avant d'accéder à proprietaire_id
        $bien = $location->bien ?? $location->reservation?->bien;

        if ($bien && $bien->proprietaire_id === $user->id && $location->canBeSignedByBailleur()) {
            return true;
        }

        return false;
    }

// ✅ CORRECTION DE LA MÉTHODE getUserRoleInLocation (ligne ~501)

    private function getUserRoleInLocation(Location $location, $user)
    {
        // Si c'est le client/locataire
        if ($location->client_id === $user->id) {
            return 'locataire';
        }

        // Si c'est le propriétaire/bailleur
        $bien = $location->reservation?->bien;
        if ($bien && $bien->proprietaire_id === $user->id) {
            return 'bailleur';
        }

        return null;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $locations = Location::query()
            ->with([
                'reservation.bien.category',
                'reservation.bien.proprietaire',
                'reservation.appartement', // ✅ Charger l'appartement si concerné
                'client'
            ])
            ->where(function($query) use ($user) {
                $query->where('client_id', $user->id)
                    ->orWhereHas('reservation.bien', function($q) use ($user) {
                        $q->where('proprietaire_id', $user->id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // ✅ Mapper les locations avec toutes les infos nécessaires
        $locationsFormatted = $locations->map(function($location) use ($user) {
            // Récupérer le bien via réservation
            $bien = $location->reservation?->bien;

            // Récupérer l'appartement si concerné
            $appartement = $location->reservation?->appartement;

            // Calculer les stats de signature
            $signatureStats = [
                'locataire_signed' => $location->isSignedByLocataire(),
                'bailleur_signed' => $location->isSignedByBailleur(),
                'fully_signed' => $location->isFullySigned(),
                'signature_status' => $location->signature_status ?? 'non_signe',
            ];

            return [
                'id' => $location->id,
                'statut' => $location->statut,
                // ✅ UTILISER loyer_mensuel AU LIEU DE bien.price
                'montant' => $location->loyer_mensuel, // ← C'EST LE LOYER RÉEL
                'date_debut' => $location->date_debut?->format('Y-m-d'),
                'date_fin' => $location->date_fin?->format('Y-m-d'),
                'type_contrat' => $location->type_contrat,
                'created_at' => $location->created_at?->format('Y-m-d H:i:s'),

                // ✅ Bien complet
                'bien' => $bien ? [
                    'id' => $bien->id,
                    'titre' => $bien->title,
                    'adresse' => $bien->address,
                    'ville' => $bien->city,
                    'prix' => $bien->price, // Prix global du bien (pas le loyer)
                    'image' => $bien->image,
                    'category' => $bien->category,
                    'proprietaire_id' => $bien->proprietaire_id,
                ] : null,

                // ✅ Appartement si concerné
                'appartement' => $appartement ? [
                    'id' => $appartement->id,
                    'numero' => $appartement->numero,
                    'etage' => $appartement->etage,
                    'statut' => $appartement->statut,
                ] : null,

                // ✅ Locataire
                'locataire' => $location->client ? [
                    'id' => $location->client->id,
                    'name' => $location->client->name,
                    'email' => $location->client->email,
                ] : null,

                // ✅ Stats de signature
                'signature_stats' => $signatureStats,

                // ✅ Rôle de l'utilisateur dans cette location
                'user_role_in_location' => $this->getUserRoleInLocation($location, $user),
            ];
        });

        return Inertia::render('Locations/Index', [
            'locations' => $locationsFormatted,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }
    // Méthodes manquantes
    public function edit(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        $location->load(['bien', 'client']);

        return Inertia::render('Locations/Edit', [
            'location' => $location,
            'typesContrat' => Location::getTypesContrat(),
        ]);
    }

    public function update(Request $request, Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'loyer_mensuel' => 'nullable|numeric|min:0',
            'statut' => 'nullable|in:active,terminee,en_retard',
        ]);

        $location->update($request->only([
            'date_debut',
            'date_fin',
            'loyer_mensuel',
            'statut'
        ]));

        return redirect()->route('locations.show', $location->id)
            ->with('success', 'Location mise à jour avec succès');
    }

    public function destroy(Location $location)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        $location->delete();

        return redirect()->route('locations.index')
            ->with('success', 'Location supprimée avec succès');
    }

    public function testNotifications(Location $location)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        try {
            $this->contractNotificationService->sendContractNotifications($location, 'location');

            return back()->with('success', 'Notifications de test envoyées avec succès');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    // Ajoutez ces méthodes dans votre LocationController.php

    /**
     * Afficher la page de gestion des loyers pour le locataire
     * Route: GET /locations/mes-loyers
     */
    public function mesLoyers()
    {
        $user = auth()->user();

        // Récupérer toutes les locations actives du locataire
        $locations = Location::with([
            'reservation.bien.category',
            'reservation.bien.proprietaire',
            'reservation.appartement'
        ])
            ->where('client_id', $user->id)
            ->whereIn('statut', ['active', 'en_retard'])
            ->orderBy('date_debut', 'desc')
            ->get();

        // Calculer les informations de paiement pour chaque location
        $locationsAvecPaiements = $locations->map(function ($location) {
            $bien = $location->reservation?->bien;
            $appartement = $location->reservation?->appartement;

            $dateDebut = Carbon::parse($location->date_debut);
            $dateFin = Carbon::parse($location->date_fin);
            $aujourdhui = Carbon::now();

            // ✅ CALCULER LES MOIS DE LOYERS (À PARTIR DU 2ÈME MOIS)
            $moisLoyers = [];

            // ✅ COMMENCER AU 2ÈME MOIS car le 1er mois est payé lors de la création
            $currentDate = $dateDebut->copy()->startOfMonth()->addMonth();

            while ($currentDate->lte($dateFin)) {
                $moisDebut = $currentDate->copy();
                $moisFin = $currentDate->copy()->endOfMonth();

                // Date d'échéance = 10 du mois en cours (pas du mois suivant)
                $dateEcheance = $currentDate->copy()->day(10);

                // ✅ Vérifier si ce mois a été payé
                $paiementEffectue = Paiement::where('location_id', $location->id)
                    ->where('type', 'loyer_mensuel')
                    ->whereYear('date_transaction', $moisDebut->year)
                    ->whereMonth('date_transaction', $moisDebut->month)
                    ->whereIn('statut', ['reussi'])
                    ->exists();

                // Déterminer le statut
                $statut = 'futur';
                $canPay = false;

                if ($aujourdhui->gte($moisDebut)) {
                    if ($paiementEffectue) {
                        $statut = 'paye';
                        $canPay = false;
                    } elseif ($aujourdhui->gt($dateEcheance)) {
                        $statut = 'en_retard';
                        $canPay = true;
                    } else {
                        $statut = 'en_cours';
                        $canPay = true;
                    }
                }

                $joursRetard = 0;
                if ($aujourdhui->gt($dateEcheance) && !$paiementEffectue) {
                    $joursRetard = $aujourdhui->diffInDays($dateEcheance);
                }

                $moisLoyers[] = [
                    'mois' => $moisDebut->format('Y-m'),
                    'mois_libelle' => $moisDebut->translatedFormat('F Y'),
                    'date_debut' => $moisDebut->format('Y-m-d'),
                    'date_fin' => $moisFin->format('Y-m-d'),
                    'date_echeance' => $dateEcheance->format('Y-m-d'),
                    'montant' => $location->loyer_mensuel,
                    'statut' => $statut,
                    'paye' => $paiementEffectue,
                    'can_pay' => $canPay,
                    'jours_retard' => $joursRetard,
                ];

                $currentDate->addMonth();
            }

            // ✅ Statistiques (ne compte que les loyers APRÈS le 1er mois)
            $totalMois = count($moisLoyers);
            $moisPayes = collect($moisLoyers)->where('paye', true)->count();
            $moisEnRetard = collect($moisLoyers)->where('statut', 'en_retard')->count();

            // ✅ Le montant total inclut le paiement initial (caution + 1er mois)
            $paiementInitial = $location->loyer_mensuel * 2; // Caution + 1er mois
            $montantLoyersRestants = $totalMois * $location->loyer_mensuel;
            $montantTotal = $paiementInitial + $montantLoyersRestants;

            $montantPaye = $paiementInitial + ($moisPayes * $location->loyer_mensuel);
            $montantRestant = $montantTotal - $montantPaye;

            return [
                'id' => $location->id,
                'bien' => $bien ? [
                    'id' => $bien->id,
                    'titre' => $bien->title,
                    'adresse' => $bien->address,
                    'ville' => $bien->city,
                    'image' => $bien->image,
                    'category' => $bien->category,
                ] : null,
                'appartement' => $appartement ? [
                    'id' => $appartement->id,
                    'numero' => $appartement->numero,
                    'etage' => $appartement->etage,
                ] : null,
                'date_debut' => $location->date_debut->format('Y-m-d'),
                'date_fin' => $location->date_fin->format('Y-m-d'),
                'loyer_mensuel' => $location->loyer_mensuel,
                'type_contrat' => $location->type_contrat,
                'type_contrat_info' => $location->getTypeContratInfo(),
                'statut' => $location->statut,
                'mois_loyers' => $moisLoyers,
                'statistiques' => [
                    'total_mois' => $totalMois, // Mois à payer (hors 1er mois)
                    'mois_payes' => $moisPayes,
                    'mois_en_retard' => $moisEnRetard,
                    'paiement_initial' => $paiementInitial, // Caution + 1er mois
                    'montant_total' => $montantTotal,
                    'montant_paye' => $montantPaye,
                    'montant_restant' => $montantRestant,
                    'taux_paiement' => $totalMois > 0 ? round(($moisPayes / $totalMois) * 100, 2) : 0,
                ],
            ];
        });

        return Inertia::render('Locations/MesLoyers', [
            'locations' => $locationsAvecPaiements,
            'user' => $user,
        ]);
    }

    /**
     * Initier le paiement d'un loyer mensuel
     * Route: POST /locations/{location}/payer-loyer
     */
    public function payerLoyer(Request $request, Location $location)
    {
        $user = auth()->user();

        // Vérifier que c'est bien le locataire
        if ($location->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à effectuer ce paiement.'
            ], 403);
        }

        $request->validate([
            'mois' => 'required|date_format:Y-m',
        ]);

        try {
            $mois = Carbon::createFromFormat('Y-m', $request->mois)->startOfMonth();
            $dateDebut = Carbon::parse($location->date_debut);
            $dateFin = Carbon::parse($location->date_fin);

            // Vérifier que le mois est dans la période de location
            if ($mois->lt($dateDebut->startOfMonth()) || $mois->gt($dateFin->startOfMonth())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce mois n\'est pas dans la période de location.'
                ], 422);
            }

            // Vérifier si le paiement existe déjà
            $paiementExistant = Paiement::where('location_id', $location->id)
                ->where('type', 'loyer_mensuel')
                ->whereYear('created_at', $mois->year)
                ->whereMonth('created_at', $mois->month)
                ->where('statut', 'reussi')
                ->first();

            if ($paiementExistant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce loyer a déjà été payé.'
                ], 422);
            }

            // Créer le paiement
            $paiement = Paiement::create([
                'type' => 'loyer_mensuel',
                'location_id' => $location->id,
                'montant_total' => $location->loyer_mensuel,
                'montant_paye' => 0,
                'montant_restant' => $location->loyer_mensuel,
                'commission_agence' => $location->loyer_mensuel * 0.05,
                'mode_paiement' => 'orange_money',
                'transaction_id' => 'LOYER_' . $location->id . '_' . $mois->format('Ym') . '_' . time(),
                'statut' => 'en_attente',
                'date_transaction' => now(),
            ]);

            Log::info('💰 Paiement loyer mensuel créé', [
                'location_id' => $location->id,
                'paiement_id' => $paiement->id,
                'mois' => $mois->format('Y-m'),
                'montant' => $location->loyer_mensuel,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement créé avec succès',
                'paiement' => $paiement,
                'redirect_url' => route('paiement.initier.show', [
                    'id' => $location->id,
                    'paiement_id' => $paiement->id
                ])
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur création paiement loyer', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du paiement : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les détails d'un mois de loyer spécifique
     * Route: GET /locations/{location}/loyer/{mois}
     */
    public function detailsLoyerMois(Location $location, $mois)
    {
        $user = auth()->user();

        if ($location->client_id !== $user->id && !$user->hasRole('admin')) {
            abort(403);
        }

        try {
            $moisDate = Carbon::createFromFormat('Y-m', $mois)->startOfMonth();
            $moisFin = $moisDate->copy()->endOfMonth();
            $dateEcheance = $moisDate->copy()->addMonth()->day(10);
            $aujourdhui = Carbon::now();

            // Vérifier si payé
            $paiement = Paiement::where('location_id', $location->id)
                ->where('type', 'loyer_mensuel')
                ->whereYear('created_at', $moisDate->year)
                ->whereMonth('created_at', $moisDate->month)
                ->first();

            $details = [
                'mois' => $mois,
                'mois_libelle' => $moisDate->translatedFormat('F Y'),
                'date_debut' => $moisDate->format('Y-m-d'),
                'date_fin' => $moisFin->format('Y-m-d'),
                'date_echeance' => $dateEcheance->format('Y-m-d'),
                'montant' => $location->loyer_mensuel,
                'paye' => $paiement && $paiement->statut === 'reussi',
                'paiement' => $paiement,
                'en_retard' => $aujourdhui->gt($dateEcheance) && (!$paiement || $paiement->statut !== 'reussi'),
                'jours_retard' => $aujourdhui->gt($dateEcheance) && (!$paiement || $paiement->statut !== 'reussi')
                    ? $aujourdhui->diffInDays($dateEcheance)
                    : 0,
            ];

            return response()->json([
                'success' => true,
                'details' => $details
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails'
            ], 500);
        }
    }
}
