<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\Bien;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function create(Request $request, $bien_id = null)
    {
        Log::info('🔍 ReservationController@create', [
            'request_all' => $request->all(),
            'bien_id' => $bien_id,
            'appartement_id' => $request->input('appartement_id')
        ]);

        if (!$bien_id) {
            $bien_id = $request->input('bien_id');
        }

        // ✅ RÉCUPÉRER l'appartement_id depuis la requête
        $appartement_id = $request->input('appartement_id');

        $bien = Bien::with(['category', 'mandat', 'images', 'appartements'])->findOrFail($bien_id);

        // ✅ CORRECTION : Vérification dynamique par nom de catégorie
        $isImmeuble = $bien->category &&
            strtolower($bien->category->name) === 'appartement' &&
            $bien->appartements()->count() > 0;

        Log::info('🏠 Bien chargé pour création réservation', [
            'bien_id' => $bien->id,
            'categorie_id' => $bien->categorie_id,
            'category_name' => $bien->category ? $bien->category->name : null,
            'status' => $bien->status,
            'has_mandat' => $bien->mandat !== null,
            'is_immeuble' => $isImmeuble, // ✅ Changé
            'nb_appartements' => $bien->appartements->count(),
            'appartement_id_requested' => $appartement_id
        ]);

        // ✅ Pour les immeubles (vérification dynamique)
        if ($isImmeuble) {
            // ✅ Si un appartement spécifique est demandé, vérifier qu'il est disponible
            if ($appartement_id) {
                $appartement = $bien->appartements()
                    ->where('id', $appartement_id)
                    ->where('statut', 'disponible')
                    ->first();

                if (!$appartement) {
                    return redirect()->back()->with('error', 'Cet appartement n\'est pas disponible.');
                }
            } else {
                // Sinon, vérifier qu'il y a au moins un appartement disponible
                $appartementDisponible = $bien->appartements()
                    ->where('statut', 'disponible')
                    ->exists();

                if (!$appartementDisponible) {
                    return redirect()->back()->with('error', 'Aucun appartement disponible dans cet immeuble.');
                }
            }
        } else {
            // Pour les autres biens
            if ($bien->status !== 'disponible') {
                return redirect()->back()->with('error', 'Ce bien n\'est plus disponible.');
            }
        }

        // Vérifier le mandat
        if (!$bien->mandat || !in_array($bien->mandat->type_mandat, ['vente', 'gestion_locative'])) {
            return redirect()->back()->with('error', 'Ce bien n\'a pas de mandat valide pour une réservation.');
        }

        if ($isImmeuble) {
            $apparts = $bien->getAppartementsDisponibles();
            Log::info('🚪 Appartements pour le bien', [
                'bien_id' => $bien->id,
                'total_appartements' => $bien->appartements->count(),
                'appartements_disponibles' => $apparts->count(),
                'tous_les_appartements' => $bien->appartements->map(function($a) {
                    return [
                        'id' => $a->id,
                        'numero' => $a->numero,
                        'statut' => $a->statut
                    ];
                })
            ]);
        }

        return Inertia::render('Reservation/Create', [
            'bien' => $bien,
            'appartement_id' => $appartement_id,
            'appartements_disponibles' => $isImmeuble
                ? $bien->getAppartementsDisponibles()
                : []
        ]);
    }


    public function store(Request $request)
    {
        try {
            Log::info('📥 === DÉBUT CRÉATION RÉSERVATION ===', [
                'user_id' => auth()->id(),
                'request_all' => $request->all(),
                'appartement_id_present' => $request->has('appartement_id'),
                'appartement_id_value' => $request->input('appartement_id')
            ]);

            $validated = $request->validate([
                'bien_id' => 'required|exists:biens,id',
                'appartement_id' => 'nullable|exists:appartements,id',
                'type_document' => 'required|string',
                'fichier' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            Log::info('✅ Validation réussie', [
                'validated_data' => $validated
            ]);

            $bien = Bien::with(['mandat', 'appartements', 'category'])->findOrFail($validated['bien_id']);

            // ✅ Vérifier si c'est un immeuble d'appartements de manière dynamique
            $isImmeuble = $bien->category &&
                strtolower($bien->category->name) === 'appartement' &&
                $bien->appartements()->count() > 0;

            Log::info('🏠 Bien récupéré', [
                'bien_id' => $bien->id,
                'categorie_id' => $bien->categorie_id,
                'category_name' => $bien->category ? $bien->category->name : null,
                'is_immeuble' => $isImmeuble, // ✅ Changé de is_appartement à is_immeuble
                'nb_appartements' => $bien->appartements->count(),
                'appartement_id_demande' => $validated['appartement_id'] ?? null
            ]);

            // ✅ Vérifications pour immeubles
            if ($isImmeuble) {
                if (!isset($validated['appartement_id'])) {
                    return redirect()->back()->withErrors([
                        'appartement' => 'Vous devez sélectionner un appartement spécifique.'
                    ]);
                }

                $appartement = Appartement::where('id', $validated['appartement_id'])
                    ->where('bien_id', $bien->id)
                    ->first();

                if (!$appartement) {
                    return redirect()->back()->withErrors([
                        'appartement' => 'Cet appartement n\'appartient pas à cet immeuble.'
                    ]);
                }

                if ($appartement->statut !== 'disponible') {
                    return redirect()->back()->withErrors([
                        'appartement' => 'Cet appartement n\'est plus disponible.'
                    ]);
                }

                Log::info('✅ Appartement validé', [
                    'appartement_id' => $appartement->id,
                    'numero' => $appartement->numero,
                    'statut' => $appartement->statut
                ]);
            } else {
                // Pour les autres biens (non-immeubles)
                if (!in_array($bien->status, ['disponible', 'en_vente'])) {
                    return redirect()->back()->withErrors([
                        'bien' => 'Ce bien n\'est plus disponible.'
                    ]);
                }
            }

            // Vérifier le mandat
            if (!$bien->mandat || !in_array($bien->mandat->type_mandat, ['vente', 'gestion_locative'])) {
                return redirect()->back()->withErrors([
                    'bien' => 'Ce bien n\'a pas de mandat valide.'
                ]);
            }

            // Vérifier réservation existante
            $queryReservation = Reservation::where('bien_id', $validated['bien_id'])
                ->where('client_id', auth()->id())
                ->whereIn('statut', ['en_attente', 'confirmée']);

            if (isset($validated['appartement_id'])) {
                $queryReservation->where('appartement_id', $validated['appartement_id']);
            }

            if ($queryReservation->exists()) {
                return redirect()->back()->withErrors([
                    'reservation' => 'Vous avez déjà une réservation active pour ce bien/appartement.'
                ]);
            }

            // Calculer le montant
            $typeMandat = $bien->mandat->type_mandat;
            if ($typeMandat === 'vente') {
                $montantInitial = $bien->price * 0.10;
                $typeMontant = 'acompte';
                $messageInfo = 'L\'acompte représente 10% du prix de vente.';
            } else {
                $montantInitial = $bien->price;
                $typeMontant = 'depot_garantie';
                $messageInfo = 'Le dépôt de garantie correspond à 1 mois de loyer.';
            }

            $reservation = DB::transaction(function () use ($validated, $request, $bien, $montantInitial, $typeMontant, $isImmeuble) {

                // Créer la réservation
                $reservation = Reservation::create([
                    'bien_id' => $validated['bien_id'],
                    'appartement_id' => $validated['appartement_id'] ?? null,
                    'client_id' => auth()->id(),
                    'date_reservation' => now(),
                    'montant' => $montantInitial,
                    'type_montant' => $typeMontant,
                    'statut' => 'en_attente',
                    'documents_valides' => false,
                ]);

                Log::info('✅ Réservation créée', [
                    'reservation_id' => $reservation->id,
                    'bien_id' => $reservation->bien_id,
                    'appartement_id' => $reservation->appartement_id,
                ]);

                // Stocker le fichier
                $fichierPath = $request->file('fichier')->store('documents/clients', 'public');

                ClientDocument::create([
                    'client_id' => auth()->id(),
                    'reservation_id' => $reservation->id,
                    'type_document' => $validated['type_document'],
                    'fichier_path' => $fichierPath,
                    'statut' => 'en_attente',
                ]);

                Paiement::create([
                    'reservation_id' => $reservation->id,
                    'type' => 'reservation',
                    'type_montant' => $typeMontant,
                    'montant_total' => $montantInitial,
                    'montant_paye' => 0,
                    'montant_restant' => $montantInitial,
                    'commission_agence' => 0,
                    'statut' => 'en_attente',
                    'mode_paiement' => 'orange_money',
                ]);

                // ✅ NE PAS CHANGER LE STATUT ICI
                // Le statut sera mis à jour après le paiement réussi
                Log::info('ℹ️ Statut non modifié - En attente du paiement', [
                    'bien_id' => $bien->id,
                    'appartement_id' => $validated['appartement_id'] ?? null,
                    'is_immeuble' => $isImmeuble
                ]);

                return $reservation;
            });

            Log::info('🎉 === FIN CRÉATION RÉSERVATION AVEC SUCCÈS ===', [
                'reservation_id' => $reservation->id
            ]);

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', "Réservation créée avec succès ! $messageInfo");

        } catch (\Exception $e) {
            Log::error('❌ Erreur création réservation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['general' => 'Une erreur est survenue : ' . $e->getMessage()]);
        }
    }
    public function annuler($id)
    {
        $reservation = Reservation::with(['bien.category', 'appartement'])->findOrFail($id);

        if (Auth::id() !== $reservation->client_id) {
            abort(403, 'Accès non autorisé');
        }

        if (!in_array($reservation->statut, ['en_attente', 'confirmée'])) {
            return redirect()->route('reservations.show', $id)
                ->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        try {
            DB::beginTransaction();

            $reservation->update([
                'statut' => 'annulée',
                'motif_rejet' => 'Annulée par le client',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id()
            ]);

            // ✅ Libérer UNIQUEMENT si le paiement était réussi (donc statut était confirmée)
            if ($reservation->statut_before_update === 'confirmée') {
                if ($reservation->appartement_id) {
                    // Pour un immeuble : libérer UNIQUEMENT l'appartement
                    Appartement::where('id', $reservation->appartement_id)
                        ->update(['statut' => 'disponible']);

                    Log::info('✅ Appartement libéré', [
                        'appartement_id' => $reservation->appartement_id
                    ]);

                    // Mettre à jour le statut global du bien parent
                    $reservation->bien->refresh();
                    $reservation->bien->updateStatutGlobal();
                } else {
                    // Pour un bien classique : libérer le bien
                    $reservation->bien->update(['status' => 'disponible']);

                    Log::info('✅ Bien libéré', [
                        'bien_id' => $reservation->bien_id
                    ]);
                }
            } else {
                Log::info('ℹ️ Réservation annulée avant paiement - Aucun statut à libérer');
            }

            DB::commit();

            return redirect()->route('reservations.index')
                ->with('success', 'Réservation annulée avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('❌ Erreur annulation réservation', [
                'error' => $e->getMessage()
            ]);
            return back()->with('error', 'Erreur lors de l\'annulation.');
        }
    }
    public function initierPaiement(Reservation $reservation)
    {
        if ($reservation->client_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        if ($reservation->paiement &&
            $reservation->paiement->statut === 'reussi' &&
            $reservation->paiement->montant_restant <= 0) {
            return redirect()->route('reservations.show', $reservation->id)
                ->with('error', 'Le paiement a déjà été effectué.');
        }

        try {
            $reservation->load('bien.mandat');

            // ✅ Vérifier que le bien a un mandat valide
            $bien = $reservation->bien;
            if (!$bien->mandat || !in_array($bien->mandat->type_mandat, ['vente', 'gestion_locative'])) {
                return redirect()->back()
                    ->with('error', 'Ce bien n\'a pas de mandat valide.');
            }

            $typeMandat = $bien->mandat->type_mandat;

            if ($typeMandat === 'vente') {
                $montantInitial = $bien->price * 0.10; // 10% d'acompte
                $messageInfo = 'Acompte : 10% du prix de vente. Les 90% restants seront payés lors de l\'achat final.';
            } elseif ($typeMandat === 'gestion_locative') {
                $montantInitial = $bien->price; // 1 mois de dépôt de garantie
                $messageInfo = 'Dépôt de garantie : 1 mois de loyer (caution restituable).';
            } else {
                return redirect()->back()
                    ->with('error', 'Type de mandat non reconnu.');
            }

            $paiement = Paiement::where('reservation_id', $reservation->id)
                ->whereIn('statut', ['en_attente', 'partiellement_paye'])
                ->first();

            if (!$paiement) {
                $paiement = Paiement::create([
                    'reservation_id' => $reservation->id,
                    'type' => 'reservation',
                    'montant_total' => $montantInitial,
                    'montant_paye' => 0,
                    'montant_restant' => $montantInitial,
                    'commission_agence' => 0,
                    'statut' => 'en_attente',
                    'mode_paiement' => 'orange_money',
                    'date_transaction' => null,
                ]);

                Log::info('💳 Paiement créé', [
                    'reservation_id' => $reservation->id,
                    'montant' => $montantInitial,
                    'type_mandat' => $typeMandat
                ]);
            } else {
                if ($paiement->montant_total != $montantInitial) {
                    $paiement->update([
                        'montant_total' => $montantInitial,
                        'montant_restant' => $montantInitial - $paiement->montant_paye
                    ]);

                    Log::info('🔄 Montant du paiement corrigé', [
                        'ancien_montant' => $paiement->montant_total,
                        'nouveau_montant' => $montantInitial
                    ]);
                }
            }

            return redirect()->route('paiement.initier.show', [$reservation->id, $paiement->id])
                ->with('info', $messageInfo);

        } catch (\Exception $e) {
            Log::error('❌ Erreur initialisation paiement réservation', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la préparation du paiement.');
        }
    }

    public function show($id)
    {
        $reservation = Reservation::with([
            'client',
            'bien.category',
            'bien.images',
            'bien.mandat',
            'paiement'
        ])->findOrFail($id);

        if (Auth::id() !== $reservation->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $documents = ClientDocument::where('client_id', $reservation->client_id)
            ->latest()
            ->get();

        $paiement = null;
        if ($reservation->paiement_id) {
            $paiement = \App\Models\Paiement::find($reservation->paiement_id);
        }

        return Inertia::render('Reservation/Show', [
            'reservation' => $reservation,
            'documents' => $documents,
            'paiement' => $paiement,
            'userRoles' => Auth::user()->roles->pluck('name')
        ]);
    }

    public function index()
    {
        $reservations = Reservation::with([
            'bien.mandat' => function($query) {
                $query->where('statut', 'actif');
            },
            'bien.category',
            'bien.images',
            'client',
            'paiement'
        ])
            ->where('client_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                $documentsValides = ClientDocument::where('client_id', $reservation->client_id)
                    ->where('statut', 'valide')
                    ->exists();

                $dejaPaye = \App\Models\Paiement::where('reservation_id', $reservation->id)
                    ->where('statut', 'reussi')
                    ->exists();

                $locationExiste = \App\Models\Location::where('reservation_id', $reservation->id)
                    ->whereIn('statut', ['active', 'finalisée', 'en_retard'])
                    ->exists();

                $venteExiste = \App\Models\Vente::where('reservation_id', $reservation->id)
                    ->whereIn('status', ['en_cours', 'confirmée', 'en_attente_paiement'])
                    ->exists();

                $reservation->documents_valides = $documentsValides;
                $reservation->deja_payee = $dejaPaye;
                $reservation->location_existe = $locationExiste;
                $reservation->vente_existe = $venteExiste;

                return $reservation;
            });

        return Inertia::render('Reservation/Index', [
            'reservations' => $reservations ?? [],
            'userRoles' => Auth::user()->roles->pluck('name')->toArray()
        ]);
    }

    public function adminIndex()
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $reservations = Reservation::with([
            'bien.category',
            'bien.images',
            'bien.mandat',
            'client',
            'paiement'
        ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($reservation) {
                $reservation->client_documents = ClientDocument::where('client_id', $reservation->client_id)
                    ->latest()
                    ->get();

                $reservation->documents_count = $reservation->client_documents->count();
                return $reservation;
            });

        return Inertia::render('Admin/ReservationIndex', [
            'reservations' => $reservations ?? [],
            'userRoles' => Auth::user()->roles->pluck('name')->toArray()
        ]);
    }

    public function edit($id)
    {
        $reservation = Reservation::with(['bien', 'client'])->findOrFail($id);

        if (Auth::id() !== $reservation->client_id) {
            abort(403, 'Accès non autorisé');
        }

        if ($reservation->statut !== 'en_attente') {
            return redirect()->route('reservations.show', $id)
                ->with('error', 'Cette réservation ne peut plus être modifiée.');
        }

        $documents = ClientDocument::where('client_id', Auth::id())->latest()->get();

        return Inertia::render('Reservation/Edit', [
            'reservation' => $reservation,
            'bien' => $reservation->bien,
            'documents' => $documents
        ]);
    }

    public function update(Request $request, $id)
    {
        $reservation = Reservation::with(['bien'])->findOrFail($id);

        if (Auth::id() !== $reservation->client_id) {
            abort(403, 'Accès non autorisé');
        }

        if ($reservation->statut !== 'en_attente') {
            return redirect()->route('reservations.show', $id)
                ->with('error', 'Cette réservation ne peut plus être modifiée.');
        }

        $request->validate([
            'type_document' => 'sometimes|required|string',
            'fichier' => 'sometimes|file|max:5120|mimes:pdf,jpg,jpeg,png',
            'supprimer_document' => 'sometimes|boolean'
        ]);

        try {
            DB::beginTransaction();

            if ($request->supprimer_document) {
                $documents = ClientDocument::where('client_id', Auth::id())->get();
                foreach ($documents as $document) {
                    if (Storage::disk('public')->exists($document->fichier_path)) {
                        Storage::disk('public')->delete($document->fichier_path);
                    }
                    $document->delete();
                }
            }

            if ($request->hasFile('fichier')) {
                $documents = ClientDocument::where('client_id', Auth::id())->get();
                foreach ($documents as $document) {
                    if (Storage::disk('public')->exists($document->fichier_path)) {
                        Storage::disk('public')->delete($document->fichier_path);
                    }
                    $document->delete();
                }

                $file = $request->file('fichier');
                $filename = time() . '_' . Auth::id() . '_' . $request->type_document . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('documents/clients', $filename, 'public');

                ClientDocument::create([
                    'client_id' => Auth::id(),
                    'type_document' => $request->type_document,
                    'fichier_path' => $path,
                    'statut' => 'en_attente'
                ]);
            }

            $reservation->touch();

            DB::commit();

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', 'Réservation mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    public function valider($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $reservation = Reservation::findOrFail($id);

        if ($reservation->statut === 'en_attente') {
            DB::beginTransaction();
            try {
                $reservation->update(['statut' => 'confirmée']);

                ClientDocument::where('client_id', $reservation->client_id)
                    ->where('statut', 'en_attente')
                    ->update(['statut' => 'valide']);

                DB::commit();
                return back()->with('success', 'Réservation validée avec succès.');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Erreur lors de la validation.');
            }
        }

        return back()->with('error', 'Impossible de valider cette réservation.');
    }

    public function rejeter(Request $request, $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $request->validate([
            'motif_rejet' => 'nullable|string|max:500'
        ]);

        $reservation = Reservation::with(['bien', 'appartement'])->findOrFail($id);

        if ($reservation->statut === 'en_attente') {
            DB::beginTransaction();
            try {
                $reservation->update([
                    'statut' => 'annulée',
                    'motif_rejet' => $request->motif_rejet ?? 'Rejetée par l\'administrateur'
                ]);

                // ✅ Libérer l'appartement ou le bien
                if ($reservation->appartement_id) {
                    Appartement::where('id', $reservation->appartement_id)
                        ->update(['statut' => 'disponible']);

                    $reservation->bien->updateStatutGlobal();
                } else {
                    $reservation->bien->update(['status' => 'disponible']);
                }

                DB::commit();
                return back()->with('success', 'Réservation rejetée avec succès.');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Erreur lors du rejet.');
            }
        }

        return back()->with('error', 'Impossible de rejeter cette réservation.');
    }}
