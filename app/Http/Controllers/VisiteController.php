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

    /**
     * Afficher le formulaire de demande de visite
     */
    public function create(Request $request)
    {
        $bienId = $request->input('bien_id');
        if (!$bienId) {
            return redirect()->route('biens.index')
                ->with('error', 'Aucun bien spécifié pour la visite.');
        }

        $bien = Bien::with(['category', 'proprietaire', 'mandat'])->findOrFail($bienId);


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
            'bien'      => $bien,
            'userRoles' => Auth::user()->roles->pluck('name'),
        ]);
    }

    /**
     * Enregistrer une demande de visite
     */
    public function store(Request $request)
    {
        $request->validate([
            'bien_id'     => 'required|exists:biens,id',
            'date_visite' => 'required|date|after:today',
            'message'     => 'nullable|string|max:500',
        ]);

        // Vérification réservation confirmée
        $reservationConfirmee = Auth::user()->reservations()
            ->where('bien_id', $request->bien_id)
            ->where('statut', 'confirmée')
            ->exists();

        if (!$reservationConfirmee) {
            return back()->with('error', 'Vous devez avoir une réservation confirmée pour ce bien.');
        }

        // Vérification visite déjà existante
        $visiteExistante = Visite::where('client_id', Auth::id())
            ->where('bien_id', $request->bien_id)
            ->whereIn('statut', ['en_attente', 'confirmee'])
            ->exists();

        if ($visiteExistante) {
            return back()->with('error', 'Vous avez déjà une demande de visite en cours pour ce bien.');
        }

        try {
            DB::transaction(function () use ($request) {
                Visite::create([
                    'statut'      => 'en_attente',
                    'bien_id'     => $request->bien_id,
                    'client_id'   => Auth::id(),
                    'date_visite' => Carbon::parse($request->date_visite),
                    'message'     => $request->message,
                ]);
            });

            return redirect()->route('home')
                ->with('success', 'Votre demande de visite a été enregistrée.');

        } catch (\Throwable $e) {
            return back()->with('error', 'Erreur lors de l\'enregistrement : ' . $e->getMessage());
        }
    }

    /**
     * Afficher une visite
     */
    public function show($id)
    {
        $visite = Visite::with(['bien.category','client'])->findOrFail($id);

        if ($visite->client_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        return Inertia::render('Visites/Show', [
            'visite'    => $visite,
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
     * ADMIN - Confirmer une visite
     */
    public function confirmer(Request $request, $id)
    {
        $this->authorize('admin');

        $request->validate([
            'date_visite' => 'required|date|after:today',
            'notes'       => 'nullable|string|max:500',
        ]);

        $visite = Visite::findOrFail($id);

        if ($visite->statut !== 'en_attente') {
            return back()->with('error', 'Cette visite ne peut plus être confirmée.');
        }

        $visite->update([
            'statut'       => 'confirmee',
            'date_visite'  => $request->date_visite,
            'notes_admin'  => $request->notes,
            'confirmee_at' => now(),
            'confirmee_par'=> Auth::id(),
        ]);

        return back()->with('success', 'Visite confirmée.');
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
