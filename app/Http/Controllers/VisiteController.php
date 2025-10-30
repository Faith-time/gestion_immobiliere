<?php

namespace App\Http\Controllers;

use App\Models\Visite;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class VisiteController extends Controller
{
    /**
     * Lister les visites du client connecté
     */
    public function index()
    {
        $visites = Visite::with(['bien.category'])
            ->where('client_id', Auth::id())
            ->latest('date_visite')
            ->get();

        return Inertia::render('Visites/Index', [
            'visites'   => $visites,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
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
            ->whereIn('statut', ['en_attente', 'confirmee'])
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
            ->whereIn('statut', ['en_attente', 'confirmee'])
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
     * ADMIN - Confirmer une visite
     */
    public function confirmer(Request $request, $id)
    {
        $this->authorize('admin');

        $request->validate([
            'date_visite' => 'required|date|after:today',
            'notes' => 'nullable|string|max:500',
        ]);

        $visite = Visite::with(['appartement'])->findOrFail($id);

        if ($visite->statut !== 'en_attente') {
            return back()->with('error', 'Cette visite ne peut plus être confirmée.');
        }

        // Pour les immeubles, vérifier que l'appartement est toujours disponible
        if ($visite->appartement_id) {
            if ($visite->appartement->statut !== 'disponible') {
                return back()->with('error', 'L\'appartement n\'est plus disponible.');
            }
        }

        $visite->update([
            'statut' => 'confirmee',
            'date_visite' => $request->date_visite,
            'notes_admin' => $request->notes,
            'confirmee_at' => now(),
            'confirmee_par' => Auth::id(),
        ]);

        return back()->with('success', 'Visite confirmée.');
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

        if (!in_array($visite->statut, ['en_attente', 'confirmee'])) {
            return back()->with('error', 'Cette visite ne peut plus être annulée.');
        }

        $visite->update([
            'statut'            => 'annulee',
            'motif_annulation'  => 'Annulée par le client',
        ]);

        return redirect()->route('visites.index')->with('success', 'Visite annulée.');
    }

    /**
     * ADMIN - Lister toutes les visites
     */
    public function adminIndex()
    {
        $this->authorize('admin'); // Policy

        $visites = Visite::with(['bien.category', 'client'])
            ->latest('date_visite')
            ->get();

        return Inertia::render('Admin/Visites/Index', [
            'visites'   => $visites,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }



    /**
     * ADMIN - Rejeter une visite
     */
    public function rejeter(Request $request, $id)
    {
        $this->authorize('admin');

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
        $this->authorize('admin');

        $request->validate([
            'commentaire_visite' => 'nullable|string|max:1000',
        ]);

        $visite = Visite::findOrFail($id);

        if ($visite->statut !== 'confirmee') {
            return back()->with('error', 'Seules les visites confirmées peuvent être marquées comme effectuées.');
        }

        $visite->update([
            'statut'            => 'effectuee',
            'commentaire_visite'=> $request->commentaire_visite,
            'effectuee_at'      => now(),
            'effectuee_par'     => Auth::id(),
        ]);

        return back()->with('success', 'Visite marquée comme effectuée.');
    }
}
