<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
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
use App\Services\QuittanceService;

class LocationController extends Controller
{
    protected $contractPdfService;
    protected $contractSignatureService;
    protected $contractNotificationService;
    protected $quittanceService;

    public function __construct(
        ContractPdfService $contractPdfService,
        ContractElectronicSignatureService $contractSignatureService,
        ContractNotificationService $contractNotificationService,
        QuittanceService $quittanceService
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractSignatureService = $contractSignatureService;
        $this->contractNotificationService = $contractNotificationService;
        $this->quittanceService = $quittanceService;
    }

    /**
     * ✅ MÉTHODE CREATE CORRIGÉE
     */
    public function create(Request $request)
    {
        $user = auth()->user();

        Log::info('🔍 LocationController@create', [
            'user_id' => $user->id,
            'request_all' => $request->all()
        ]);

        // ✅ VÉRIFICATION 1 : reservation_id fourni
        if (!$request->has('reservation_id')) {
            return redirect()->route('home')
                ->with('error', '❌ Paramètre manquant : reservation_id requis.');
        }

        // ✅ CHARGER LA RÉSERVATION AVEC TOUTES LES RELATIONS
        $reservation = Reservation::with([
            'bien.category',
            'bien.proprietaire',
            'bien.mandat',
            'bien.appartements' => function($query) {
                $query->where('statut', 'disponible')->orderBy('etage');
            },
            'appartement',
            'client'
        ])->find($request->reservation_id);

        // ✅ VÉRIFICATION 2 : Réservation existe
        if (!$reservation) {
            Log::error('❌ Réservation introuvable', [
                'reservation_id' => $request->reservation_id
            ]);
            return redirect()->route('home')
                ->with('error', '❌ Réservation introuvable.');
        }

        // ✅ VÉRIFICATION 3 : Bien existe
        $bien = $reservation->bien;
        if (!$bien) {
            Log::error('❌ Bien associé introuvable', [
                'reservation_id' => $reservation->id,
                'bien_id' => $reservation->bien_id
            ]);
            return redirect()->route('home')
                ->with('error', '❌ Le bien associé à cette réservation est introuvable.');
        }

        // ✅ VÉRIFICATION 4 : Prix du bien
        if (!$bien->price || $bien->price <= 0) {
            Log::error('❌ Prix du bien invalide', [
                'bien_id' => $bien->id,
                'price' => $bien->price
            ]);
            return redirect()->route('home')
                ->with('error', '❌ Le prix du bien n\'est pas défini. Veuillez contacter l\'administrateur.');
        }

        // ✅ VÉRIFICATION 5 : Utilisateur est bien le client
        if ($reservation->client_id !== $user->id) {
            return redirect()->route('home')
                ->with('error', '❌ Cette réservation ne vous appartient pas.');
        }

        $statutNormalise = str_replace('é', 'e', strtolower($reservation->statut));
        if ($statutNormalise !== 'confirmee') {
            return redirect()->route('reservations.show', $reservation->id)
                ->with('error', '❌ La réservation doit être confirmée avant de créer une location.');
        }

        // ✅ VÉRIFICATION 7 : Pas le propriétaire
        if ($bien->proprietaire_id === $user->id) {
            return redirect()->route('biens.show', $bien->id)
                ->with('error', '❌ Vous ne pouvez pas louer votre propre bien.');
        }

        // ✅ VÉRIFICATION 8 : Pas de location existante
        $locationExistante = Location::where('reservation_id', $reservation->id)
            ->whereIn('statut', ['active', 'finalisée', 'en_retard'])
            ->first();

        if ($locationExistante) {
            return redirect()->route('locations.show', $locationExistante->id)
                ->with('info', 'Une location existe déjà pour cette réservation.');
        }

        // ✅ Charger les appartements disponibles
        $appartements = collect();
        if ($reservation->appartement_id && $reservation->appartement) {
            $appartements = collect([$reservation->appartement]);
        } else {
            $appartements = $bien->appartements;
        }

        $bienData = $bien->toArray();
        $bienData['image'] = $bien->images->first() ? $bien->images->first()->chemin_image : null;
        $bienData['images'] = $bien->images->map(function($img) {
            return [
                'id' => $img->id,
                'url' => $img->chemin_image,
                'libelle' => $img->libelle
            ];
        });


        return Inertia::render('Locations/Create', [
            'bien' => $bienData,
            'reservation' => $reservation,
            'appartements' => $appartements,
            'isImmeuble' => $bien->isImmeuble(),
            'typesContrat' => Location::getTypesContrat(),
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $locations = Location::query()
            ->with([
                'reservation.bien.category',
                'reservation.bien.proprietaire',
                'reservation.bien.appartements',
                'reservation.appartement',
                'client',
                'paiement'
            ])
            ->where(function($query) use ($user) {
                $query->where('client_id', $user->id)
                    ->orWhereHas('reservation.bien', function($q) use ($user) {
                        $q->where('proprietaire_id', $user->id);
                    });
            })
            ->orderBy('created_at', 'desc')
            ->get();

        Log::info('📊 Locations trouvées', [
            'user_id' => $user->id,
            'count' => $locations->count()
        ]);

        $locationsFormatted = $locations->map(function($location) use ($user) {

            $bien = $location->reservation?->bien;

            if (!$bien) {
                Log::warning('⚠️ Location sans bien', [
                    'location_id' => $location->id
                ]);
                return null;
            }

            $appartement = $location->reservation?->appartement;

            $signatureStats = [
                'locataire_signed' => $location->isSignedByLocataire(),
                'bailleur_signed' => $location->isSignedByBailleur(),
                'fully_signed' => $location->isFullySigned(),
                'signature_status' => $location->signature_status ?? 'non_signe',
            ];

            $premiereImage = $bien->images->first();

            return [
                'id' => $location->id,
                'statut' => $location->statut,
                'montant' => $location->loyer_mensuel,
                'date_debut' => $location->date_debut?->format('Y-m-d'),
                'date_fin' => $location->date_fin?->format('Y-m-d'),
                'type_contrat' => $location->type_contrat,
                'created_at' => $location->created_at?->format('Y-m-d H:i:s'),

                'bien' => [
                    'id' => $bien->id,
                    'titre' => $bien->title,
                    'adresse' => $bien->address,
                    'ville' => $bien->city,
                    'prix' => $bien->price,
                    'image' => $bien->images->first()
                        ? asset('storage/' . $bien->images->first()->chemin_image)
                        : null,
                    'proprietaire_id' => $bien->proprietaire_id,
                ],

                'appartement' => $appartement ? [
                    'id' => $appartement->id,
                    'numero' => $appartement->numero,
                    'etage' => $appartement->etage,
                    'statut' => $appartement->statut,
                ] : null,

                'locataire' => $location->client ? [
                    'id' => $location->client->id,
                    'name' => $location->client->name,
                    'email' => $location->client->email,
                ] : null,

                'signature_stats' => $signatureStats,
                'user_role_in_location' => $this->getUserRoleInLocation($location, $user),
            ];
        })
            ->filter() // ✅ RETIRER LES NULL
            ->values(); // ✅ RÉINDEXER

        Log::info('✅ Locations formatées', [
            'count' => $locationsFormatted->count()
        ]);

        return Inertia::render('Locations/Index', [
            'locations' => $locationsFormatted,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    private function getUserRoleInLocation(Location $location, $user)
    {
        if ($location->client_id === $user->id) {
            return 'locataire';
        }

        $bien = $location->reservation?->bien;
        if ($bien && $bien->proprietaire_id === $user->id) {
            return 'bailleur';
        }

        return null;
    }

    public function show(Location $location)
    {
        $user = Auth::user();

        $location->load([
            'reservation.bien.category',
            'reservation.bien.proprietaire',
            'reservation.appartement',
            'client',
            'paiement'
        ]);

        $bien = $location->reservation?->bien;
        if (!$bien) {
            return redirect()->route('locations.index')
                ->with('error', '❌ Le bien associé à cette location est introuvable.');
        }

        if ($location->client_id !== $user->id &&
            $bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé à consulter cette location.');
        }

        $signatureStats = $this->contractSignatureService->getSignatureStats($location, 'location');
        $transactionStatus = $location->getTransactionStatus();

        return Inertia::render('Locations/Show', [
            'location' => [
                ...$location->toArray(),
                'type_contrat_info' => $location->getTypeContratInfo(),
                'bien' => $bien,
            ],
            'signatureStats' => $signatureStats,
            'transactionStatus' => $transactionStatus,
            'userRoles' => $user->roles->pluck('name'),
            'isLocataire' => $location->client_id === $user->id,
            'isBailleur' => $bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }


    public function initierPaiementLocation(Location $location)
    {
        $user = auth()->user();

        if ($location->client_id !== $user->id && !$user->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        // ✅ CONTRÔLE 1: Vérifier si le paiement initial est déjà complet
        $paiementInitialComplet = Paiement::where('location_id', $location->id)
            ->where('type', 'location') // Paiement initial (pas loyer mensuel)
            ->where('statut', 'reussi')
            ->where('montant_restant', '<=', 0)
            ->first();

        if ($paiementInitialComplet) {
            Log::warning('⚠️ Tentative de paiement doublon pour location', [
                'location_id' => $location->id,
                'user_id' => $user->id,
                'paiement_id' => $paiementInitialComplet->id
            ]);

            return redirect()->route('locations.show', $location->id)
                ->with('error', '✅ Le paiement initial de cette location (caution + 1er mois) a déjà été effectué.');
        }

        // ✅ CONTRÔLE 2: Vérifier le statut de la location
        if ($location->statut === 'active') {
            return redirect()->route('locations.show', $location->id)
                ->with('info', '✅ Cette location est déjà active. Le paiement initial a été effectué.');
        }

        try {
            $bien = $location->reservation?->bien ?? $location->bien;

            if (!$bien) {
                return redirect()->back()
                    ->with('error', 'Le bien associé est introuvable.');
            }

            $loyerMensuel = $bien->price;

            if (!$loyerMensuel || $loyerMensuel <= 0) {
                return redirect()->back()
                    ->with('error', 'Le montant du loyer n\'est pas défini.');
            }

            // ✅ Montant initial = Caution (1 mois) + Premier mois = 2 mois
            $montantPaiementAttendu = $loyerMensuel * 2;

            // Récupérer ou créer le paiement
            $paiement = Paiement::where('location_id', $location->id)
                ->where('type', 'location')
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->first();

            if (!$paiement) {
                $paiement = Paiement::create([
                    'location_id' => $location->id,
                    'type' => 'location',
                    'montant_total' => $montantPaiementAttendu,
                    'montant_paye' => 0,
                    'montant_restant' => $montantPaiementAttendu,
                    'commission_agence' => $montantPaiementAttendu * 0.05,
                    'mode_paiement' => 'orange_money',
                    'statut' => 'en_attente',
                    'date_transaction' => now(),
                ]);

                Log::info('💳 Paiement initial location créé', [
                    'location_id' => $location->id,
                    'loyer_mensuel' => $loyerMensuel,
                    'montant_total' => $montantPaiementAttendu
                ]);
            }

            return redirect()->route('paiement.initier.show', [$location->id, $paiement->id])
                ->with('info', 'Paiement initial : Caution + 1er mois de loyer.');

        } catch (\Exception $e) {
            Log::error('Erreur initialisation paiement location', [
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Erreur lors de la préparation du paiement.');
        }
    }
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

        // Vérifier que la location est active
        if ($location->statut !== 'active' && $location->statut !== 'en_retard') {
            return response()->json([
                'success' => false,
                'message' => 'Cette location n\'est pas active. Impossible de payer un loyer.'
            ], 422);
        }

        $request->validate([
            'mois' => 'required|date_format:Y-m',
        ]);

        try {
            $mois = Carbon::createFromFormat('Y-m', $request->mois)->startOfMonth();
            $dateDebut = Carbon::parse($location->date_debut);
            $dateFin = Carbon::parse($location->date_fin);

            Log::info('🔍 Tentative de paiement loyer', [
                'location_id' => $location->id,
                'mois_demande' => $mois->format('Y-m'),
                'user_id' => $user->id
            ]);

            // Vérifier que le mois est dans la période de location
            if ($mois->lt($dateDebut->startOfMonth()) || $mois->gt($dateFin->startOfMonth())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce mois n\'est pas dans la période de location.'
                ], 422);
            }

            // 🔥 FIX CRITIQUE : Vérifier par mois_concerne ET par created_at
            $paiementExistant = Paiement::where('location_id', $location->id)
                ->where('type', 'loyer_mensuel')
                ->where(function($query) use ($mois) {
                    // ✅ PRIORITÉ 1 : Chercher par mois_concerne (si rempli)
                    $query->where('mois_concerne', $mois->format('Y-m-01'))
                        // ✅ PRIORITÉ 2 : Fallback sur created_at (pour anciens paiements)
                        ->orWhere(function($q) use ($mois) {
                            $q->whereNull('mois_concerne')
                                ->whereYear('created_at', $mois->year)
                                ->whereMonth('created_at', $mois->month);
                        });
                })
                ->where('statut', 'reussi')
                ->first();

            if ($paiementExistant) {
                Log::warning('⚠️ Loyer déjà payé', [
                    'location_id' => $location->id,
                    'mois' => $mois->format('Y-m'),
                    'paiement_id' => $paiementExistant->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => '✅ Ce loyer a déjà été payé pour le mois de ' . $mois->translatedFormat('F Y')
                ], 422);
            }

            // Vérifier les paiements en attente pour ce mois
            $paiementEnAttente = Paiement::where('location_id', $location->id)
                ->where('type', 'loyer_mensuel')
                ->where(function($query) use ($mois) {
                    $query->where('mois_concerne', $mois->format('Y-m-01'))
                        ->orWhere(function($q) use ($mois) {
                            $q->whereNull('mois_concerne')
                                ->whereYear('created_at', $mois->year)
                                ->whereMonth('created_at', $mois->month);
                        });
                })
                ->whereIn('statut', ['en_attente', 'en_cours'])
                ->first();

            if ($paiementEnAttente) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un paiement est déjà en cours pour ce mois.',
                    'paiement' => $paiementEnAttente,
                    'redirect_url' => route('paiement.initier.show', [
                        'id' => $location->id,
                        'paiement_id' => $paiementEnAttente->id
                    ])
                ], 422);
            }

            // Calculer les pénalités de retard
            $dateEcheance = $mois->copy()->day(10);
            $aujourdhui = Carbon::now();
            $enRetard = $aujourdhui->gt($dateEcheance);
            $joursRetard = 0;
            $penalites = 0;

            if ($enRetard) {
                $joursRetard = $aujourdhui->diffInDays($dateEcheance);
                $penalites = $location->loyer_mensuel * 0.02; // 2% du loyer
            }

            $montantTotal = $location->loyer_mensuel + $penalites;

            // 🔥 FIX CRITIQUE : TOUJOURS remplir mois_concerne
            $paiement = Paiement::create([
                'type' => 'loyer_mensuel',
                'location_id' => $location->id,
                'montant_total' => $montantTotal,
                'montant_paye' => 0,
                'montant_restant' => $montantTotal,
                'commission_agence' => $montantTotal * 0.05,
                'mode_paiement' => 'orange_money',
                'transaction_id' => 'LOYER_' . $location->id . '_' . $mois->format('Ym') . '_' . time(),
                'statut' => 'en_attente',
                'date_transaction' => now(),
                // ✅ ESSENTIEL : Stocker le mois concerné explicitement
                'mois_concerne' => $mois->format('Y-m-01'),
            ]);

            Log::info('💰 Paiement loyer mensuel créé', [
                'location_id' => $location->id,
                'paiement_id' => $paiement->id,
                'mois_concerne' => $mois->format('Y-m'),
                'mois_concerne_stocke' => $paiement->mois_concerne,
                'date_creation' => now()->format('Y-m-d H:i:s'),
                'loyer_base' => $location->loyer_mensuel,
                'penalites' => $penalites,
                'montant_total' => $montantTotal,
                'jours_retard' => $joursRetard,
            ]);

            return response()->json([
                'success' => true,
                'message' => $enRetard
                    ? "Paiement créé avec pénalités de retard ({$joursRetard} jours)"
                    : 'Paiement créé avec succès',
                'paiement' => $paiement,
                'details' => [
                    'mois_concerne' => $mois->format('Y-m'),
                    'mois_libelle' => $mois->translatedFormat('F Y'),
                    'date_echeance' => $dateEcheance->format('Y-m-d'),
                    'loyer_base' => $location->loyer_mensuel,
                    'penalites' => $penalites,
                    'montant_total' => $montantTotal,
                    'en_retard' => $enRetard,
                    'jours_retard' => $joursRetard
                ],
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
    public function canReceivePayment(Location $location, $type = 'initial'): array
    {
        if ($type === 'initial') {
            // Vérifier le paiement initial
            $paiementComplet = Paiement::where('location_id', $location->id)
                ->where('type', 'location')
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
                ->exists();

            if ($paiementComplet) {
                return [
                    'can_pay' => false,
                    'reason' => 'Paiement initial déjà effectué'
                ];
            }

            if ($location->statut === 'active') {
                return [
                    'can_pay' => false,
                    'reason' => 'Location déjà active'
                ];
            }
        }

        return [
            'can_pay' => true,
            'reason' => null
        ];
    }


    public function confirmerPaiementLoyer(Paiement $paiement)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            return back()->with('error', 'Action non autorisée');
        }

        if (!$paiement->location) {
            return back()->with('error', 'Ce paiement n\'est pas associé à une location');
        }

        if ($paiement->statut === 'reussi') {
            return back()->with('info', 'Ce paiement a déjà été confirmé');
        }

        try {
            DB::beginTransaction();

            // 1. Mettre à jour le paiement
            $paiement->update([
                'statut' => 'reussi',
                'montant_paye' => $paiement->montant_total,
                'montant_restant' => 0,
                'date_transaction' => now(), // ✅ IMPORTANT
            ]);

            // 2. Créer la commission
            app(\App\Services\CommissionService::class)->creerCommissionsApresPaiement($paiement);

            // 3. ✅ CORRECTION : Envoyer la quittance selon le TYPE de paiement
            if ($paiement->type === 'location') {
                // Premier paiement (caution + 1er mois)
                $resultat = $this->quittanceService->genererEtEnvoyerQuittancePaiementLocation($paiement);
            } elseif ($paiement->type === 'loyer_mensuel') {
                // Loyer mensuel classique
                $resultat = $this->quittanceService->genererEtEnvoyerQuittanceLoyer($paiement);
            }

            DB::commit();

            if (isset($resultat) && $resultat['success']) {
                return back()->with('success',
                    'Paiement confirmé avec succès. Quittance envoyée à ' .
                    $paiement->location->client->email
                );
            } else {
                return back()->with('warning',
                    'Paiement confirmé mais erreur lors de l\'envoi de la quittance : ' .
                    ($resultat['message'] ?? 'Erreur inconnue')
                );
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Erreur confirmation paiement', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Erreur lors de la confirmation : ' . $e->getMessage());
        }
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

    // ====================================================================
// CORRECTIF POUR LocationController::showSignaturePage()
// ====================================================================

    public function showSignaturePage(Location $location)
    {
        $user = Auth::user();

        // ✅ CHARGER TOUTES LES RELATIONS NÉCESSAIRES
        $location->load([
            'reservation.bien.category',
            'reservation.bien.proprietaire',
            'reservation.bien.images',
            'reservation.appartement',
            'client'
        ]);

        // ✅ RÉCUPÉRER LE BIEN
        $bien = $location->reservation?->bien;

        if (!$bien) {
            return redirect()->route('locations.index')
                ->with('error', '❌ Le bien associé à cette location est introuvable.');
        }

        // ✅ VÉRIFIER AUTORISATION
        if ($location->client_id !== $user->id &&
            $bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé');
        }

        // ✅ OBTENIR LES STATS DE SIGNATURE
        $signatureStats = $this->contractSignatureService->getSignatureStats($location, 'location');

        Log::info('📝 Page signature chargée', [
            'location_id' => $location->id,
            'statut' => $location->statut,
            'bien_id' => $bien->id,
            'user_role' => $location->client_id === $user->id ? 'locataire' : 'bailleur',
            'signature_stats' => $signatureStats
        ]);

        // 🔥 CORRECTION CRITIQUE : Utiliser 'Locations/Signature' au lieu de 'Biens/Signature'
        return Inertia::render('Locations/Signature', [  // ✅ CHANGÉ ICI !
            'location' => [
                'id' => $location->id,
                'statut' => $location->statut,
                'loyer_mensuel' => $location->loyer_mensuel,
                'montant' => $location->loyer_mensuel, // ✅ Pour compatibilité template
                'date_debut' => $location->date_debut->format('Y-m-d'),
                'date_fin' => $location->date_fin->format('Y-m-d'),
                'type_contrat' => $location->type_contrat,
                'type_contrat_info' => $location->getTypeContratInfo(),
                'signature_status' => $location->signature_status,
                'pdf_path' => $location->pdf_path,
                'client_id' => $location->client_id,
                // ✅ Bien structuré avec toutes les infos nécessaires
                'bien' => [
                    'id' => $bien->id,
                    'title' => $bien->title,
                    'adresse' => $bien->address,  // ✅ Utilisé dans le template
                    'address' => $bien->address,   // ✅ Doublon pour compatibilité
                    'city' => $bien->city,
                    'price' => $bien->price,
                    'proprietaire_id' => $bien->proprietaire_id,
                    'proprietaire' => [
                        'id' => $bien->proprietaire->id,
                        'name' => $bien->proprietaire->name,
                        'email' => $bien->proprietaire->email,
                    ],
                    'images' => $bien->images->map(fn($img) => [
                        'id' => $img->id,
                        'url' => asset('storage/' . $img->chemin_image),
                        'libelle' => $img->libelle
                    ])
                ],
                // ✅ Client structuré
                'client' => [
                    'id' => $location->client->id,
                    'name' => $location->client->name,
                    'email' => $location->client->email,
                ],
                // ✅ Appartement (si existe)
                'appartement' => $location->reservation->appartement ? [
                    'id' => $location->reservation->appartement->id,
                    'numero' => $location->reservation->appartement->numero,
                    'etage' => $location->reservation->appartement->etage,
                ] : null
            ],
            'signatureStats' => $signatureStats,
            'isLocataire' => $location->client_id === $user->id,
            'isBailleur' => $bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }
    public function signByBailleur(Request $request, Location $location)
    {
        $user = Auth::user();

        // 🔐 Vérifier que le bien appartient bien au bailleur
        $bien = $location->reservation?->bien;

        if (
            !$bien ||
            ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin'))
        ) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé'
            ], 403);
        }

        // ⛔ Vérifier si la signature est encore possible
        if (!$location->canBeSignedByBailleur()) {
            return response()->json([
                'success' => false,
                'error' => 'Cette location ne peut plus être signée'
            ], 422);
        }

        $validated = $request->validate([
            'signature' => 'required|string'
        ]);

        try {
            Log::info('🖊️ Début signature bailleur', [
                'location_id' => $location->id,
                'user_id' => $user->id,
                'signature_length' => strlen($validated['signature']),
                'statut_avant' => $location->statut
            ]);

            $location = $this->contractSignatureService->signLocationByBailleur(
                $location,
                $validated['signature']
            );

            Log::info('✅ Signature bailleur enregistrée', [
                'location_id' => $location->id,
                'signature_status' => $location->signature_status,
                'statut_location' => $location->statut,
                'fully_signed' => $location->isFullySigned()
            ]);

            return response()->json([
                'success' => true,
                'message' => $location->isFullySigned()
                    ? '✅ Contrat entièrement signé ! La location est maintenant ACTIVE. 🎉'
                    : 'Signature du bailleur enregistrée avec succès !',
                'signatureStats' => $this->contractSignatureService
                    ->getSignatureStats($location, 'location'),
                'fully_signed' => $location->isFullySigned(),
                'location_status' => $location->statut,
            ]);

        } catch (\Throwable $e) {
            Log::error('❌ Erreur signature bailleur', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la signature'
            ], 500);
        }
    }
    /**
     * ✅ CORRECTION : Signature par le locataire
     */
    public function signByLocataire(Request $request, Location $location)
    {
        $user = Auth::user();

        // 🔐 Sécurité : seul le locataire peut signer
        if ($location->client_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => 'Non autorisé'
            ], 403);
        }

        // ⛔ Vérifier si la signature est encore possible
        if (!$location->canBeSignedByLocataire()) {
            return response()->json([
                'success' => false,
                'error' => 'Cette location ne peut plus être signée'
            ], 422);
        }

        // ✅ Validation
        $validated = $request->validate([
            'signature' => 'required|string'
        ]);

        try {
            Log::info('🖊️ Début signature locataire', [
                'location_id' => $location->id,
                'user_id' => $user->id,
                'signature_length' => strlen($validated['signature']),
                'statut_avant' => $location->statut
            ]);

            // ✅ Signature via le service
            $location = $this->contractSignatureService->signLocationByLocataire(
                $location,
                $validated['signature']
            );

            Log::info('✅ Signature locataire enregistrée', [
                'location_id' => $location->id,
                'signature_status' => $location->signature_status,
                'statut_location' => $location->statut,
                'fully_signed' => $location->isFullySigned()
            ]);

            return response()->json([
                'success' => true,
                'message' => $location->isFullySigned()
                    ? '✅ Contrat entièrement signé ! La location est maintenant ACTIVE. 🎉'
                    : 'Signature du locataire enregistrée avec succès !',
                'signatureStats' => $this->contractSignatureService
                    ->getSignatureStats($location, 'location'),
                'fully_signed' => $location->isFullySigned(),
                'location_status' => $location->statut,
            ]);

        } catch (\Throwable $e) {
            Log::error('❌ Erreur signature locataire', [
                'location_id' => $location->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la signature'
            ], 500);
        }
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

    public function mesLoyers()
    {
        $user = auth()->user();

        // Charger toutes les locations actives du locataire avec relations
        $locations = Location::with([
            'reservation.bien.category',
            'reservation.bien.proprietaire',
            'reservation.appartement',
            'reservation.paiements' => function($query) {
                $query->where('statut', 'reussi')->orderBy('created_at', 'asc');
            },
            'paiements' => function($query) {
                $query->orderBy('created_at', 'asc');
            }
        ])
            ->where('client_id', $user->id)
            ->whereIn('statut', ['active', 'en_retard'])
            ->orderBy('date_debut', 'desc')
            ->get();

        Log::info('📊 Chargement des loyers', [
            'user_id' => $user->id,
            'locations_count' => $locations->count()
        ]);

        $locationsAvecPaiements = $locations->map(function ($location) {
            $bien = $location->reservation?->bien;
            $appartement = $location->reservation?->appartement;

            if (!$bien) {
                Log::warning('⚠️ Location sans bien', ['location_id' => $location->id]);
                return null;
            }

            $dateDebut = Carbon::parse($location->date_debut);
            $dateFin = Carbon::parse($location->date_fin);
            $aujourdhui = Carbon::now();

            $paiementReservation = $location->reservation?->paiements()
                ->where('type', 'reservation')
                ->where('statut', 'reussi')
                ->first();

            $montantReservation = 0;
            if ($paiementReservation) {
                $montantReservation = $paiementReservation->montant_paye;
                Log::info('💰 Paiement réservation trouvé', [
                    'location_id' => $location->id,
                    'paiement_id' => $paiementReservation->id,
                    'montant' => $montantReservation
                ]);
            } else {
                Log::warning('⚠️ Pas de paiement réservation trouvé', [
                    'location_id' => $location->id,
                    'reservation_id' => $location->reservation_id
                ]);
            }

            $paiementInitial = $location->paiements()
                ->where('type', 'location')
                ->where('statut', 'reussi')
                ->first();

            $montantPaiementInitial = 0;
            if ($paiementInitial) {
                $montantPaiementInitial = $paiementInitial->montant_paye;
                Log::info('💰 Paiement initial location trouvé', [
                    'location_id' => $location->id,
                    'paiement_id' => $paiementInitial->id,
                    'montant' => $montantPaiementInitial
                ]);
            }

            $paiementsLoyersMensuels = $location->paiements()
                ->where('type', 'loyer_mensuel')
                ->where('statut', 'reussi')
                ->get();

            $montantLoyersMensuels = $paiementsLoyersMensuels->sum('montant_paye');

            Log::info('💰 Loyers mensuels trouvés', [
                'location_id' => $location->id,
                'nombre' => $paiementsLoyersMensuels->count(),
                'montant_total' => $montantLoyersMensuels
            ]);

            $moisCouvertsPaiementInitial = 0;

            if ($paiementInitial) {
                // Le paiement initial couvre le PREMIER mois (caution = 2 autres mois)
                $moisCouvertsPaiementInitial = 1;

                Log::info('💵 Paiement initial détecté', [
                    'paiement_id' => $paiementInitial->id,
                    'montant' => $paiementInitial->montant_paye,
                    'mois_couverts' => $moisCouvertsPaiementInitial
                ]);
            }

            $moisLoyers = [];
            $currentDate = $dateDebut->copy()->startOfMonth();
            $indexMois = 0;

            while ($currentDate->lte($dateFin)) {
                $moisDebut = $currentDate->copy();
                $moisFin = $currentDate->copy()->endOfMonth();
                $dateEcheance = $moisDebut->copy()->day(10);

                $paiementEffectue = false;
                $paiementPourCeMois = null;
                $sourcePaiement = null;

                if ($indexMois < $moisCouvertsPaiementInitial) {
                    $paiementEffectue = true;
                    $paiementPourCeMois = $paiementInitial;
                    $sourcePaiement = 'initial';
                } else {
                    $paiementPourCeMois = $paiementsLoyersMensuels->first(function($p) use ($moisDebut) {
                        if ($p->mois_concerne) {
                            $moisPaiement = Carbon::parse($p->mois_concerne);
                            return $moisPaiement->year == $moisDebut->year &&
                                $moisPaiement->month == $moisDebut->month;
                        }

                        if ($p->created_at) {
                            $dateCreation = Carbon::parse($p->created_at);
                            return $dateCreation->year == $moisDebut->year &&
                                $dateCreation->month == $moisDebut->month;
                        }

                        return false;
                    });

                    if ($paiementPourCeMois) {
                        $paiementEffectue = true;
                        $sourcePaiement = 'mensuel';
                    }
                }

                // Déterminer le statut du mois
                $statut = 'futur';
                $canPay = false;
                $joursRetard = 0;

                if ($aujourdhui->gte($moisDebut)) {
                    if ($paiementEffectue) {
                        $statut = 'paye';
                        $canPay = false;
                    } elseif ($aujourdhui->gt($moisFin)) {
                        $statut = 'en_retard';
                        $canPay = true;
                        $joursRetard = $aujourdhui->diffInDays($dateEcheance);
                    } elseif ($aujourdhui->gt($dateEcheance)) {
                        $statut = 'en_retard';
                        $canPay = true;
                        $joursRetard = $aujourdhui->diffInDays($dateEcheance);
                    } else {
                        $statut = 'en_cours';
                        $canPay = true;
                    }
                }

                $moisData = [
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
                    'paiement_id' => $paiementPourCeMois?->id,
                    'source_paiement' => $sourcePaiement,
                ];

                $moisLoyers[] = $moisData;
                $currentDate->addMonth();
                $indexMois++;
            }

            // ========================================
            // 4. CALCULER LES STATISTIQUES
            // ========================================
            $totalMois = count($moisLoyers);
            $moisPayes = collect($moisLoyers)->where('paye', true)->count();
            $moisEnRetard = collect($moisLoyers)->where('statut', 'en_retard')->count();

            // 🔥 CORRECTION CRITIQUE : Montant total INCLUANT TOUS LES PAIEMENTS
            $montantTotal = ($totalMois * $location->loyer_mensuel) + // Tous les loyers
                $montantReservation + // Dépôt de garantie (réservation)
                ($paiementInitial ? $paiementInitial->montant_paye : 0); // Caution + 1er mois

            // 🔥 CORRECTION CRITIQUE : Montant payé = TOUS les paiements réussis
            $montantPaye = $montantReservation + // ✅ Dépôt de garantie
                $montantPaiementInitial + // ✅ Caution + 1er mois
                $montantLoyersMensuels; // ✅ Loyers mensuels

            $montantRestant = $montantTotal - $montantPaye;

            Log::info('💰 CALCULS FINAUX', [
                'location_id' => $location->id,
                'montant_reservation' => $montantReservation,
                'montant_initial' => $montantPaiementInitial,
                'montant_loyers_mensuels' => $montantLoyersMensuels,
                'montant_paye_TOTAL' => $montantPaye,
                'montant_total' => $montantTotal,
                'montant_restant' => $montantRestant
            ]);

            $statistiques = [
                'total_mois' => $totalMois,
                'mois_payes' => $moisPayes,
                'mois_en_retard' => $moisEnRetard,

                // 🔥 AJOUT : Détail des paiements
                'paiement_reservation' => $montantReservation,
                'paiement_initial' => $montantPaiementInitial,
                'paiements_loyers_mensuels' => $montantLoyersMensuels,

                'mois_couverts_initial' => $moisCouvertsPaiementInitial,
                'montant_total' => $montantTotal,
                'montant_paye' => $montantPaye,
                'montant_restant' => $montantRestant,
                'taux_paiement' => $totalMois > 0 ? round(($moisPayes / $totalMois) * 100, 2) : 0,
            ];

            // ========================================
            // 5. RETOURNER LA LOCATION AVEC TOUTES LES DONNÉES
            // ========================================
            return [
                'id' => $location->id,
                'bien' => $bien ? [
                    'id' => $bien->id,
                    'titre' => $bien->title,
                    'adresse' => $bien->address,
                    'ville' => $bien->city,
                    'image' => $bien->images->first()
                        ? asset('storage/' . $bien->images->first()->chemin_image)
                        : null,
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
                'statistiques' => $statistiques,
            ];
        })->filter()->values();

        Log::info('✅ Données locations préparées', [
            'locations_count' => $locationsAvecPaiements->count()
        ]);

        return Inertia::render('Locations/MesLoyers', [
            'locations' => $locationsAvecPaiements,
            'user' => $user,
        ]);
    }
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

        $bien = $reservation->bien;

        if (!$bien) {
            return back()->withErrors(['message' => '❌ Le bien associé à cette réservation est introuvable.']);
        }

        // ✅ Auto-sélection intelligente de l'appartement
        $appartementId = $request->appartement_id;

        if (!$appartementId && $reservation->appartement_id) {
            $appartementId = $reservation->appartement_id;
            Log::info('✅ Appartement récupéré depuis réservation', [
                'appartement_id' => $appartementId
            ]);
        }

        if (!$appartementId && $bien->isImmeuble()) {
            $appartementsDispo = $bien->appartements()
                ->whereIn('statut', ['disponible', 'reserve'])
                ->get();

            if ($appartementsDispo->count() === 1) {
                $appartementId = $appartementsDispo->first()->id;
                Log::info('✅ Auto-sélection du seul appartement disponible', [
                    'appartement_id' => $appartementId
                ]);
            }
        }

        // ✅ VALIDATION DE L'APPARTEMENT
        $appartement = null;

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

            if (!$appartement->isReserve()) {
                return back()->withErrors([
                    'message' => '❌ L\'appartement n\'est plus disponible.'
                ]);
            }
        } elseif ($appartementId) {
            $appartement = Appartement::find($appartementId);

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
        } elseif ($bien->isImmeuble()) {
            return back()->withErrors([
                'appartement_id' => '❌ Vous devez sélectionner un appartement.'
            ]);
        }

        try {
            $location = DB::transaction(function () use ($request, $user, $bien, $appartement, $reservation) {
                $dateDebut = Carbon::parse($request->date_debut);
                $dateFin = $dateDebut->copy()->addMonths((int) $request->duree_mois);

                Log::info('💰 Calcul du loyer', [
                    'bien_id' => $bien->id,
                    'prix_bien' => $bien->price,
                    'est_immeuble' => $bien->isImmeuble(),
                    'a_appartement' => $appartement ? 'OUI' : 'NON',
                ]);

                $loyerMensuel = $bien->price;

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

                // ❌ NE PLUS marquer l'appartement ici
                // Il sera marqué lors de la validation du paiement

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
                $message .= ' Appartement ' . $appartement->numero . ' sera loué après paiement.';
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

                // ✅ MARQUER L'APPARTEMENT COMME LOUÉ (via reservation)
                if ($location->reservation && $location->reservation->appartement_id) {
                    $appartement = $location->reservation->appartement;

                    if ($appartement) {
                        $appartement->update(['statut' => 'loue']);

                        Log::info('🏠 Appartement marqué comme loué', [
                            'appartement_id' => $appartement->id,
                            'numero' => $appartement->numero,
                            'location_id' => $location->id
                        ]);
                    }
                }

                // ✅ METTRE À JOUR LE STATUT GLOBAL DU BIEN
                if ($location->reservation && $location->reservation->bien) {
                    $location->reservation->bien->updateStatutGlobal();

                    Log::info('🏢 Statut du bien mis à jour', [
                        'bien_id' => $location->reservation->bien->id,
                        'nouveau_statut' => $location->reservation->bien->fresh()->status
                    ]);
                }

                // Mettre à jour la réservation
                if ($location->reservation) {
                    $location->reservation->update([
                        'statut' => 'finalisée',
                    ]);
                }
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


    public function terminerLocation(Location $location)
    {
        $user = auth()->user();

        // ✅ Vérifier autorisation via reservation->bien
        $bien = $location->reservation?->bien;

        if (!$bien || ($bien->proprietaire_id !== $user->id && !$user->hasRole('admin'))) {
            abort(403);
        }

        if ($location->statut === 'terminee') {
            return back()->with('info', 'Cette location est déjà terminée');
        }

        try {
            DB::transaction(function () use ($location) {
                $location->update(['statut' => 'terminee']);

                // ✅ LIBÉRER L'APPARTEMENT (via reservation)
                if ($location->reservation && $location->reservation->appartement_id) {
                    $appartement = $location->reservation->appartement;

                    if ($appartement) {
                        $appartement->update(['statut' => 'disponible']);

                        Log::info('🏠 Appartement libéré', [
                            'appartement_id' => $appartement->id,
                            'numero' => $appartement->numero,
                            'location_id' => $location->id
                        ]);
                    }
                }

                // ✅ METTRE À JOUR LE STATUT GLOBAL DU BIEN
                if ($location->reservation && $location->reservation->bien) {
                    $location->reservation->bien->updateStatutGlobal();

                    Log::info('🏢 Statut du bien mis à jour après fin de location', [
                        'bien_id' => $location->reservation->bien->id,
                        'nouveau_statut' => $location->reservation->bien->fresh()->status
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


    private function canUserSign(Location $location, $user)
    {
        // Vérifier si le locataire peut signer
        if ($location->client_id === $user->id && $location->canBeSignedByLocataire()) {
            return true;
        }

        // ✅ CORRECTION : Charger le bien via reservation
        $bien = $location->reservation?->bien;

        if ($bien && $bien->proprietaire_id === $user->id && $location->canBeSignedByBailleur()) {
            return true;
        }

        return false;
    }
}
