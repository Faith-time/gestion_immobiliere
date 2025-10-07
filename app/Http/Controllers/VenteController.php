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

    public function store(Request $request)
    {
        $request->validate([
            'biens_id' => 'required|exists:biens,id',
            'prix_vente' => 'required|numeric|min:0',
            'date_vente' => 'required|date',
        ]);

        $user = Auth::user();
        $bien = Bien::with('mandat')->find($request->biens_id);

        if (!$bien) {
            return back()->withErrors(['message' => 'Ce bien est introuvable.']);
        }

        if ($bien->proprietaire_id === $user->id) {
            return back()->withErrors(['message' => 'Vous ne pouvez pas acheter votre propre bien.']);
        }

        if (!$bien->mandat || $bien->mandat->type_mandat !== 'vente' || $bien->mandat->statut !== 'actif') {
            return back()->withErrors(['message' => 'Ce bien n\'est pas disponible à la vente.']);
        }

        if (!in_array($bien->status, ['disponible', 'reserve'])) {
            return back()->withErrors(['message' => 'Ce bien n\'est plus disponible à la vente.']);
        }

        if ($bien->status === 'reserve') {
            $reservationConfirmee = Reservation::where('bien_id', $bien->id)
                ->where('client_id', $user->id)
                ->where('statut', 'confirmée')
                ->first();

            if (!$reservationConfirmee) {
                return back()->withErrors(['message' => 'Ce bien est réservé par un autre client.']);
            }
        }

        $venteExistante = Vente::where('biens_id', $request->biens_id)
            ->where('acheteur_id', $user->id)
            ->first();

        if ($venteExistante) {
            $paiement = Paiement::where('vente_id', $venteExistante->id)
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->first();

            if ($paiement) {
                return redirect()->route('paiement.initier.show', [$venteExistante->id, $paiement->id]);
            }
        }

        $autreVente = Vente::where('biens_id', $request->biens_id)
            ->where('acheteur_id', '!=', $user->id)
            ->exists();

        if ($autreVente) {
            return back()->withErrors(['message' => 'Ce bien a déjà été vendu à un autre client.']);
        }

        try {
            $vente = DB::transaction(function () use ($request, $user) {
                return Vente::create([
                    'biens_id' => $request->biens_id,
                    'acheteur_id' => $user->id,
                    'prix_vente' => $request->prix_vente,
                    'date_vente' => $request->date_vente,
                ]);
            });

            $paiement = Paiement::create([
                'vente_id' => $vente->id,
                'type' => 'vente',
                'montant_total' => $vente->prix_vente,
                'montant_paye' => 0,
                'montant_restant' => $vente->prix_vente,
                'commission_agence' => $vente->prix_vente * 0.05,
                'mode_paiement' => null,
                'statut' => 'en_attente',
                'transaction_id' => null,
                'date_transaction' => now(),
            ]);

            return Inertia::location(route('paiement.initier.show', [$vente->id, $paiement->id]));

        } catch (\Exception $e) {
            Log::error('Erreur création vente', ['error' => $e->getMessage()]);
            return back()->withErrors(['message' => 'Erreur lors de la création de la vente.']);
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

        if (!$vente->bien) {
            return redirect()->route('ventes.index')
                ->with('error', 'Le bien associé à cette vente est introuvable.');
        }

        try {
            $paiement = Paiement::where('vente_id', $vente->id)
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->first();

            if (!$paiement) {
                $paiement = Paiement::create([
                    'vente_id' => $vente->id,
                    'type' => 'vente',
                    'montant_total' => $vente->prix_vente,
                    'montant_paye' => 0,
                    'montant_restant' => $vente->prix_vente,
                    'commission_agence' => $vente->prix_vente * 0.05,
                    'mode_paiement' => null,
                    'statut' => 'en_attente',
                    'transaction_id' => null,
                    'date_transaction' => now(),
                ]);
            }

            return redirect()->route('paiement.initier.show', [$vente->id, $paiement->id]);

        } catch (\Exception $e) {
            Log::error('Erreur initialisation paiement', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Erreur lors de la préparation du paiement.');
        }
    }

    public function index()
    {
        $user = auth()->user();
        $userId = $user->id;

        // Récupérer toutes les ventes liées à l'utilisateur
        $ventes = Vente::with([
            'bien.proprietaire',
            'bien.category',
            'acheteur',
            'paiement',
        ])
            ->where(function ($query) use ($userId) {
                $query->where('acheteur_id', $userId)
                    ->orWhereHas('bien', function ($q) use ($userId) {
                        $q->where('proprietaire_id', $userId);
                    });
            })
            ->where('status', Vente::STATUT_CONFIRMEE)
            ->orderBy('date_vente', 'desc')
            ->get()
            ->map(function ($vente) use ($userId) {
                // Déterminer le rôle de l'utilisateur pour cette vente
                $vente->user_role_in_vente = $vente->acheteur_id === $userId ? 'acheteur' : 'vendeur';

                // Statistiques de signature
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

                // Vérifier si l'utilisateur peut signer
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

    public function show(Vente $vente)
    {
        $user = Auth::user();

        if ($vente->acheteur_id !== $user->id &&
            $vente->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Vous n\'êtes pas autorisé à consulter cette vente.');
        }

        $vente->load(['bien.category', 'acheteur', 'bien.proprietaire', 'ancienProprietaire']);
        $signatureStats = $this->contractSignatureService->getSignatureStats($vente, 'vente');

        return Inertia::render('Ventes/Show', [
            'vente' => $vente,
            'signatureStats' => $signatureStats,
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
