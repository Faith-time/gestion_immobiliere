<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Bien;
use App\Models\ClientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReservationController extends Controller
{
    /**
     * Afficher le formulaire de réservation
     */
    public function create($bien_id)
    {
        $bien = Bien::with('category')->findOrFail($bien_id);

        if ($bien->status !== 'disponible') {
            return redirect()->back()->with('error', 'Ce bien n\'est plus disponible.');
        }

        return Inertia::render('Reservation/Create', [
            'bien' => $bien
        ]);
    }

    /**
     * Créer une réservation avec document
     */
    public function store(Request $request)
    {
        $request->validate([
            'bien_id' => 'required|exists:biens,id',
            'type_document' => 'required|string',
            'fichier' => 'required|file|max:5120|mimes:pdf,jpg,jpeg,png'
        ]);

        try {
            DB::beginTransaction();

            // Vérifier la disponibilité du bien
            $bien = Bien::findOrFail($request->bien_id);
            if ($bien->status !== 'disponible') {
                return back()->withErrors(['bien_id' => 'Ce bien n\'est plus disponible.']);
            }

            // Créer la réservation
            $reservation = Reservation::create([
                'client_id' => Auth::id(),
                'bien_id' => $request->bien_id,
                'montant' => 25000,
                'statut' => 'en_attente',
                'paiement_id' => null,
                'date_reservation' => now()
            ]);

            // Uploader et créer le document
            if ($request->hasFile('fichier')) {
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

            // Mettre le bien en réservé
            $bien->update(['status' => 'reserve']);

            DB::commit();

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', 'Réservation créée avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    /**
     * Afficher une réservation
     */
    public function show($id)
    {
        $reservation = Reservation::with(['bien', 'client'])->findOrFail($id);

        // Récupérer le paiement associé s'il existe
        $paiement = null;
        if ($reservation->paiement_id) {
            $paiement = \App\Models\Paiement::find($reservation->paiement_id);
        }

        return Inertia::render('Reservation/Show', [
            'reservation' => $reservation,
            'paiement' => $paiement
        ]);
    }

    /**
     * Valider une réservation (Admin)
     */
    public function valider($id)
    {
        $reservation = Reservation::findOrFail($id);

        if ($reservation->statut === 'en_attente') {
            $reservation->update(['statut' => 'confirmée']);
            return back()->with('success', 'Réservation validée.');
        }

        return back()->with('error', 'Impossible de valider cette réservation.');
    }

    /**
     * Lister les réservations
     */
    public function index()
    {
        $reservations = Reservation::with(['bien', 'client'])
            ->where('client_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                // Vérifier si les documents sont validés
                $documentsValides = \App\Models\ClientDocument::where('client_id', auth()->id())
                    ->where('reservation_id', $reservation->id)
                    ->where('statut', 'valide')
                    ->exists();

                $reservation->documents_valides = $documentsValides;
                return $reservation;
            });

        return Inertia::render('Reservation/Index', [
            'reservations' => $reservations
        ]);
    }
    /**
     * Initier le paiement d'une réservation validée
     */
    public function initierPaiement(Reservation $reservation)
    {
        if ($reservation->client_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        // Accepter les réservations confirmées ET en attente
        if (!in_array($reservation->statut, ['confirmée'])) {
            return redirect()->back()->with('error', 'Cette réservation ne peut plus être payée.');
        }
        // Vérifier que les documents ont été validés par l'admin
        $documentsValides = \App\Models\ClientDocument::where('client_id', auth()->id())
            ->where('reservation_id', $reservation->id)
            ->where('statut', 'valide')
            ->exists();

        if (!$documentsValides) {
            return redirect()->back()->with('error', 'Vos documents doivent être validés avant de pouvoir effectuer le paiement.');
        }

        // Créer ou récupérer le paiement associé
        $paiement = \App\Models\Paiement::firstOrCreate([
            'reservation_id' => $reservation->id,
            'type' => 'reservation',
        ], [
            'montant_total' => $reservation->montant,
            'montant_paye' => 0,
            'montant_restant' => $reservation->montant,
            'commission_agence' => $reservation->montant * 0.05,
            'mode_paiement' => 'mobile_money', // par défaut
            'statut' => 'en_attente',
            'date_transaction' => now(),
        ]);

        return redirect()->route('paiement.initier', [
            'type' => 'reservation',
            'id' => $reservation->id,
            'paiement_id' => $paiement->id
        ]);
    }}
