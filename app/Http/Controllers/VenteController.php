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
use Illuminate\Support\Str;
use Inertia\Inertia;

class VenteController extends Controller
{
    protected $contractPdfService;
    protected $contractSignatureService;
    protected $propertyTransferService;


    public function __construct(
        ContractPdfService $contractPdfService,
        ContractElectronicSignatureService $contractSignatureService,
        PropertyTransferService $propertyTransferService // NOUVEAU
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractSignatureService = $contractSignatureService;
        $this->propertyTransferService = $propertyTransferService; // NOUVEAU

    }
    /**
     * Afficher la liste des ventes
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            // Admin voit toutes les ventes
            $ventes = Vente::with(['bien.category', 'acheteur', 'bien.proprietaire'])->latest()->get();
            $userType = 'admin';
        } else {
            // Récupérer les ventes où l'utilisateur est acheteur OU propriétaire du bien
            $ventes = Vente::with(['bien.category', 'acheteur', 'bien.proprietaire'])
                ->where(function($query) use ($user) {
                    $query->where('acheteur_id', $user->id) // Ventes comme acheteur
                    ->orWhereHas('bien', function($q) use ($user) {
                        $q->where('proprietaire_id', $user->id); // Ventes comme propriétaire
                    });
                })
                ->latest()
                ->get();
            $userType = 'client';
        }

        return Inertia::render('Ventes/Index', [
            'ventes' => $ventes->map(function ($vente) use ($user) {
                return [
                    ...$vente->toArray(),
                    'signature_stats' => $this->contractSignatureService->getSignatureStats($vente, 'vente'),
                    'can_sign' => $this->canUserSign($vente, $user),
                    'user_role_in_vente' => $this->getUserRoleInVente($vente, $user)
                ];
            }),
            'userRoles' => $user->roles->pluck('name'),
            'userType' => $userType,
        ]);
    }
    /**
     * Afficher le formulaire de création d'une vente
     */
    public function create(Request $request)
    {
        $bienId = $request->input('bien_id');
        if (!$bienId) {
            return redirect()->route('biens.index')
                ->with('error', 'Aucun bien spécifié pour la vente.');
        }

        $venteExistante = Auth::user()->ventes()
            ->where('biens_id', $bienId)
            ->exists();

        if ($venteExistante) {
            return redirect()->route('ventes.index')
                ->with('error', 'Vous avez déjà acheté ce bien.');
        }

        $bien = Bien::with(['category', 'proprietaire', 'mandat'])->findOrFail($bienId);

        $reservationConfirmee = Auth::user()->reservations()
            ->where('bien_id', $bienId)
            ->where('statut', 'confirmée')
            ->exists();

        if ($bien->status === 'reserve' && !$reservationConfirmee) {
            return redirect()->route('biens.show', $bienId)
                ->with('error', 'Ce bien est déjà réservé par un autre client.');
        }

        if (!$bien->mandat || $bien->mandat->type_mandat !== 'vente') {
            return redirect()->route('biens.show', $bienId)
                ->with('error', 'Ce bien n\'est pas disponible à la vente.');
        }

        return Inertia::render('Ventes/Create', [
            'bien' => $bien,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }

    /**
     * Enregistrer une nouvelle vente avec paiement préalable
     */
    public function store(Request $request)
    {
        $request->validate([
            'biens_id' => 'required|exists:biens,id',
            'prix_vente' => 'required|numeric|min:0',
            'date_vente' => 'required|date',
        ]);

        $user = Auth::user();
        $bien = Bien::with(['mandat', 'proprietaire'])->findOrFail($request->biens_id);

        // Vérifier que l'utilisateur n'est pas le propriétaire
        if ($bien->proprietaire_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas acheter votre propre bien.'
            ], 400);
        }

        // Vérifications existantes...
        if (!$bien->mandat || $bien->mandat->type_mandat !== 'vente' || $bien->mandat->statut !== 'actif') {
            return response()->json([
                'success' => false,
                'message' => 'Ce bien n\'est pas disponible à la vente.'
            ], 400);
        }

        $reservationConfirmee = Reservation::where('client_id', $user->id)
            ->where('bien_id', $request->biens_id)
            ->where('statut', 'confirmée')
            ->first();

        if (!$reservationConfirmee) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez avoir une réservation confirmée pour acheter ce bien.'
            ], 400);
        }

        if (Vente::where('biens_id', $request->biens_id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Une vente existe déjà pour ce bien.'
            ], 400);
        }

        try {
            // NOUVEAU : Créer d'abord la vente avec statut 'en_attente_paiement'
            $vente = DB::transaction(function () use ($request, $user) {
                return Vente::create([
                    'biens_id' => $request->biens_id,
                    'acheteur_id' => $user->id,
                    'prix_vente' => $request->prix_vente,
                    'date_vente' => $request->date_vente,
                    'status' => 'en_attente_paiement', // NOUVEAU statut
                ]);
            });

            // NOUVEAU : Créer l'enregistrement de paiement
            $paiement = Paiement::create([
                'type' => 'vente',
                'vente_id' => $vente->id,
                'montant_total' => $request->prix_vente,
                'montant_paye' => 0,
                'montant_restant' => $request->prix_vente,
                'commission_agence' => $request->prix_vente * 0.05,
                'mode_paiement' => 'carte', // Par défaut
                'transaction_id' => 'TXN_' . Str::upper(Str::random(10)) . '_' . time(),
                'statut' => 'reussi',
                'date_transaction' => now(),
            ]);

            // NOUVEAU : Rediriger vers l'interface de paiement
            return response()->json([
                'success' => true,
                'message' => 'Vente créée. Redirection vers le paiement...',
                'redirect_url' => route('paiement.initier.show', [
                    'type' => 'vente',
                    'id' => $vente->id,
                    'paiement_id' => $paiement->id
                ])
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur création vente:', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'bien_id' => $request->biens_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la vente : ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Afficher une vente avec options de signature
     */
    public function show(Vente $vente)
    {
        $user = Auth::user();

        if ($vente->acheteur_id !== $user->id &&
            $vente->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter cette vente.');
        }

        $vente->load(['bien.category', 'acheteur', 'bien.proprietaire', 'ancienProprietaire']); // NOUVEAU

        $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

        return Inertia::render('Ventes/Show', [
            'vente' => $vente,
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isAcheteur' => $vente->acheteur_id === $user->id,
            'isVendeur' => $vente->bien->proprietaire_id === $user->id || $vente->ancien_proprietaire_id === $user->id, // MODIFIÉ
            'isAdmin' => $user->hasRole('admin'),
            'propertyTransferred' => $vente->isPropertyTransferred(), // NOUVEAU
        ]);
    }

    /**
     * Page de signature du contrat de vente
     */
    public function showSignaturePage(Vente $vente)
    {
        $user = Auth::user();

        // Vérifier les permissions
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
     * Signature par le vendeur (propriétaire) - VERSION MISE À JOUR
     */
    public function signByVendeur(Request $request, Vente $vente)
    {
        $user = Auth::user();

        if ($vente->bien->proprietaire_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }

        if (!$this->contractSignatureService->canVenteBeSignedByVendeur($vente)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne peut pas être signé par le vendeur actuellement.'
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            $this->contractSignatureService->signVenteByVendeur($vente, $request->signature_data);

            $message = 'Contrat signé avec succès par le vendeur !';
            $venteRefresh = $vente->fresh();

            if ($venteRefresh->isFullySigned()) {
                // NOUVEAU : Déclencher le transfert de propriété automatique
                $transferSuccess = $this->propertyTransferService->transferProperty($venteRefresh);

                if ($transferSuccess) {
                    $message .= ' Le contrat est maintenant entièrement signé et la propriété a été transférée automatiquement !';
                    $venteRefresh->update(['statut' => 'confirmée']);
                } else {
                    $message .= ' Le contrat est entièrement signé mais le transfert de propriété a échoué. Contactez l\'administration.';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'signature_stats' => $this->contractSignatureService->getSignatureStats($venteRefresh, 'vente'),
                'property_transferred' => $venteRefresh->fresh()->isPropertyTransferred(), // NOUVEAU
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur signature vendeur:', [
                'vente_id' => $vente->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Signature par l'acheteur - VERSION MISE À JOUR
     */
    public function signByAcheteur(Request $request, Vente $vente)
    {
        $user = Auth::user();

        if ($vente->acheteur_id !== $user->id) {
            abort(403, 'Vous n\'êtes pas autorisé à signer ce contrat.');
        }

        if (!$this->contractSignatureService->canVenteBeSignedByAcheteur($vente)) {
            return response()->json([
                'success' => false,
                'message' => 'Ce contrat ne peut pas être signé par l\'acheteur actuellement.'
            ], 400);
        }

        $request->validate([
            'signature_data' => 'required|string',
        ]);

        try {
            $this->contractSignatureService->signVenteByAcheteur($vente, $request->signature_data);

            $message = 'Contrat signé avec succès par l\'acheteur !';
            $venteRefresh = $vente->fresh();

            if ($venteRefresh->isFullySigned()) {
                // NOUVEAU : Déclencher le transfert de propriété automatique
                $transferSuccess = $this->propertyTransferService->transferProperty($venteRefresh);

                if ($transferSuccess) {
                    $message .= ' Le contrat est maintenant entièrement signé et la propriété a été transférée automatiquement !';
                    $venteRefresh->update(['statut' => 'confirmée']);
                } else {
                    $message .= ' Le contrat est entièrement signé mais le transfert de propriété a échoué. Contactez l\'administration.';
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'signature_stats' => $this->contractSignatureService->getSignatureStats($venteRefresh, 'vente'),
                'property_transferred' => $venteRefresh->fresh()->isPropertyTransferred(), // NOUVEAU
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la signature : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le contrat PDF
     */
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

    /**
     * Prévisualiser le contrat PDF
     */
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
     * Annuler une signature
     */
    public function cancelSignature(Request $request, Vente $vente, $signatoryType)
    {
        $user = Auth::user();

        // Vérifier les permissions
        if ($signatoryType === 'vendeur' && $vente->bien->proprietaire_id !== $user->id) {
            abort(403, 'Vous ne pouvez annuler que votre propre signature.');
        }

        if ($signatoryType === 'acheteur' && $vente->acheteur_id !== $user->id) {
            abort(403, 'Vous ne pouvez annuler que votre propre signature.');
        }

        try {
            $this->contractSignatureService->cancelSignature($vente, $signatoryType, 'vente');

            return response()->json([
                'success' => true,
                'message' => 'Signature annulée avec succès.',
                'signature_stats' => $this->contractSignatureService->getSignatureStats($vente->fresh(), 'vente'),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation : ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Afficher le formulaire d'édition d'une vente
     */
    public function edit(Vente $vente)
    {
        $user = Auth::user();

        // Seuls les admins peuvent modifier une vente
        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cette vente.');
        }

        $vente->load(['bien.category', 'acheteur']);

        return Inertia::render('Ventes/Edit', [
            'vente' => $vente,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    /**
     * Mettre à jour une vente
     */
    public function update(Request $request, Vente $vente)
    {
        $user = Auth::user();

        // Seuls les admins peuvent modifier une vente
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

    /**
     * Supprimer une vente
     */
    public function destroy(Vente $vente)
    {
        $user = Auth::user();

        // Seuls les admins peuvent supprimer une vente
        if (!$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer cette vente.');
        }

        try {
            DB::transaction(function () use ($vente) {
                $bien = $vente->bien;

                // Remettre le bien en disponible
                if ($bien) {
                    $bien->update(['status' => 'disponible']);

                    // Réactiver le mandat si possible
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

    /**
     * Vérifier si un utilisateur peut signer une vente
     */
    private function canUserSign(Vente $vente, $user)
    {
        if ($vente->acheteur_id === $user->id && $vente->canBeSignedByAcheteur()) {
            return true;
        }

        if ($vente->bien->proprietaire_id === $user->id && $vente->canBeSignedByVendeur()) {
            return true;
        }

        return false;
    }

    /**
     * Obtenir le rôle de l'utilisateur dans la vente
     */
    private function getUserRoleInVente(Vente $vente, $user)
    {
        if ($vente->acheteur_id === $user->id) {
            return 'acheteur';
        }

        if ($vente->bien->proprietaire_id === $user->id) {
            return 'vendeur';
        }

        return null;
    }

    /**
     * NOUVELLE MÉTHODE : Obtenir l'historique des transferts d'un bien
     */
    public function getTransferHistory(Bien $bien)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin') && $bien->proprietaire_id !== $user->id) {
            abort(403, 'Accès non autorisé.');
        }

        $history = $this->propertyTransferService->getTransferHistory($bien);

        return response()->json([
            'transfer_history' => $history
        ]);
    }
}
