<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Bien;
use App\Models\Reservation;
use App\Services\ContractElectronicSignatureService;
use App\Services\ContractPdfService;
use App\Services\PropertyTransferService;
use App\Services\SaleCompletionNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Services\QuittanceService;

class VenteController extends Controller
{
    protected $contractPdfService;
    protected $contractSignatureService;
    protected $propertyTransferService;
    protected $quittanceService;
    protected $saleCompletionService;

    public function __construct(
        ContractPdfService $contractPdfService,
        ContractElectronicSignatureService $contractSignatureService,
        PropertyTransferService $propertyTransferService,
        QuittanceService $quittanceService,
        SaleCompletionNotificationService $saleCompletionService // ✅ AJOUT
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractSignatureService = $contractSignatureService;
        $this->propertyTransferService = $propertyTransferService;
        $this->quittanceService = $quittanceService;
        $this->saleCompletionService = $saleCompletionService;
        }// ✅ AJOUT


        /**
     * ✅ INDEX CORRIGÉ - Utilise reservation.bien au lieu de bien_id
     */
    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        // ✅ CHARGER TOUTES LES RELATIONS VIA RESERVATION
        $ventes = Vente::with([
            'reservation.bien.category',
            'reservation.bien.proprietaire',
            'reservation.bien.images',
            'reservation.appartement',
            'acheteur',
            'ancien_proprietaire',
            'paiement',
        ])
            ->where(function ($query) use ($userId) {
                // Ventes où l'utilisateur est acheteur
                $query->where('acheteur_id', $userId)
                    // OU ventes où l'utilisateur est propriétaire via reservation
                    ->orWhereHas('reservation.bien', function ($q) use ($userId) {
                        $q->where('proprietaire_id', $userId);
                    });
            })
            ->orderBy('date_vente', 'desc')
            ->get();

        Log::info('📊 Ventes trouvées', [
            'user_id' => $userId,
            'count_total' => $ventes->count()
        ]);

        // ✅ MAPPER AVEC VÉRIFICATIONS
        $ventesFormatted = $ventes->map(function ($vente) use ($userId) {
            // ✅ Récupérer le bien VIA RESERVATION
            $bien = $vente->reservation?->bien;

            // ✅ SI PAS DE BIEN, RETOURNER NULL
            if (!$bien) {
                Log::warning('⚠️ Vente sans bien', [
                    'vente_id' => $vente->id,
                    'reservation_id' => $vente->reservation_id
                ]);
                return null;
            }

            // ✅ VÉRIFIER LE PRIX
            if (!$bien->price || $bien->price <= 0) {
                Log::warning('⚠️ Vente avec prix invalide', [
                    'vente_id' => $vente->id,
                    'bien_id' => $bien->id,
                    'price' => $bien->price
                ]);
            }

            // ✅ FORMATER LES DONNÉES DU BIEN
            $bienData = $bien->toArray();
            $premiereImage = $bien->images->first();
            $bienData['image'] = $premiereImage
                ? asset('storage/' . $premiereImage->chemin_image)
                : null;

            $bienData['images'] = $bien->images->map(function($img) {
                return [
                    'id' => $img->id,
                    'url' => asset('storage/' . $img->chemin_image),
                    'libelle' => $img->libelle
                ];
            })->toArray();

            // Stats de signature
            $isVendeurSigned = $vente->isSignedByVendeur();
            $isAcheteurSigned = $vente->isSignedByAcheteur();
            $signaturesCompleted = ($isVendeurSigned ? 1 : 0) + ($isAcheteurSigned ? 1 : 0);

            // Info de paiement
            $paiementInfo = $vente->paiement ? [
                'statut' => $vente->paiement->statut,
                'montant_total' => $vente->paiement->montant_total,
                'montant_paye' => $vente->paiement->montant_paye,
                'montant_restant' => $vente->paiement->montant_restant,
                'est_complet' => $vente->paiement->statut === 'reussi' &&
                    $vente->paiement->montant_restant <= 0,
            ] : null;

            return [
                'id' => $vente->id,
                'reservation_id' => $vente->reservation_id,
                'acheteur_id' => $vente->acheteur_id,
                'prix_vente' => $vente->prix_vente,
                'date_vente' => $vente->date_vente,
                'status' => $vente->status,
                'signature_status' => $vente->signature_status,
                'property_transferred' => $vente->property_transferred,
                'created_at' => $vente->created_at,

                // ✅ BIEN VIA RESERVATION
                'bien' => $bienData,

                // Acheteur
                'acheteur' => $vente->acheteur,

                // Stats
                'signature_stats' => [
                    'total' => 2,
                    'completed' => $signaturesCompleted,
                    'signature_status' => match ($signaturesCompleted) {
                        0 => 'non_signe',
                        2 => 'entierement_signe',
                        default => 'partiellement_signe',
                    },
                    'fully_signed' => $signaturesCompleted === 2,
                    'vendeur_signe' => $isVendeurSigned,
                    'acheteur_signe' => $isAcheteurSigned,
                ],

                'paiement_info' => $paiementInfo,
                'can_sign' => $vente->acheteur_id === $userId
                    ? $vente->canBeSignedByAcheteur()
                    : $vente->canBeSignedByVendeur(),

                'user_role_in_vente' => $this->getUserRoleInVente($vente, $userId),
            ];
        })
            ->filter() // ✅ RETIRER LES NULL
            ->values(); // ✅ RÉINDEXER

