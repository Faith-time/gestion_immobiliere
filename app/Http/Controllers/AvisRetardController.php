<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\AvisRetard;
use App\Services\NotificationLoyerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AvisRetardController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationLoyerService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Afficher le tableau de bord des avis de retard
     */
    public function index()
    {
        $avisRetards = AvisRetard::with(['location.reservation.bien', 'location.client'])
            ->latest()
            ->paginate(20);

        return Inertia::render('AvisRetard/Index', [
            'avisRetards' => $avisRetards,
            'statistiques' => $this->getStatistiques()
        ]);
    }

    /**
     * ✅ MODIFIÉ : Utiliser le nouveau service
     */
    public function envoyerRappels()
    {
        try {
            $result = $this->notificationService->envoyerRappelsMensuels();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi rappels', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des rappels'
            ], 500);
        }
    }

    /**
     * ✅ MODIFIÉ : Utiliser le nouveau service
     */
    public function envoyerAvisRetards()
    {
        try {
            $result = $this->notificationService->envoyerAvisRetards();

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('❌ Erreur envoi avis retard', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'envoi des avis de retard'
            ], 500);
        }
    }

    /**
     * Traiter les notifications automatiquement
     */
    public function traiterNotificationsAutomatiques()
    {
        try {
            DB::beginTransaction();

            $rappelsResult = $this->notificationService->envoyerRappelsMensuels();
            $avisResult = $this->notificationService->envoyerAvisRetards();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Notifications traitées avec succès',
                'rappels' => $rappelsResult['rappels_envoyes'] ?? 0,
                'avis' => $avisResult['avis_envoyes'] ?? 0
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Erreur traitement notifications', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement des notifications'
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des avis
     */
    private function getStatistiques()
    {
        return [
            'total_avis' => AvisRetard::count(),
            'rappels_envoyes' => AvisRetard::where('type', 'rappel')->count(),
            'avis_retard' => AvisRetard::where('type', 'retard')->count(),
            'en_attente_paiement' => AvisRetard::where('statut', 'envoye')->count(),
            'payes' => AvisRetard::where('statut', 'paye')->count(),
        ];
    }

    /**
     * Marquer un avis comme payé
     */
    public function marquerPaye(AvisRetard $avisRetard)
    {
        $avisRetard->update([
            'statut' => 'paye',
            'date_paiement' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Avis marqué comme payé'
        ]);
    }

    /**
     * Afficher les détails d'un avis
     */
    public function show(AvisRetard $avisRetard)
    {
        $avisRetard->load(['location.reservation.bien', 'location.client']);

        return Inertia::render('AvisRetard/Show', [
            'avisRetard' => $avisRetard
        ]);
    }
}
