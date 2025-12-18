<?php

namespace App\Http\Controllers;

use App\Models\Visite;
use App\Models\Bien;
use App\Models\Appartement;
use App\Services\VisiteConfirmationService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class VisiteController extends Controller
{
    protected $confirmationService;

    public function __construct(VisiteConfirmationService $confirmationService)
    {
        $this->confirmationService = $confirmationService;
    }

    /**
     * ✅ Liste des visites - VERSION SIMPLIFIÉE
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            $visites = Visite::with([
                'bien.category',
                'bien.mandat',
                'appartement',
                'client'
            ])
                ->orderBy('date_visite', 'desc')
                ->get()
                ->map(function ($visite) {
                    return [
                        'id' => $visite->id,
                        'statut' => $visite->statut,
                        'date_visite' => $visite->date_visite,
                        'message' => $visite->message,
                        'created_at' => $visite->created_at,

                        'bien' => [
                            'id' => $visite->bien->id,
                            'title' => $visite->bien->title,
                            'address' => $visite->bien->address,
                            'city' => $visite->bien->city,
                            'type' => $visite->bien->category->name ?? 'N/A',
                            'type_mandat' => $visite->bien->mandat->type_mandat ?? null,
                        ],

                        'appartement' => $visite->appartement ? [
                            'id' => $visite->appartement->id,
                            'numero' => $visite->appartement->numero,
                            'etage' => $visite->appartement->etage,
                            'etage_label' => $visite->appartement->getEtageLabel(),
                            'superficie' => $visite->appartement->superficie,
                            'pieces' => ($visite->appartement->salons + $visite->appartement->chambres),
                        ] : null,

                        'client' => [
                            'id' => $visite->client->id,
                            'name' => $visite->client->name,
                            'email' => $visite->client->email,
                            'telephone' => $visite->client->telephone ?? $visite->client->phone ?? 'N/A',
                        ],
                    ];
                });

            return Inertia::render('Admin/Visites/Index', [
                'visites' => $visites,
                'userRoles' => $user->roles->pluck('name'),
            ]);
        }

        $visites = Visite::with(['bien.category'])
            ->where('client_id', $user->id)
            ->latest('date_visite')
            ->get();

        return Inertia::render('/Admin/Visites/Index', [
            'visites' => $visites,
            'userRoles' => $user->roles->pluck('name'),
        ]);
    }

    /**
     * ✅ Confirmation de visite - APPELÉE DIRECTEMENT via /visites/action-confirmer
     */
    public function confirmer(Request $request)
    {
        $user = Auth::user();

        // ✅ Récupérer l'ID depuis le body de la requête
        $visiteId = $request->input('visite_id');

        Log::info('🎯 APPEL DIRECT - confirmer()', [
            'visite_id' => $visiteId,
            'user_id' => $user->id,
            'date_visite' => $request->input('date_visite'),
            'notes' => $request->input('notes')
        ]);

        if (!$user->hasRole('admin')) {
            Log::error('❌ Accès refusé');
            abort(403, 'Action non autorisée');
        }

        $request->validate([
            'visite_id' => 'required|exists:visites,id',
            'date_visite' => 'required|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $visite = Visite::with([
            'bien.category',
            'bien.mandat',
            'appartement',
            'client'
        ])->findOrFail($visiteId);

        if ($visite->statut !== 'en_attente') {
            return back()->with('error', 'Cette visite ne peut plus être confirmée.');
        }

        if ($visite->appartement_id && $visite->appartement->statut !== 'disponible') {
            return back()->with('error', 'L\'appartement n\'est plus disponible.');
        }

        DB::beginTransaction();

        try {
            $visite->update([
                'statut' => 'planifiee',
                'date_visite' => $request->date_visite,
                'notes_admin' => $request->notes,
                'confirmee_at' => now(),
                'confirmee_par' => $user->id,
            ]);

            $visite->load([
                'bien.category',
                'bien.mandat',
                'appartement',
                'client'
            ]);

            Log::info('✅ AVANT appel service');
            $messageEnvoye = $this->confirmationService->envoyerConfirmation($visite);
            Log::info('✅ APRÈS appel service', ['resultat' => $messageEnvoye]);

            DB::commit();

            if ($messageEnvoye) {
                Log::info('🎉 SUCCÈS COMPLET');
                return redirect()->route('visites.index')->with('success', 'Visite confirmée et message envoyé au client.');
            } else {
                Log::warning('⚠️ Message non envoyé');
                return redirect()->route('visites.index')->with('warning', 'Visite confirmée mais le message n\'a pas pu être envoyé.');
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ ERREUR', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    /**
     * ✅ NOUVEAU : Gestionnaire d'actions directes
     */
    private function handleAction(Request $request)
    {
        $action = $request->input('action');

        switch ($action) {
            case 'confirmer':
                return $this->confirmer($request, $request->input('visite_id'));

            case 'rejeter':
                return $this->rejeter($request, $request->input('visite_id'));

            case 'marquer-effectuee':
                return $this->marquerEffectuee($request, $request->input('visite_id'));

            default:
                return back()->with('error', 'Action non reconnue');
        }
    }



    public function create(Request $request)
    {
        $bienId = $request->input('bien_id');
        if (!$bienId) {
            return redirect()->route('biens.index')
                ->with('error', 'Aucun bien spécifié pour la visite.');
        }

        $bien = Bien::with(['category', 'proprietaire', 'mandat', 'appartements'])
            ->findOrFail($bienId);

        Log::info('🏠 Préparation visite', [
            'bien_id' => $bien->id,
            'categorie_id' => $bien->categorie_id,
            'is_appartement' => $bien->categorie_id === 4
        ]);

        // Pour les immeubles, vérifier qu'il y a au moins un appartement disponible
        if ($bien->categorie_id === 4) {
            $appartementDisponible = $bien->appartements()
                ->where('statut', 'disponible')
                ->exists();

            if (!$appartementDisponible) {
                return redirect()->back()
                    ->with('error', 'Aucun appartement disponible dans cet immeuble.');
            }
        }

        // Vérifier visite en cours
        $visiteExistante = Visite::where('client_id', Auth::id())
            ->where('bien_id', $bienId)
            ->whereIn('statut', ['en_attente', 'planifiee'])
            ->exists();

        if ($visiteExistante) {
            return redirect()->route('visites.index')
                ->with('error', 'Vous avez déjà une demande de visite en cours pour ce bien.');
        }

        return Inertia::render('Visites/Create', [
            'bien' => $bien,
            'appartements_disponibles' => $bien->categorie_id === 4
                ? $bien->getAppartementsDisponibles()
                : [],
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }

    /**
     * Enregistrer une demande de visite
     */
    public function store(Request $request)
    {
        $request->validate([
            'bien_id' => 'required|exists:biens,id',
            'appartement_id' => 'nullable|exists:appartements,id',
            'date_visite' => 'required|date|after:today',
            'message' => 'nullable|string|max:500',
        ]);

        $bien = Bien::with(['appartements'])->findOrFail($request->bien_id);

        // Pour les immeubles, vérifier l'appartement
        if ($bien->categorie_id === 4) {
            if (!$request->appartement_id) {
                return back()->withErrors([
                    'appartement' => 'Vous devez sélectionner un appartement à visiter.'
                ]);
            }

            $appartement = Appartement::where('id', $request->appartement_id)
                ->where('bien_id', $bien->id)
                ->where('statut', 'disponible')
                ->first();

            if (!$appartement) {
                return back()->withErrors([
                    'appartement' => 'Cet appartement n\'est pas disponible.'
                ]);
            }
        }

        // Vérification visite déjà existante
        $visiteExistante = Visite::where('client_id', Auth::id())
            ->where('bien_id', $request->bien_id)
            ->whereIn('statut', ['en_attente', 'planifiee'])
            ->exists();

        if ($visiteExistante) {
            return back()->with('error', 'Vous avez déjà une demande de visite en cours.');
        }

        try {
            DB::transaction(function () use ($request) {
                Visite::create([
                    'statut' => 'en_attente',
                    'bien_id' => $request->bien_id,
                    'appartement_id' => $request->appartement_id ?? null,
                    'client_id' => Auth::id(),
                    'date_visite' => Carbon::parse($request->date_visite),
                    'message' => $request->message,
                ]);
            });

            return redirect()->route('home')
                ->with('success', 'Votre demande de visite a été enregistrée.');

        } catch (\Throwable $e) {
            Log::error('Erreur création visite', ['error' => $e->getMessage()]);
            return back()->with('error', 'Erreur lors de l\'enregistrement.');
        }
    }

    /**
     * Afficher une visite
     */
    public function show($id)
    {
        $visite = Visite::with([
            'bien.category',
            'bien.appartements',
            'appartement',
            'client'
        ])->findOrFail($id);

        if ($visite->client_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        return Inertia::render('Visites/Show', [
            'visite' => $visite,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }

    /**
     * Annuler une visite (Client)
     */
    public function annuler($id)
    {
        $visite = Visite::findOrFail($id);

        if ($visite->client_id !== Auth::id()) {
            abort(403, 'Vous ne pouvez annuler que vos propres visites.');
        }

        if (!in_array($visite->statut, ['en_attente', 'planifiee'])) {
            return back()->with('error', 'Cette visite ne peut plus être annulée.');
        }

        $visite->update([
            'statut'            => 'annulee',
            'motif_annulation'  => 'Annulée par le client',
        ]);

        return redirect()->route('visites.index')->with('success', 'Visite annulée.');
    }

    /**
     * ADMIN - Rejeter une visite
     */
    public function rejeter(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Action non autorisée');
        }

        $request->validate([
            'motif_rejet' => 'required|string|max:500',
        ]);

        $visite = Visite::findOrFail($id);

        if ($visite->statut !== 'en_attente') {
            return back()->with('error', 'Cette visite ne peut plus être rejetée.');
        }

        $visite->update([
            'statut'      => 'rejetee',
            'motif_rejet' => $request->motif_rejet,
            'rejetee_at'  => now(),
            'rejetee_par' => Auth::id(),
        ]);

        return back()->with('success', 'Visite rejetée.');
    }

    /**
     * ADMIN - Marquer comme effectuée
     */
    public function marquerEffectuee(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user->hasRole('admin')) {
            abort(403, 'Action non autorisée');
        }

        $request->validate([
            'commentaire_visite' => 'nullable|string|max:1000',
        ]);

        $visite = Visite::findOrFail($id);

        if ($visite->statut !== 'planifiee') {
            return back()->with('error', 'Seules les visites confirmées peuvent être marquées comme effectuées.');
        }

        // ✅ NOUVEAU : Vérifier que la date de visite + 2h est dépassée
        $dateVisitePlus2h = Carbon::parse($visite->date_visite)->addHours(2);
        $maintenant = now();

        Log::info('🕐 Vérification temporelle pour marquer effectuée', [
            'visite_id' => $visite->id,
            'date_visite' => $visite->date_visite,
            'date_visite_plus_2h' => $dateVisitePlus2h,
            'maintenant' => $maintenant,
            'peut_marquer_effectuee' => $maintenant->greaterThan($dateVisitePlus2h)
        ]);

        if ($maintenant->lessThan($dateVisitePlus2h)) {
            $heuresRestantes = $maintenant->diffInHours($dateVisitePlus2h, false);
            $minutesRestantes = $maintenant->diffInMinutes($dateVisitePlus2h, false) % 60;

            $tempsRestant = '';
            if ($heuresRestantes > 0) {
                $tempsRestant = abs($heuresRestantes) . ' heure(s)';
                if ($minutesRestantes > 0) {
                    $tempsRestant .= ' et ' . abs($minutesRestantes) . ' minute(s)';
                }
            } else {
                $tempsRestant = abs($minutesRestantes) . ' minute(s)';
            }

            Log::warning('⏰ Tentative prématurée de marquer effectuée', [
                'visite_id' => $visite->id,
                'temps_restant' => $tempsRestant
            ]);

            return back()->with('error', "Cette visite ne peut pas encore être marquée comme effectuée. Veuillez attendre au moins 2 heures après l'heure prévue de la visite. Temps restant : {$tempsRestant}.");
        }

        // ✅ Si le délai est respecté, marquer comme effectuée
        $visite->update([
            'statut'            => 'effectuee',
            'commentaire_visite'=> $request->commentaire_visite,
            'effectuee_at'      => now(),
            'effectuee_par'     => Auth::id(),
        ]);

        Log::info('✅ Visite marquée comme effectuée', [
            'visite_id' => $visite->id,
            'effectuee_par' => Auth::id()
        ]);

        return back()->with('success', 'Visite marquée comme effectuée.');
    }
}
