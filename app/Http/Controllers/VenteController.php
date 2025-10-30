<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Bien;
use App\Models\Reservation;
use App\Services\ContractElectronicSignatureService;
use App\Services\ContractPdfService;
use App\Services\PropertyTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VenteController extends Controller
{
    protected $contractPdfService;
    protected $contractSignatureService;
    protected $propertyTransferService;

    public function __construct(
        ContractPdfService $contractPdfService,
        ContractElectronicSignatureService $contractSignatureService,
        PropertyTransferService $propertyTransferService
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractSignatureService = $contractSignatureService;
        $this->propertyTransferService = $propertyTransferService;
    }

    public function create(Request $request)
    {
        $reservationId = $request->input('reservation_id');
        if (!$reservationId) {
            return redirect()->route('reservations.index')
                ->with('error', 'Aucune réservation spécifiée pour la vente.');
        }

        $reservation = Reservation::with(['bien.category', 'bien.proprietaire', 'bien.mandat', 'client'])
            ->findOrFail($reservationId);

        // Vérifier que la réservation est confirmée
        if ($reservation->statut !== 'confirmée') {
            return redirect()->route('reservations.show', $reservationId)
                ->with('error', 'La réservation doit être confirmée avant de procéder à la vente.');
        }

        $bien = $reservation->bien;

        // ✅ NOUVEAU : Vérifier que l'utilisateur n'est pas le propriétaire
        if ($bien->proprietaire_id === Auth::id()) {
            return redirect()->route('biens.show', $bien->id)
                ->with('error', '❌ Vous ne pouvez pas acheter votre propre bien.');
        }

        // Vérifier qu'une vente n'existe pas déjà
        $venteExistante = Auth::user()->ventes()
            ->where('reservation_id', $reservationId)
            ->exists();

        if ($venteExistante) {
            return redirect()->route('ventes.index')
                ->with('error', 'Vous avez déjà acheté ce bien via cette réservation.');
        }

        if (!$bien->mandat || $bien->mandat->type_mandat !== 'vente') {
            return redirect()->route('reservations.show', $reservationId)
                ->with('error', 'Ce bien n\'est pas disponible à la vente.');
        }

        return Inertia::render('Ventes/Create', [
            'bien' => $bien,
            'reservation' => $reservation,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }

    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        $ventes = Vente::with([
            'reservation.bien.proprietaire',
            'reservation.bien.category',
            'reservation.bien.images',
            'acheteur',
            'paiement',
        ])
            ->where(function ($query) use ($userId) {
                $query->where('acheteur_id', $userId)
                    ->orWhereHas('reservation.bien', function ($q) use ($userId) {
                        $q->where('proprietaire_id', $userId);
                    });
            })
            ->orderBy('date_vente', 'desc')
            ->get()
            ->filter(function ($vente) {
                // ✅ N'afficher que les ventes complètement payées
                return $vente->paiement &&
                    $vente->paiement->statut === 'reussi' &&
                    $vente->paiement->montant_restant <= 0;
            })
            ->map(function ($vente) use ($userId) {
                $vente->user_role_in_vente = $vente->acheteur_id === $userId ? 'acheteur' : 'vendeur';
                $vente->bien = $vente->reservation->bien ?? null;

                $isVendeurSigned = $vente->isSignedByVendeur();
                $isAcheteurSigned = $vente->isSignedByAcheteur();
                $signaturesCompleted = ($isVendeurSigned ? 1 : 0) + ($isAcheteurSigned ? 1 : 0);

                $vente->signature_stats = [
                    'total' => 2,
                    'completed' => $signaturesCompleted,
                    'signature_status' => match ($signaturesCompleted) {
                        0 => 'non_signe',
                        2 => 'entierement_signe',
                        default => 'partiellement_signe',
                    },
                    'fully_signed' => $signaturesCompleted === 2,
                ];

                $vente->can_sign = $vente->acheteur_id === $userId
                    ? $vente->canBeSignedByAcheteur()
                    : $vente->canBeSignedByVendeur();

                return $vente;
            });

        return Inertia::render('Ventes/Index', [
            'ventes' => $ventes,
            'userRoles' => $user->roles->pluck('name'),
            'userType' => $ventes->first()?->user_role_in_vente ?? 'client',
        ]);
    }


    public function store(Request $request)
    {
        try {
            // Nettoyer les anciens messages flash
            session()->forget(['error', 'success', 'warning', 'info']);

            Log::info('📥 Réception données vente:', $request->all());

            // Validation des données
            $validated = $request->validate([
                'reservation_id' => 'required|exists:reservations,id',
                'prix_vente' => 'required|numeric|min:0',
                'date_vente' => 'required|date',
            ]);

            // Charger la réservation avec ses relations
            $reservation = Reservation::with(['bien.mandat', 'client'])->findOrFail($validated['reservation_id']);

            // Vérifier que l'utilisateur est bien le client de la réservation
            if ($reservation->client_id !== auth()->id()) {
                return back()->withErrors([
                    'autorisation' => 'Vous n\'êtes pas autorisé à acheter ce bien.'
                ]);
            }

            // Vérifier si le bien existe
            if (!$reservation->bien) {
                return back()->withErrors([
                    'bien' => 'Le bien associé à cette réservation n\'existe pas.'
                ]);
            }

            // ✅ Vérifier que l'acheteur n'est pas le propriétaire
            if ($reservation->bien->proprietaire_id === auth()->id()) {
                return back()->withErrors([
                    'proprietaire' => '❌ Vous ne pouvez pas acheter votre propre bien.'
                ]);
            }

            // Vérifier si le bien n'est pas déjà vendu
            if ($reservation->bien->status === 'vendu') {
                return back()->withErrors([
                    'bien' => 'Ce bien a déjà été vendu.'
                ]);
            }

            // ✅ CALCUL DU MONTANT À PAYER (90% du prix car 10% déjà payé en dépôt)
            $prixVenteTotal = $validated['prix_vente'];
            $depotGarantie = $prixVenteTotal * 0.10; // 10% déjà payé lors de la réservation
            $montantRestantAPayer = $prixVenteTotal - $depotGarantie; // 90% restant

            Log::info('💰 Calcul montant vente', [
                'prix_vente_total' => $prixVenteTotal,
                'depot_10%_deja_paye' => $depotGarantie,
                'montant_restant_90%' => $montantRestantAPayer
            ]);

            // Vérifier si une vente existe déjà pour cette réservation
            $venteExistante = Vente::where('reservation_id', $validated['reservation_id'])
                ->whereIn('status', ['confirmée', 'en_attente_paiement'])
                ->first();

            if ($venteExistante) {
                Log::info('✅ Vente existante trouvée', [
                    'vente_id' => $venteExistante->id,
                    'status' => $venteExistante->status
                ]);

                // Récupérer ou créer le paiement
                $paiement = Paiement::where('vente_id', $venteExistante->id)->first();

                if (!$paiement) {
                    // ✅ Créer le paiement avec le montant restant (90%)
                    $paiement = Paiement::create([
                        'vente_id' => $venteExistante->id,
                        'type' => 'vente',
                        'montant_total' => $montantRestantAPayer, // ✅ 90% seulement
                        'montant_paye' => 0,
                        'montant_restant' => $montantRestantAPayer,
                        'commission_agence' => $prixVenteTotal * 0.05, // Commission sur le prix total
                        'statut' => 'en_attente',
                        'mode_paiement' => 'orange_money',
                    ]);

                    Log::info('💳 Paiement créé pour vente existante', [
                        'montant_a_payer' => $montantRestantAPayer,
                        'pourcentage' => '90%'
                    ]);
                } else {
                    // ✅ Vérifier et corriger le montant si nécessaire
                    if ($paiement->montant_total == $prixVenteTotal) {
                        $paiement->update([
                            'montant_total' => $montantRestantAPayer,
                            'montant_restant' => $montantRestantAPayer - $paiement->montant_paye
                        ]);

                        Log::info('🔄 Montant paiement corrigé', [
                            'ancien_montant' => $prixVenteTotal,
                            'nouveau_montant' => $montantRestantAPayer
                        ]);
                    }
                }

                return redirect()
                    ->route('paiement.initier.show', [
                        'id' => $venteExistante->id,
                        'paiement_id' => $paiement->id
                    ])
                    ->with('info', 'Une vente existe déjà pour ce bien. Continuez le paiement des 90% restants.');
            }

            // ✅ Créer une nouvelle vente
            Log::info('📝 Création d\'une nouvelle vente...');

            $vente = DB::transaction(function () use ($validated, $reservation, $prixVenteTotal, $montantRestantAPayer) {
                $vente = Vente::create([
                    'reservation_id' => $validated['reservation_id'],
                    'acheteur_id' => auth()->id(),
                    'prix_vente' => $prixVenteTotal, // ✅ Prix complet du bien
                    'date_vente' => $validated['date_vente'],
                    'status' => Vente::STATUT_EN_ATTENTE_PAIEMENT,
                    'ancien_proprietaire_id' => $reservation->bien->proprietaire_id,
                ]);

                Log::info('✅ Vente créée', [
                    'vente_id' => $vente->id,
                    'acheteur_id' => $vente->acheteur_id,
                    'prix_vente_total' => $vente->prix_vente,
                    'status' => $vente->status
                ]);

                // ✅ Créer le paiement avec le montant restant (90%)
                Paiement::create([
                    'vente_id' => $vente->id,
                    'type' => 'vente',
                    'montant_total' => $montantRestantAPayer, // ✅ 90% seulement
                    'montant_paye' => 0,
                    'montant_restant' => $montantRestantAPayer,
                    'commission_agence' => $prixVenteTotal * 0.05, // Commission sur le prix total
                    'statut' => 'en_attente',
                    'mode_paiement' => 'orange_money',
                    'date_transaction' => now(),
                ]);

                Log::info('💳 Paiement créé', [
                    'prix_vente_total' => $prixVenteTotal,
                    'depot_deja_paye_10%' => $prixVenteTotal * 0.10,
                    'montant_a_payer_90%' => $montantRestantAPayer
                ]);

                return $vente;
            });

            // Récupérer le paiement créé
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

        } catch (\Exception $e) {
            Log::error('❌ Erreur création vente', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->withErrors([
                'general' => 'Une erreur est survenue lors de la création de la vente.'
            ]);
        }
    }

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
                ->with('error', 'Cette vente a déjà été payée intégralement.');
        }

        if (!$vente->reservation || !$vente->reservation->bien) {
            return redirect()->route('ventes.index')
                ->with('error', 'Le bien associé à cette vente est introuvable.');
        }

        try {
            // ✅ Calculer le montant restant (90% du prix)
            $prixVenteTotal = $vente->prix_vente;
            $depotGarantie = $prixVenteTotal * 0.10; // 10% déjà payé
            $montantRestantAPayer = $prixVenteTotal - $depotGarantie; // 90%

            $paiement = Paiement::where('vente_id', $vente->id)
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->first();

            if (!$paiement) {
                // ✅ Créer le paiement avec 90% du prix
                $paiement = Paiement::create([
                    'vente_id' => $vente->id,
                    'type' => 'vente',
                    'montant_total' => $montantRestantAPayer, // ✅ 90% seulement
                    'montant_paye' => 0,
                    'montant_restant' => $montantRestantAPayer,
                    'commission_agence' => $prixVenteTotal * 0.05,
                    'mode_paiement' => 'orange_money',
                    'statut' => 'en_attente',
                    'transaction_id' => null,
                    'date_transaction' => now(),
                ]);

                Log::info('💳 Paiement créé pour vente', [
                    'prix_vente_total' => $prixVenteTotal,
                    'depot_10%' => $depotGarantie,
                    'montant_a_payer_90%' => $montantRestantAPayer
                ]);
            } else {
                // ✅ Vérifier et corriger le montant si nécessaire
                if ($paiement->montant_total == $prixVenteTotal) {
                    $paiement->update([
                        'montant_total' => $montantRestantAPayer,
                        'montant_restant' => $montantRestantAPayer - $paiement->montant_paye
                    ]);

                    Log::info('🔄 Montant paiement vente corrigé', [
                        'ancien_montant' => $prixVenteTotal,
                        'nouveau_montant' => $montantRestantAPayer
                    ]);
                }
            }

            return redirect()->route('paiement.initier.show', [$vente->id, $paiement->id])
                ->with('info', 'Vous devez payer 90% du prix (10% déjà versés en dépôt de réservation).');

        } catch (\Exception $e) {
            Log::error('Erreur initialisation paiement', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erreur lors de la préparation du paiement.');
        }
    }

    public function show(Vente $vente)
    {
        $user = Auth::user();

        // Vérifier les autorisations
        if ($vente->acheteur_id !== $user->id &&
            $vente->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter cette vente.');
        }

        // Charger toutes les relations nécessaires
        $vente->load([
            'bien.category',
            'bien.images',
            'bien.proprietaire',
            'acheteur',
            'ancienProprietaire',
            'reservation.bien.mandat',
            'paiement'
        ]);

        // Obtenir les statistiques de signature
        $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

        // Calculer le statut de la transaction
        $transactionStatus = $vente->getTransactionStatus();

        Log::info('📄 Affichage détails vente', [
            'vente_id' => $vente->id,
            'user_id' => $user->id,
            'transaction_status' => $transactionStatus
        ]);

        return Inertia::render('Ventes/Show', [
            'vente' => $vente,
            'signatureStats' => $signatureStats,
            'transactionStatus' => $transactionStatus,
            'userRoles' => $user->roles->pluck('name'),
            'isAcheteur' => $vente->acheteur_id === $user->id,
            'isVendeur' => $vente->bien->proprietaire_id === $user->id || $vente->ancien_proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
            'propertyTransferred' => $vente->isPropertyTransferred(),
        ]);
    }
    public function showSignaturePage(Vente $vente)
    {
        $user = Auth::user();

        if ($vente->acheteur_id !== $user->id &&
            $vente->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }

        $vente->load(['bien.category', 'acheteur', 'bien.proprietaire']);
        $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

        return Inertia::render('Contrats/SignatureVente', [
            'vente' => $vente,
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isAcheteur' => $vente->acheteur_id === $user->id,
            'isVendeur' => $vente->bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    /**
     * Signature par le vendeur
     */
    public function signByVendeur(Request $request, $id)
    {
        $vente = Vente::findOrFail($id);

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            // 👉 Délégation totale au service
            $this->contractSignatureService->signVenteByVendeur($vente, $request->signature_data);

            return response()->json([
                'message' => 'Vente signée par le vendeur avec succès.',
                'vente' => $vente->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Erreur signature vendeur : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la signature.'], 500);
        }
    }

    /**
     * Signature par l’acheteur
     */
    public function signByAcheteur(Request $request, $id)
    {
        $vente = Vente::findOrFail($id);

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            // 👉 Délégation totale au service
            $this->contractSignatureService->signVenteByAcheteur($vente, $request->signature_data);

            // Si la vente est entièrement signée, on transfère la propriété
            if ($vente->isFullySigned()) {
                $this->propertyTransferService->transferPropertyToBuyer($vente);
            }

            return response()->json([
                'message' => 'Vente signée par l’acheteur avec succès.',
                'vente' => $vente->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Erreur signature acheteur : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de la signature.'], 500);
        }
    }

    public function downloadContract(Vente $vente)
    {
        $user = Auth::user();

        if ($vente->acheteur_id !== $user->id &&
            $vente->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à télécharger ce contrat.');
        }

        $response = $this->contractPdfService->downloadPdf($vente, 'vente');

        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de télécharger le contrat.');
        }

        return $response;
    }

    public function previewContract(Vente $vente)
    {
        $user = Auth::user();

        if ($vente->acheteur_id !== $user->id &&
            $vente->bien->proprietaire_id !== $user->id &&
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
    public function cancelSignature(Request $request, $id)
    {
        $vente = Vente::findOrFail($id);

        $request->validate([
            'signatory_type' => 'required|in:vendeur,acheteur',
            'type_contrat' => 'required|string',
        ]);

        try {
            // 👉 Appel direct du service
            $this->contractSignatureService->cancelSignature(
                $vente,
                $request->signatory_type,
                $request->type_contrat
            );

            return response()->json([
                'message' => 'Signature annulée avec succès.',
                'vente' => $vente->fresh(),
            ], 200);
        } catch (\Exception $e) {
            Log::error("Erreur lors de l’annulation : " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de l’annulation.'], 500);
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