        Log::info('✅ Ventes formatées', [
            'count' => $ventesFormatted->count(),
        ]);

        return Inertia::render('Ventes/Index', [
            'ventes' => $ventesFormatted,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    /**
     * ✅ HELPER : Déterminer le rôle de l'utilisateur dans la vente
     */
    private function getUserRoleInVente(Vente $vente, $userId)
    {
        if ($vente->acheteur_id === $userId) {
            return 'acheteur';
        }

        $bien = $vente->reservation?->bien;
        if ($bien && $bien->proprietaire_id === $userId) {
            return 'vendeur';
        }

        return null;
    }

    public function signByVendeur(Request $request, Vente $vente)
    {
        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            Log::info('🖊️ Début signature vendeur', [
                'vente_id' => $vente->id,
                'bien_id' => $vente->reservation?->bien?->id
            ]);

            // 1. Signer via le service
            $vente = $this->contractSignatureService->signVenteByVendeur($vente, $request->signature_data);

            Log::info('✅ Signature vendeur enregistrée', [
                'vente_id' => $vente->id,
                'signature_status' => $vente->signature_status,
                'is_fully_signed' => $vente->isFullySigned()
            ]);

            // 2. Vérifier si entièrement signé
            if ($vente->isFullySigned()) {
                Log::info('🎯 Contrat ENTIÈREMENT SIGNÉ détecté !', [
                    'vente_id' => $vente->id
                ]);

                $bien = $vente->reservation?->bien;

                if ($bien) {
                    // ✅ Marquer le BIEN comme vendu (pas la vente)
                    $bien->update(['status' => 'vendu']);

                    Log::info('✅ Bien marqué comme VENDU', [
                        'bien_id' => $bien->id,
                        'nouveau_statut' => 'vendu'
                    ]);

                    // ✅ Mettre à jour le statut de la VENTE (confirmée, pas vendu)
                    $vente->update(['status' => Vente::STATUT_CONFIRMEE]);

                    Log::info('✅ Vente confirmée', [
                        'vente_id' => $vente->id,
                        'status' => $vente->status
                    ]);

                    // ✅ Envoyer la notification à l'acheteur
                    Log::info('🚀 APPEL du service de notification...', [
                        'vente_id' => $vente->id,
                        'acheteur_id' => $vente->acheteur_id
                    ]);

                    $notificationSuccess = $this->saleCompletionService->envoyerNotificationAchat($vente);

                    if ($notificationSuccess) {
                        Log::info('📧 ✅ Notification acheteur envoyée avec succès', [
                            'vente_id' => $vente->id
                        ]);
                    } else {
                        Log::error('⚠️ ❌ Échec envoi notification acheteur', [
                            'vente_id' => $vente->id
                        ]);
                    }

                    // ✅ Transférer la propriété (ne bloque pas la notification)
                    try {
                        $transferSuccess = $this->propertyTransferService->transferPropertyToBuyer($vente);

                        if ($transferSuccess) {
                            Log::info('✅ Propriété transférée avec succès', [
                                'vente_id' => $vente->id,
                                'nouveau_proprietaire' => $vente->acheteur_id
                            ]);
                        } else {
                            Log::error('❌ Échec transfert propriété', [
                                'vente_id' => $vente->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('❌ Exception lors du transfert propriété', [
                            'vente_id' => $vente->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // On continue quand même, le transfert peut être fait manuellement
                    }
                } else {
                    Log::error('❌ Bien introuvable pour la vente', [
                        'vente_id' => $vente->id,
                        'reservation_id' => $vente->reservation_id
                    ]);
                }
            } else {
                Log::info('⏳ Contrat partiellement signé, en attente signature acheteur', [
                    'vente_id' => $vente->id,
                    'signatures_manquantes' => 2 - ($vente->isSignedByVendeur() ? 1 : 0) - ($vente->isSignedByAcheteur() ? 1 : 0)
                ]);
            }

            $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

            return response()->json([
                'success' => true,
                'message' => $vente->isFullySigned()
                    ? '✅ Contrat entièrement signé ! Le bien est maintenant marqué comme VENDU. L\'acheteur a été notifié par message. 🎉'
                    : 'Signature du vendeur enregistrée avec succès ! En attente de la signature de l\'acheteur.',
                'signatureStats' => $signatureStats,
                'fully_signed' => $vente->isFullySigned(),
                'bien_status' => $vente->reservation?->bien?->status,
                'vente_status' => $vente->status,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur signature vendeur", [
                'vente_id' => $vente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    public function signByAcheteur(Request $request, Vente $vente)
    {
        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            Log::info('🖊️ Début signature acheteur', [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id
            ]);

            // 1. Signer via le service
            $vente = $this->contractSignatureService->signVenteByAcheteur($vente, $request->signature_data);

            Log::info('✅ Signature acheteur enregistrée', [
                'vente_id' => $vente->id,
                'signature_status' => $vente->signature_status,
                'is_fully_signed' => $vente->isFullySigned()
            ]);

            // 2. Vérifier si entièrement signé
            if ($vente->isFullySigned()) {
                Log::info('🎯 Contrat ENTIÈREMENT SIGNÉ détecté !', [
                    'vente_id' => $vente->id
                ]);

                $bien = $vente->reservation?->bien;

                if ($bien) {
                    // ✅ Marquer le BIEN comme vendu (pas la vente)
                    $bien->update(['status' => 'vendu']);

                    Log::info('✅ Bien marqué comme VENDU', [
                        'bien_id' => $bien->id,
                        'nouveau_statut' => 'vendu'
                    ]);

                    // ✅ Mettre à jour le statut de la VENTE (confirmée, pas vendu)
                    $vente->update(['status' => Vente::STATUT_CONFIRMEE]);

                    Log::info('✅ Vente confirmée', [
                        'vente_id' => $vente->id,
                        'status' => $vente->status
                    ]);

                    // ✅ Envoyer la notification à l'acheteur
                    Log::info('🚀 APPEL du service de notification...', [
                        'vente_id' => $vente->id,
                        'acheteur_id' => $vente->acheteur_id
                    ]);

                    $notificationSuccess = $this->saleCompletionService->envoyerNotificationAchat($vente);

                    if ($notificationSuccess) {
                        Log::info('📧 ✅ Notification acheteur envoyée avec succès', [
                            'vente_id' => $vente->id
                        ]);
                    } else {
                        Log::error('⚠️ ❌ Échec envoi notification acheteur', [
                            'vente_id' => $vente->id
                        ]);
                    }

                    // ✅ Transférer la propriété (ne bloque pas la notification)
                    try {
                        $transferSuccess = $this->propertyTransferService->transferPropertyToBuyer($vente);

                        if ($transferSuccess) {
                            Log::info('✅ Propriété transférée avec succès', [
                                'vente_id' => $vente->id,
                                'nouveau_proprietaire' => $vente->acheteur_id
                            ]);
                        } else {
                            Log::error('❌ Échec transfert propriété', [
                                'vente_id' => $vente->id
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error('❌ Exception lors du transfert propriété', [
                            'vente_id' => $vente->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // On continue quand même, le transfert peut être fait manuellement
                    }
                } else {
                    Log::error('❌ Bien introuvable pour la vente', [
                        'vente_id' => $vente->id,
                        'reservation_id' => $vente->reservation_id
                    ]);
                }
            } else {
                Log::info('⏳ Contrat partiellement signé, en attente signature vendeur', [
                    'vente_id' => $vente->id,
                    'signatures_manquantes' => 2 - ($vente->isSignedByVendeur() ? 1 : 0) - ($vente->isSignedByAcheteur() ? 1 : 0)
                ]);
            }

            $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

            return response()->json([
                'success' => true,
                'message' => $vente->isFullySigned()
                    ? '✅ Contrat entièrement signé ! Le bien est maintenant marqué comme VENDU. Vous allez recevoir un message de confirmation avec toutes les informations. 🎉'
                    : 'Signature de l\'acheteur enregistrée avec succès ! En attente de la signature du vendeur.',
                'signatureStats' => $signatureStats,
                'fully_signed' => $vente->isFullySigned(),
                'bien_status' => $vente->reservation?->bien?->status,
                'vente_status' => $vente->status,
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur signature acheteur", [
                'vente_id' => $vente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    private function traiterVenteComplete(Paiement $paiement)
    {
        $vente = Vente::find($paiement->vente_id);
        if (!$vente) return;

        DB::transaction(function () use ($vente, $paiement) {
            // ... votre code existant ...

            // ✅ NOUVEAU : Si le contrat est déjà entièrement signé, notifier l'acheteur
            if ($vente->isFullySigned()) {
                Log::info('📧 Paiement complet et contrat signé, envoi notification acheteur');

                $notificationResult = $this->saleCompletionService->notifyBuyerOfCompletedSale($vente);

                if ($notificationResult['success']) {
                    Log::info('✅ Notification acheteur envoyée après paiement complet');
                }
            }
        });
    }

    /**
     * ✅ CREATE CORRIGÉ
     */
    public function create(Request $request)
    {
        $reservationId = $request->input('reservation_id');
        if (!$reservationId) {
            return redirect()->route('reservations.index')
                ->with('error', 'Aucune réservation spécifiée pour la vente.');
        }

        // ✅ CHARGER TOUTES LES RELATIONS
        $reservation = Reservation::with([
            'bien.category',
            'bien.proprietaire',
            'bien.mandat',
            'bien.images',
            'appartement',
            'client'
        ])->find($reservationId);

        if (!$reservation) {
            Log::error('❌ Réservation introuvable', [
                'reservation_id' => $reservationId
            ]);
            return redirect()->route('reservations.index')
                ->with('error', 'Réservation introuvable.');
        }

        $bien = $reservation->bien;
        if (!$bien) {
            Log::error('❌ Bien introuvable pour vente', [
                'reservation_id' => $reservationId,
                'bien_id' => $reservation->bien_id
            ]);
            return redirect()->route('reservations.index')
                ->with('error', 'Le bien associé à cette réservation est introuvable.');
        }

        if (!$bien->price || $bien->price <= 0) {
            Log::error('❌ Prix du bien invalide pour vente', [
                'bien_id' => $bien->id,
                'price' => $bien->price
            ]);
            return redirect()->route('reservations.show', $reservationId)
                ->with('error', 'Le prix du bien n\'est pas défini.');
        }

        if ($reservation->statut !== 'confirmée') {
            return redirect()->route('reservations.show', $reservationId)
                ->with('error', 'La réservation doit être confirmée avant de procéder à la vente.');
        }

        if ($bien->proprietaire_id === Auth::id()) {
            return redirect()->route('biens.show', $bien->id)
                ->with('error', '❌ Vous ne pouvez pas acheter votre propre bien.');
        }

        $venteExistante = Vente::where('reservation_id', $reservationId)
            ->where('acheteur_id', Auth::id())
            ->exists();

        if ($venteExistante) {
            return redirect()->route('ventes.index')
                ->with('error', 'Vous avez déjà acheté ce bien via cette réservation.');
        }

        if (!$bien->mandat || $bien->mandat->type_mandat !== 'vente') {
            return redirect()->route('reservations.show', $reservationId)
                ->with('error', 'Ce bien n\'est pas disponible à la vente.');
        }

        Log::info('✅ Données vente prêtes', [
            'reservation_id' => $reservationId,
            'bien_id' => $bien->id,
            'prix' => $bien->price
        ]);

        // ✅ FORMATER LES DONNÉES DU BIEN
        $bienData = $bien->toArray();
        $bienData['image'] = $bien->images->first() ? $bien->images->first()->chemin_image : null;
        $bienData['images'] = $bien->images->map(function($img) {
            return [
                'id' => $img->id,
                'url' => $img->chemin_image,
                'libelle' => $img->libelle
            ];
        })->toArray();

        return Inertia::render('Ventes/Create', [
            'bien' => $bienData,
            'reservation' => $reservation,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }

    /**
     * ✅ SHOW CORRIGÉ
     */
    public function show(Vente $vente)
    {
        $user = Auth::user();

        // ✅ CHARGER VIA RESERVATION
        $vente->load([
            'reservation.bien.category',
            'reservation.bien.images',
            'reservation.bien.proprietaire',
            'reservation.bien.mandat',
            'reservation.appartement',
            'acheteur',
            'ancien_proprietaire',
            'paiement'
        ]);

        // ✅ VÉRIFIER LE BIEN VIA RESERVATION
        $bien = $vente->reservation?->bien;
        if (!$bien) {
            Log::error('❌ Bien introuvable pour vente', [
                'vente_id' => $vente->id
            ]);
            return redirect()->route('ventes.index')
                ->with('error', '❌ Le bien associé à cette vente est introuvable.');
        }

        // Vérifier les autorisations
        if ($vente->acheteur_id !== $user->id &&
            $bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter cette vente.');
        }

        $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');
        $transactionStatus = $vente->getTransactionStatus();

        return Inertia::render('Ventes/Show', [
            'vente' => array_merge($vente->toArray(), [
                'bien' => $bien,
            ]),
            'signatureStats' => $signatureStats,
            'transactionStatus' => $transactionStatus,
            'userRoles' => $user->roles->pluck('name'),
            'isAcheteur' => $vente->acheteur_id === $user->id,
            'isVendeur' => $bien->proprietaire_id === $user->id || $vente->ancien_proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
            'propertyTransferred' => $vente->isPropertyTransferred(),
        ]);
    }

    /**
     * ✅ STORE CORRIGÉ
     */
    public function store(Request $request)
    {
        try {
            session()->forget(['error', 'success', 'warning', 'info']);

            Log::info('📥 Réception données vente:', $request->all());

            $validated = $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'prix_vente' => 'required|numeric|min:0',
                'date_vente' => 'required|date',
            ]);

            // ✅ CHARGER VIA RESERVATION
            $reservation = Reservation::with(['bien.mandat', 'client'])->findOrFail($validated['reservation_id']);

            if ($reservation->client_id !== auth()->id()) {
                return back()->withErrors([
                    'autorisation' => 'Vous n\'êtes pas autorisé à acheter ce bien.'
                ]);
            }

            if (!$reservation->bien) {
                return back()->withErrors([
                    'bien' => 'Le bien associé à cette réservation n\'existe pas.'
                ]);
            }

            if ($reservation->bien->proprietaire_id === auth()->id()) {
                return back()->withErrors([
                    'proprietaire' => '❌ Vous ne pouvez pas acheter votre propre bien.'
                ]);
            }

            if ($reservation->bien->status === 'vendu') {
                return back()->withErrors([
                    'bien' => 'Ce bien a déjà été vendu.'
                ]);
            }

            // ✅ CALCUL DES MONTANTS
            $prixVenteTotal = $validated['prix_vente'];
            $depotGarantie = $prixVenteTotal * 0.10;
            $montantRestantAPayer = $prixVenteTotal - $depotGarantie;

            Log::info('💰 Calcul montant vente', [
                'prix_vente_total' => $prixVenteTotal,
                'depot_10%_deja_paye' => $depotGarantie,
                'montant_restant_90%' => $montantRestantAPayer
            ]);

            // Vérifier si vente existe déjà
            $venteExistante = Vente::where('reservation_id', $validated['reservation_id'])
                ->whereIn('status', ['confirmée', 'en_attente_paiement'])
                ->first();

            if ($venteExistante) {
                Log::info('✅ Vente existante trouvée', [
                    'vente_id' => $venteExistante->id,
                    'status' => $venteExistante->status
                ]);

                $paiement = Paiement::where('vente_id', $venteExistante->id)->first();

                if (!$paiement) {
                    $paiement = Paiement::create([
                        'vente_id' => $venteExistante->id,
                        'type' => 'vente',
                        'montant_total' => $montantRestantAPayer,
                        'montant_paye' => 0,
                        'montant_restant' => $montantRestantAPayer,
                        'commission_agence' => $prixVenteTotal * 0.05,
                        'statut' => 'en_attente',
                        'mode_paiement' => 'orange_money',
                    ]);

                    Log::info('💳 Paiement créé pour vente existante');
                }

                return redirect()
                    ->route('paiement.initier.show', [
                        'id' => $venteExistante->id,
                        'paiement_id' => $paiement->id
                    ])
                    ->with('info', 'Une vente existe déjà pour ce bien. Continuez le paiement des 90% restants.');
            }

            // ✅ CRÉER NOUVELLE VENTE
            Log::info('📝 Création d\'une nouvelle vente...');

            $vente = DB::transaction(function () use ($validated, $reservation, $prixVenteTotal, $montantRestantAPayer) {
                $vente = Vente::create([
                    'reservation_id' => $validated['reservation_id'],
                    'acheteur_id' => auth()->id(),
                    'prix_vente' => $prixVenteTotal,
                    'date_vente' => $validated['date_vente'],
                    'status' => Vente::STATUT_EN_ATTENTE_PAIEMENT,
                    'ancien_proprietaire_id' => $reservation->bien->proprietaire_id,
                ]);

                Log::info('✅ Vente créée', [
                    'vente_id' => $vente->id,
                    'prix_vente_total' => $vente->prix_vente,
                ]);

                Paiement::create([
                    'vente_id' => $vente->id,
                    'type' => 'vente',
                    'montant_total' => $montantRestantAPayer,
                    'montant_paye' => 0,
                    'montant_restant' => $montantRestantAPayer,
                    'commission_agence' => $prixVenteTotal * 0.05,
                    'statut' => 'en_attente',
                    'mode_paiement' => 'orange_money',
                    'date_transaction' => now(),
                ]);

                Log::info('💳 Paiement créé');

                return $vente;
            });

            $paiement = Paiement::where('vente_id', $vente->id)->first();

            if (!$paiement) {
                throw new \Exception('Erreur lors de la création du paiement');
            }

            return redirect()
                ->route('paiement.initier.show', [
                    'id' => $vente->id,
                    'paiement_id' => $paiement->id
                ])
                ->with('success', 'Vente créée avec succès. Vous devez payer 90% du prix (10% déjà versés en dépôt).');

        } catch (\Exception $e) {
            Log::error('❌ Erreur création vente', [
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors([
                'general' => 'Une erreur est survenue lors de la création de la vente.'
            ]);
        }
    }

    /**
     * ✅ AUTRES MÉTHODES CORRIGÉES
     */
    public function initierPaiement(Vente $vente)
    {
        if ($vente->acheteur_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        $paiementComplet = Paiement::where('vente_id', $vente->id)
            ->where('statut', 'reussi')
            ->where('montant_restant', '<=', 0)
            ->first();

        if ($paiementComplet) {
            return redirect()->route('ventes.show', $vente->id)
                ->with('error', '✅ Cette vente a déjà été payée intégralement.');
        }

        if (!$vente->reservation || !$vente->reservation->bien) {
            return redirect()->route('ventes.index')
                ->with('error', 'Le bien associé à cette vente est introuvable.');
        }

        try {
            $prixVenteTotal = $vente->prix_vente;
            $montantRestantAPayer = $prixVenteTotal * 0.90;

            $paiement = Paiement::where('vente_id', $vente->id)
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->first();

            if (!$paiement) {
                $paiement = Paiement::create([
                    'vente_id' => $vente->id,
                    'type' => 'vente',
                    'montant_total' => $montantRestantAPayer,
                    'montant_paye' => 0,
                    'montant_restant' => $montantRestantAPayer,
                    'commission_agence' => $prixVenteTotal * 0.05,
                    'mode_paiement' => 'orange_money',
                    'statut' => 'en_attente',
                    'date_transaction' => now(),
                ]);
            }

            return redirect()->route('paiement.initier.show', [$vente->id, $paiement->id])
                ->with('info', 'Vous devez payer 90% du prix.');

        } catch (\Exception $e) {
            Log::error('Erreur initialisation paiement', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erreur lors de la préparation du paiement.');
        }
    }

    public function validerPaiementVente(Vente $vente)
    {
        $user = auth()->user();

        if (!$user->hasRole('admin')) {
            abort(403);
        }

        $paiement = $vente->paiement;

        if (!$paiement || $paiement->statut === 'reussi') {
            return back()->with('error', 'Paiement introuvable ou déjà validé');
        }

        try {
            DB::beginTransaction();

            $paiement->update([
                'statut' => 'reussi',
                'montant_paye' => $paiement->montant_total,
                'montant_restant' => 0,
            ]);

            $vente->update([
                'status' => Vente::STATUT_CONFIRMEE,
            ]);

            $resultat = $this->quittanceService->genererEtEnvoyerRecuVente($vente, $paiement);

            DB::commit();

            if ($resultat['success']) {
                return redirect()->route('ventes.show', $vente->id)
                    ->with('success', 'Paiement validé avec succès. Reçu envoyé.');
            } else {
                return redirect()->route('ventes.show', $vente->id)
                    ->with('warning', 'Paiement validé mais erreur lors de l\'envoi du reçu.');
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur validation paiement vente', [
                'vente_id' => $vente->id,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Erreur lors de la validation');
        }
    }
    /**
     * ✅ MÉTHODE showSignaturePage CORRIGÉE
     */
    public function showSignaturePage(Vente $vente)
    {
        $user = Auth::user();

        // ✅ CHARGER LES RELATIONS VIA reservation->bien
        $vente->load([
            'reservation.bien.category',
            'reservation.bien.proprietaire',
            'acheteur'
        ]);

        // ✅ RÉCUPÉRER LE BIEN VIA RESERVATION
        $bien = $vente->reservation?->bien;

        if (!$bien) {
            return redirect()->route('ventes.index')
                ->with('error', '❌ Le bien associé à cette vente est introuvable.');
        }

        // Vérifier les autorisations
        if ($vente->acheteur_id !== $user->id &&
            $bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }

        $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

        return Inertia::render('Ventes/Signature', [
            'vente' => [
                ...$vente->toArray(),
                'bien' => $bien, // ✅ Passer le bien explicitement
            ],
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isAcheteur' => $vente->acheteur_id === $user->id,
            'isVendeur' => $bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    /**
     * ✅ MÉTHODE downloadContract CORRIGÉE
     */
    public function downloadContract(Vente $vente)
    {
        $user = Auth::user();

        // ✅ CHARGER LE BIEN VIA RESERVATION
        $vente->load(['reservation.bien']);
        $bien = $vente->reservation?->bien;

        if (!$bien) {
            return redirect()->back()->with('error', '❌ Le bien associé est introuvable.');
        }

        // Vérifier les autorisations
        if ($vente->acheteur_id !== $user->id &&
            $bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce contrat.');
        }

        $response = $this->contractPdfService->downloadPdf($vente, 'vente');

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de télécharger le contrat.');
        }

        return $response;
    }

    /**
     * ✅ MÉTHODE previewContract CORRIGÉE
     */
    public function previewContract(Vente $vente)
    {
        $user = Auth::user();

        // ✅ CHARGER LE BIEN VIA RESERVATION
        $vente->load(['reservation.bien']);
        $bien = $vente->reservation?->bien;

        if (!$bien) {
            return redirect()->back()->with('error', '❌ Le bien associé est introuvable.');
        }

        // Vérifier les autorisations
        if ($vente->acheteur_id !== $user->id &&
            $bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à prévisualiser ce contrat.');
        }

        $response = $this->contractPdfService->previewPdf($vente, 'vente');

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de prévisualiser le contrat.');
        }

        return $response;
    }


    /**
     * Annuler une signature (vendeur ou acheteur)
     */
    public function cancelSignature(Vente $vente, string $type)
    {
        $user = auth()->user();

        // ✅ Vérifier les autorisations
        $bien = $vente->reservation?->bien;

        if (($type === 'vendeur' && $bien && $bien->proprietaire_id !== $user->id) ||
            ($type === 'acheteur' && $vente->acheteur_id !== $user->id)) {

            if (!$user->hasRole('admin')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Non autorisé'
                ], 403);
            }
        }

        try {
            // ✅ Appeler le service de signature
            $result = $this->contractSignatureService->cancelSignature($vente, $type, 'vente');

            if ($result['success']) {
                // Régénérer le PDF sans cette signature
                $this->contractPdfService->regeneratePdf($vente, 'vente');

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'signatureStats' => $this->contractSignatureService->getSignatureStats($vente, 'vente')
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $result['message']
            ], 422);

        } catch (\Exception $e) {
            Log::error("❌ Erreur lors de l'annulation de signature", [
                'vente_id' => $vente->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de l\'annulation.'
            ], 500);
        }
    }
    /**
     * Statistiques de signature
     */
    public function getSignatureStats($id)
    {
        $vente = Vente::findOrFail($id);

        $stats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

        return response()->json([
            'vente_id' => $vente->id,
            'signatures' => $stats,
        ]);
    }
    public function edit(Vente $vente)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette vente.');
        }

        $vente->load(['bien.category', 'acheteur']);

        return Inertia::render('Ventes/Edit', [
            'vente' => $vente,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    public function update(Request $request, Vente $vente)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette vente.');
        }

        $request->validate([
            'prix_vente' => 'required|numeric|min:0',
            'date_vente' => 'required|date',
        ]);

        $vente->update([
            'prix_vente' => $request->prix_vente,
            'date_vente' => $request->date_vente,
        ]);

        return redirect()->route('ventes.show', $vente)
            ->with('success', 'Vente mise à jour avec succès.');
    }

    public function destroy(Vente $vente)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer cette vente.');
        }

        try {
            DB::transaction(function () use ($vente) {
                $bien = $vente->bien;

                if ($bien) {
                    $bien->update(['status' => 'disponible']);

                    if ($bien->mandat && $bien->mandat->statut === 'termine') {
                        $bien->mandat->update(['statut' => 'actif']);
                    }
                }

                $vente->delete();
            });

            return redirect()->route('ventes.index')
                ->with('success', 'Vente supprimée avec succès.');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
}
