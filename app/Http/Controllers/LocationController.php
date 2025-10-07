<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Location;
use App\Models\Reservation;
use App\Services\ContractNotificationService;
use App\Services\ContractPdfService;
use App\Services\ContractElectronicSignatureService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LocationController extends Controller
{
    protected $contractPdfService;
    protected $contractSignatureService;
    protected $contractNotificationService; // NOUVEAU

    public function __construct(
        ContractPdfService $contractPdfService,
        ContractElectronicSignatureService $contractSignatureService,
        ContractNotificationService $contractNotificationService // NOUVEAU
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractSignatureService = $contractSignatureService;
        $this->contractNotificationService = $contractNotificationService; // NOUVEAU
    }

    /**
     * Signature par le bailleur (propriétaire)
     */
    public function signByBailleur(Request $request, Location $location)
    {
        $user = Auth::user();

        if ($location->bien->proprietaire_id !== $user->id) {
            abort(403, 'Non autorisé.');
        }

        if (!$this->contractSignatureService->canLocationBeSignedByBailleur($location)) {
            return response()->json(['success' => false, 'message' => 'Impossible de signer maintenant.'], 400);
        }

        $request->validate(['signature_data' => 'required|string']);

        try {
            $this->contractSignatureService->signLocationByBailleur($location, $request->signature_data);

            $location->refresh();
            $message = 'Contrat signé avec succès par le propriétaire !';

            if ($location->signature_status === 'entierement_signe') {
                $message .= ' Le contrat est maintenant entièrement signé. Les notifications de test seront envoyées dans 5 et 10 minutes.';
                $location->update(['statut' => 'active']);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'signature_stats' => $this->contractSignatureService->getSignatureStats($location, 'location'),
                'notifications_programmees' => $location->signature_status === 'entierement_signe'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Signature par le locataire
     */
    public function signByLocataire(Request $request, Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id) {
            abort(403, 'Non autorisé.');
        }

        if (!$this->contractSignatureService->canLocationBeSignedByLocataire($location)) {
            return response()->json(['success' => false, 'message' => 'Impossible de signer maintenant.'], 400);
        }

        $request->validate(['signature_data' => 'required|string']);

        try {
            $this->contractSignatureService->signLocationByLocataire($location, $request->signature_data);

            $location->refresh();
            $message = 'Contrat signé avec succès par le locataire !';

            if ($location->signature_status === 'entierement_signe') {
                $message .= ' Le contrat est maintenant entièrement signé. Les notifications de test seront envoyées dans 5 et 10 minutes.';
                $location->update(['statut' => 'active']);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'signature_stats' => $this->contractSignatureService->getSignatureStats($location, 'location'),
                'notifications_programmees' => $location->signature_status === 'entierement_signe'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * NOUVEAU: Méthode pour tester manuellement les notifications
     */
    public function testNotifications(Request $request, Location $location)
    {
        $user = Auth::user();

        // Vérifier les autorisations
        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé.');
        }

        $request->validate([
            'delai_rappel' => 'nullable|integer|min:1|max:60',
            'delai_avis' => 'nullable|integer|min:1|max:120'
        ]);

        try {
            $delaiRappel = $request->delai_rappel ?? 5;
            $delaiAvis = $request->delai_avis ?? 10;

            $success = $this->contractNotificationService->programmerNotificationsPersonnalisees(
                $location,
                $delaiRappel,
                $delaiAvis
            );

            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => "Notifications de test programmées : rappel dans {$delaiRappel} min, avis dans {$delaiAvis} min",
                    'delai_rappel' => $delaiRappel,
                    'delai_avis' => $delaiAvis
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Le contrat doit être entièrement signé pour programmer les notifications'
                ], 400);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la programmation : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister les locations
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $locations = Location::with(['bien.category', 'client', 'bien.proprietaire'])
                ->latest()
                ->get();
        } else {
            // Locations où l'utilisateur est soit le client, soit le propriétaire du bien
            $locations = Location::with(['bien.category', 'client', 'bien.proprietaire'])
                ->where(function($query) use ($user) {
                    $query->where('client_id', $user->id) // Utilisateur est locataire
                    ->orWhereHas('bien', function($q) use ($user) {
                        $q->where('proprietaire_id', $user->id); // Utilisateur est propriétaire
                    });
                })
                ->latest()
                ->get();
        }

        return Inertia::render('Locations/Index', [
            'locations' => $locations->map(function ($location) {
                return [
                    ...$location->toArray(),
                    'signature_stats' => $this->contractSignatureService->getSignatureStats($location, 'location'),
                    'can_sign' => $this->canUserSign($location, Auth::user()),
                    'user_role_in_location' => $this->getUserRoleInLocation($location, Auth::user())
                ];
            }),
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    /**
     * Créer une nouvelle location avec redirection vers PayDunya
     */
    public function store(Request $request)
    {
        $request->validate([
            'bien_id' => 'required|exists:biens,id',
            'date_debut' => 'required|date|after:today',
            'duree_mois' => 'required|integer|min:1|max:120',
        ]);

        $user = Auth::user();
        $bien = Bien::with(['mandat', 'proprietaire'])->find($request->bien_id);

        // Vérifications
        if ($bien->proprietaire_id === $user->id) {
            return back()->withErrors(['message' => '❌ Vous ne pouvez pas louer votre propre bien.']);
        }

        $maLocationExistante = Location::where('bien_id', $request->bien_id)
            ->where('client_id', $user->id)
            ->whereIn('statut', ['active', 'en_cours'])
            ->first();

        if ($maLocationExistante) {
            return redirect()->route('locations.show', $maLocationExistante->id)
                ->withErrors(['message' => '❌ Vous avez déjà une location active pour ce bien.']);
        }

        $locationAutreClient = Location::where('bien_id', $request->bien_id)
            ->where('client_id', '!=', $user->id)
            ->whereIn('statut', ['active', 'en_cours'])
            ->exists();

        if ($locationAutreClient) {
            return back()->withErrors(['message' => '❌ Ce bien est déjà loué par un autre client.']);
        }

        if (!$bien || !$bien->mandat || $bien->mandat->type_mandat !== 'gestion_locative' || $bien->mandat->statut !== 'actif') {
            return back()->withErrors(['message' => '❌ Ce bien n\'est pas disponible à la location.']);
        }

        $reservationConfirmee = Reservation::where('client_id', $user->id)
            ->where('bien_id', $request->bien_id)
            ->where('statut', 'confirmée')
            ->first();

        if (!$reservationConfirmee) {
            return back()->withErrors(['message' => '❌ Vous devez avoir une réservation confirmée pour louer ce bien.']);
        }

        if (!in_array($bien->status, ['disponible', 'reserve'])) {
            return back()->withErrors(['message' => '❌ Ce bien ne peut pas être loué.']);
        }

        // Dans la méthode store() du LocationController
        try {
            $location = DB::transaction(function () use ($request, $user, $bien) {
                $dateDebut = Carbon::parse($request->date_debut);
                $dateFin = $dateDebut->copy()->addMonths((int) $request->duree_mois);

                return Location::create([
                    'bien_id' => $request->bien_id,
                    'client_id' => $user->id,
                    'loyer_mensuel' => $bien->price,
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                    'statut' => 'en_attente_paiement',
                    'signature_status' => 'non_signe',
                ]);
            });

            // Calculer le montant total (premier mois + caution de 2 mois)
            $montantTotal = ($bien->price * 1) + ($bien->price * 2);

            // Créer le paiement
            $paiement = Paiement::create([
                'type' => 'location',
                'location_id' => $location->id,
                'montant_total' => $montantTotal,
                'montant_paye' => 0,
                'montant_restant' => $montantTotal,
                'commission_agence' => $montantTotal * 0.05,
                'mode_paiement' => 'en_attente',
                'transaction_id' => 'LOC_' . $location->id . '_' . time(),
                'statut' => 'en_attente',
                'date_transaction' => now(),
            ]);

            return Inertia::location(route('paiement.initier.show', [
                'type' => 'location',
                'id' => $location->id,
                'paiement_id' => $paiement->id
            ]));

        } catch (\Exception $e) {
            // ...
        }
    }
    /**
     * Détails d'une location
     */
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
            'location' => $location,
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isLocataire' => $location->client_id === $user->id,
            'isBailleur' => $location->bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    /**
     * Page de signature contrat location
     */
    public function showSignaturePage(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé à signer ce contrat.');
        }

        // Vérifier que le client n'est pas le propriétaire
        if ($location->client_id === $location->bien->proprietaire_id) {
            abort(403, 'Incohérence détectée : le locataire ne peut pas être le propriétaire.');
        }

        $location->load(['bien.category', 'client', 'bien.proprietaire']);
        $signatureStats = $this->contractSignatureService->getSignatureStats($location, 'location');

        return Inertia::render('Contrats/SignatureLocation', [
            'location' => $location,
            'signatureStats' => $signatureStats,
            'userRoles' => $user->roles->pluck('name'),
            'isLocataire' => $location->client_id === $user->id,
            'isBailleur' => $location->bien->proprietaire_id === $user->id,
            'isAdmin' => $user->hasRole('admin'),
        ]);
    }

    /**
     * Télécharger le contrat
     */
    public function downloadContract(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé à télécharger.');
        }

        $response = $this->contractPdfService->downloadPdf($location, 'location');
        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de télécharger le contrat.');
        }

        return $response;
    }

    /**
     * Prévisualiser le contrat
     */
    public function previewContract(Location $location)
    {
        $user = Auth::user();

        if ($location->client_id !== $user->id &&
            $location->bien->proprietaire_id !== $user->id &&
            !$user->hasRole('admin')) {
            abort(403, 'Non autorisé à prévisualiser.');
        }

        $response = $this->contractPdfService->previewPdf($location, 'location');
        if (!$response) {
            return redirect()->back()->with('error', 'Impossible de prévisualiser le contrat.');
        }

        return $response;
    }

    /**
     * Annuler signature
     */
    public function cancelSignature(Request $request, Location $location, $signatoryType)
    {
        $user = Auth::user();

        if ($signatoryType === 'bailleur' && $location->bien->proprietaire_id !== $user->id) {
            abort(403, 'Vous ne pouvez annuler que votre propre signature.');
        }

        if ($signatoryType === 'locataire' && $location->client_id !== $user->id) {
            abort(403, 'Vous ne pouvez annuler que votre propre signature.');
        }

        try {
            $this->contractSignatureService->cancelSignature($location, $signatoryType, 'location');

            return response()->json([
                'success' => true,
                'message' => 'Signature annulée avec succès.',
                'signature_stats' => $this->contractSignatureService->getSignatureStats($location->fresh(), 'location'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()], 500);
        }
    }

    /**
     * Vérifier si un utilisateur peut signer une location
     */
    private function canUserSign(Location $location, $user)
    {
        if ($location->client_id === $user->id && $location->canBeSignedByLocataire()) {
            return true;
        }

        if ($location->bien->proprietaire_id === $user->id && $location->canBeSignedByBailleur()) {
            return true;
        }

        return false;
    }

    /**
     * Obtenir le rôle de l'utilisateur dans la location
     */
    private function getUserRoleInLocation(Location $location, $user)
    {
        if ($location->client_id === $user->id) {
            return 'locataire';
        }

        if ($location->bien->proprietaire_id === $user->id) {
            return 'bailleur';
        }

        return null;
    }

    /**
     * Afficher le formulaire de création d'une location
     */
    public function create(Request $request)
    {
        $bien = null;

        if ($request->has('bien_id')) {
            $bien = Bien::find($request->bien_id);
        }

        return inertia('Locations/Create', [
            'bien' => $bien,
        ]);
    }


}
