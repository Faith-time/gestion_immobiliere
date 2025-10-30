<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Location;
use App\Services\CommissionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CommissionController extends Controller
{
    protected $commissionService;

    public function __construct(CommissionService $commissionService)
    {
        $this->commissionService = $commissionService;
    }

    public function index(Request $request)
    {
        $query = Commission::with(['commissionable', 'bien', 'paiement']);

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut') && $request->statut) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('mois_concerne') && $request->mois_concerne) {
            $date = Carbon::parse($request->mois_concerne);
            $query->duMois($date);
        }

        if ($request->has('bien_id') && $request->bien_id) {
            $query->where('bien_id', $request->bien_id);
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $commissions = $query->paginate(15);

        $stats = [
            'total_commissions' => Commission::sum('montant_commission'),
            'commissions_payees' => Commission::payee()->sum('montant_commission'),
            'commissions_en_attente' => Commission::enAttente()->sum('montant_commission'),
            'total_proprietaires' => Commission::sum('montant_net_proprietaire'),
        ];

        return Inertia::render('Commissions/Index', [
            'commissions' => $commissions,
            'stats' => $stats,
            'filters' => $request->only(['type', 'statut', 'mois_concerne', 'bien_id'])
        ]);
    }

    public function show($id)
    {
        $commission = Commission::with([
            'commissionable',
            'bien.proprietaire',
            'bien.mandat',
            'paiement'
        ])->findOrFail($id);

        return Inertia::render('Commissions/Show', [
            'commission' => $commission
        ]);
    }

    public function recapitulatifLocation($locationId)
    {
        $location = Location::with(['bien', 'client'])->findOrFail($locationId);
        $recapitulatif = $this->commissionService->getRecapitulatifLocation($location);

        return Inertia::render('Commissions/RecapitulatifLocation', [
            'location' => $location,
            'recapitulatif' => $recapitulatif
        ]);
    }

    public function marquerPayee(Request $request, $id)
    {
        $request->validate([
            'paiement_id' => 'nullable|exists:paiements,id'
        ]);

        try {
            DB::beginTransaction();

            $commission = Commission::findOrFail($id);

            if ($commission->statut === 'payee') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commission est déjà marquée comme payée.'
                ], 422);
            }

            $commission->marquerCommePaye($request->paiement_id);

            DB::commit();

            Log::info('Commission marquée comme payée', [
                'commission_id' => $commission->id,
                'montant' => $commission->montant_commission
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commission marquée comme payée avec succès.',
                'commission' => $commission->fresh()
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur marquage commission', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage de la commission.'
            ], 500);
        }
    }

    public function calculerRepartition(Request $request)
    {
        $request->validate([
            'montant_base' => 'required|numeric|min:0'
        ]);

        $repartition = Commission::calculerRepartition($request->montant_base);

        return response()->json([
            'success' => true,
            'repartition' => $repartition
        ]);
    }

    public function genererRenouvellement(Request $request, $locationId)
    {
        $request->validate([
            'nombre_mois' => 'required|integer|min:1|max:24'
        ]);

        try {
            DB::beginTransaction();

            $location = Location::findOrFail($locationId);
            $commissions = $this->commissionService->genererCommissionsRenouvellement(
                $location,
                $request->nombre_mois
            );

            DB::commit();

            Log::info('Commissions renouvellement générées', [
                'location_id' => $locationId,
                'nombre_mois' => $request->nombre_mois
            ]);

            return response()->json([
                'success' => true,
                'message' => sprintf('%d commission(s) générée(s).', count($commissions)),
                'commissions' => $commissions
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur génération commissions', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération des commissions.'
            ], 500);
        }
    }

    public function commissionsAVenir($locationId)
    {
        $location = Location::findOrFail($locationId);
        $nombreMois = request()->input('nombre_mois', 3);
        $commissions = $this->commissionService->getCommissionsAVenir($location, $nombreMois);

        return response()->json([
            'success' => true,
            'commissions' => $commissions
        ]);
    }

    public function commissionMoisCourant($locationId)
    {
        $location = Location::findOrFail($locationId);
        $date = request()->has('date') ? Carbon::parse(request()->date) : now();
        $commission = $this->commissionService->getCommissionMoisCourant($location, $date);

        if (!$commission) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune commission trouvée pour ce mois.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'commission' => $commission
        ]);
    }

    public function rapport(Request $request)
    {
        $request->validate([
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
            'type' => 'nullable|in:location,vente'
        ]);

        $dateDebut = Carbon::parse($request->date_debut);
        $dateFin = Carbon::parse($request->date_fin);

        $query = Commission::whereBetween('created_at', [$dateDebut, $dateFin]);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $commissions = $query->with(['bien', 'commissionable'])->get();

        $rapport = [
            'periode' => [
                'debut' => $dateDebut->format('d/m/Y'),
                'fin' => $dateFin->format('d/m/Y')
            ],
            'total_commissions' => $commissions->sum('montant_commission'),
            'total_base' => $commissions->sum('montant_base'),
            'total_proprietaires' => $commissions->sum('montant_net_proprietaire'),
            'commissions_payees' => $commissions->where('statut', 'payee')->sum('montant_commission'),
            'commissions_en_attente' => $commissions->where('statut', 'en_attente')->sum('montant_commission'),
            'nombre_total' => $commissions->count(),
            'nombre_payees' => $commissions->where('statut', 'payee')->count(),
            'nombre_en_attente' => $commissions->where('statut', 'en_attente')->count(),
            'par_type' => [
                'location' => [
                    'nombre' => $commissions->where('type', 'location')->count(),
                    'montant' => $commissions->where('type', 'location')->sum('montant_commission')
                ],
                'vente' => [
                    'nombre' => $commissions->where('type', 'vente')->count(),
                    'montant' => $commissions->where('type', 'vente')->sum('montant_commission')
                ]
            ],
            'details' => $commissions
        ];

        return Inertia::render('Commissions/Rapport', [
            'rapport' => $rapport,
            'filters' => $request->only(['date_debut', 'date_fin', 'type'])
        ]);
    }

    public function export(Request $request)
    {
        $request->validate([
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'type' => 'nullable|in:location,vente',
            'statut' => 'nullable|in:en_attente,payee,annulee'
        ]);

        $query = Commission::with(['bien', 'commissionable']);

        if ($request->has('date_debut') && $request->has('date_fin')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_debut),
                Carbon::parse($request->date_fin)
            ]);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $commissions = $query->orderBy('created_at', 'desc')->get();
        $filename = 'commissions_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($commissions) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID', 'Type', 'Bien', 'Mois Concerné', 'Montant Base',
                'Taux Commission', 'Montant Commission', 'Montant Net Propriétaire',
                'Statut', 'Date Paiement', 'Date Création'
            ]);

            foreach ($commissions as $commission) {
                fputcsv($file, [
                    $commission->id,
                    $commission->type,
                    $commission->bien ? $commission->bien->title : 'N/A',
                    $commission->mois_concerne ? $commission->mois_concerne->format('m/Y') : 'N/A',
                    $commission->montant_base,
                    $commission->taux_commission,
                    $commission->montant_commission,
                    $commission->montant_net_proprietaire,
                    $commission->statut,
                    $commission->date_paiement ? $commission->date_paiement->format('d/m/Y') : 'N/A',
                    $commission->created_at->format('d/m/Y H:i')
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function dashboard()
    {
        $moisCourant = now();
        $moisPrecedent = now()->subMonth();

        $stats = [
            'mois_courant' => [
                'total' => Commission::duMois($moisCourant)->sum('montant_commission'),
                'payees' => Commission::duMois($moisCourant)->payee()->sum('montant_commission'),
                'en_attente' => Commission::duMois($moisCourant)->enAttente()->sum('montant_commission'),
            ],
            'mois_precedent' => [
                'total' => Commission::duMois($moisPrecedent)->sum('montant_commission'),
            ],
            'global' => [
                'total_commissions' => Commission::sum('montant_commission'),
                'commissions_payees' => Commission::payee()->sum('montant_commission'),
                'commissions_en_attente' => Commission::enAttente()->sum('montant_commission'),
            ],
            'par_type' => [
                'location' => Commission::location()->sum('montant_commission'),
                'vente' => Commission::vente()->sum('montant_commission'),
            ]
        ];

        $commissionsRecentes = Commission::with(['bien', 'commissionable'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return Inertia::render('Commissions/Dashboard', [
            'stats' => $stats,
            'commissionsRecentes' => $commissionsRecentes
        ]);
    }
}
