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
    public function create(Request $request,$bien_id = null)
    {
        if (!$bien_id) {
            $bien_id = $request->input('bien_id');
        }
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
                    'reservation_id' => $reservation->id,
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



// Ajouter ces méthodes dans votre ReservationController existant

    /**
     * Afficher le formulaire d'édition d'une réservation
     */
    public function edit($id)
    {
        $reservation = Reservation::with(['bien', 'client', 'clientDocuments'])->findOrFail($id);

        // Vérifier les permissions
        if (Auth::id() !== $reservation->client_id) {
            abort(403, 'Accès non autorisé');
        }

        // Seules les réservations en attente peuvent être modifiées
        if ($reservation->statut !== 'en_attente') {
            return redirect()->route('reservations.show', $id)
                ->with('error', 'Cette réservation ne peut plus être modifiée.');
        }

        return Inertia::render('Reservation/Edit', [
            'reservation' => $reservation,
            'bien' => $reservation->bien
        ]);
    }

    /**
     * Mettre à jour une réservation
     */
    public function update(Request $request, $id)
    {
        $reservation = Reservation::with(['bien', 'clientDocuments'])->findOrFail($id);

        // Vérifier les permissions
        if (Auth::id() !== $reservation->client_id) {
            abort(403, 'Accès non autorisé');
        }

        // Seules les réservations en attente peuvent être modifiées
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

            // Gérer la suppression de document si demandée
            if ($request->supprimer_document) {
                foreach ($reservation->clientDocuments as $document) {
                    // Supprimer le fichier physique
                    if (\Storage::disk('public')->exists($document->fichier_path)) {
                        \Storage::disk('public')->delete($document->fichier_path);
                    }
                    // Supprimer l'enregistrement
                    $document->delete();
                }
            }

            // Uploader un nouveau document si fourni
            if ($request->hasFile('fichier')) {
                // Supprimer l'ancien document d'abord
                foreach ($reservation->clientDocuments as $document) {
                    if (\Storage::disk('public')->exists($document->fichier_path)) {
                        \Storage::disk('public')->delete($document->fichier_path);
                    }
                    $document->delete();
                }

                // Créer le nouveau document
                $file = $request->file('fichier');
                $filename = time() . '_' . Auth::id() . '_' . $request->type_document . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('documents/clients', $filename, 'public');

                ClientDocument::create([
                    'client_id' => Auth::id(),
                    'reservation_id' => $reservation->id,
                    'type_document' => $request->type_document,
                    'fichier_path' => $path,
                    'statut' => 'en_attente'
                ]);
            }

            // Mettre à jour la date de modification
            $reservation->update([
                'updated_at' => now()
            ]);

            DB::commit();

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', 'Réservation mise à jour avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour: ' . $e->getMessage()]);
        }
    }

    /**
     * Annuler une réservation (Client)
     */
    public function annuler($id)
    {
        $reservation = Reservation::with(['bien', 'clientDocuments'])->findOrFail($id);

        // Vérifier les permissions
        if (Auth::id() !== $reservation->client_id) {
            abort(403, 'Accès non autorisé');
        }

        // Seules les réservations en attente ou confirmées peuvent être annulées par le client
        if (!in_array($reservation->statut, ['en_attente', 'confirmee'])) {
            return redirect()->route('reservations.show', $id)
                ->with('error', 'Cette réservation ne peut plus être annulée.');
        }

        try {
            DB::beginTransaction();

            // Annuler la réservation
            $reservation->update([
                'statut' => 'annulee',
                'motif_rejet' => 'Annulée par le client',
                'cancelled_at' => now(),
                'cancelled_by' => Auth::id()
            ]);

            // Supprimer les documents associés
            foreach ($reservation->clientDocuments as $document) {
                if (\Storage::disk('public')->exists($document->fichier_path)) {
                    \Storage::disk('public')->delete($document->fichier_path);
                }
                $document->delete();
            }

            // Remettre le bien en disponible
            $reservation->bien->update(['status' => 'disponible']);

            DB::commit();

            return redirect()->route('reservations.index')
                ->with('success', 'Réservation annulée avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->with('error', 'Erreur lors de l\'annulation: ' . $e->getMessage());
        }
    }

    /**
     * Afficher une réservation
     */
    /**
     * Afficher une réservation
     */
    public function show($id)
    {
        $reservation = Reservation::with([
            'client',
            'bien',
            'clientDocuments',
            'paiement'
        ])->findOrFail($id);

        // Vérifier les permissions
        if (Auth::id() !== $reservation->client_id && !Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        // Récupérer le paiement associé s'il existe
        $paiement = null;
        if ($reservation->paiement_id) {
            $paiement = \App\Models\Paiement::find($reservation->paiement_id);
        }

        return Inertia::render('Reservation/Show', [
            'reservation' => $reservation->toArray(), // Passer toutes les données de la réservation
            'paiement' => $paiement,
            'userRoles' => Auth::user()->roles->pluck('name')
        ]);
    }    /**
     * Valider une réservation (Admin)
     */
    public function valider($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $reservation = Reservation::with(['clientDocuments'])->findOrFail($id);

        if ($reservation->statut === 'en_attente') {
            DB::beginTransaction();
            try {
                // Valider la réservation
                $reservation->update([
                    'statut' => 'confirmée',
                ]);

                // Valider automatiquement tous les documents de cette réservation
                $reservation->clientDocuments()->update([
                    'statut' => 'valide',
                ]);

                DB::commit();
                return back()->with('success', 'Réservation validée avec succès.');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Erreur lors de la validation.');
            }
        }

        return back()->with('error', 'Impossible de valider cette réservation.');
    }

    /**
     * Rejeter une réservation (Admin)
     */
    public function rejeter(Request $request, $id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $request->validate([
            'motif_rejet' => 'string|max:500'
        ]);

        $reservation = Reservation::with(['bien', 'clientDocuments'])->findOrFail($id);

        if ($reservation->statut === 'en_attente') {
            DB::beginTransaction();
            try {
                // Rejeter la réservation
                $reservation->update([
                    'statut' => 'annulée',
                ]);

                // Rejeter tous les documents de cette réservation
                $reservation->clientDocuments()->update([
                    'statut' => 'refusée',
                ]);

                // Remettre le bien en disponible
                $reservation->bien->update(['status' => 'disponible']);

                DB::commit();
                return back()->with('success', 'Réservation rejetée avec succès.');
            } catch (\Exception $e) {
                DB::rollback();
                return back()->with('error', 'Erreur lors du rejet.');
            }
        }

        return back()->with('error', 'Impossible de rejeter cette réservation.');
    }

    /**
     * Lister les réservations pour le client
     */
    public function index()
    {
        $reservations = Reservation::with(['bien', 'client', 'clientDocuments'])
            ->where('client_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                // Vérifier si les documents sont validés
                $documentsValides = $reservation->clientDocuments
                    ->where('statut', 'valide')
                    ->isNotEmpty();

                $reservation->documents_valides = $documentsValides;
                return $reservation;
            });

        return Inertia::render('Reservation/Index', [
            'reservations' => $reservations,
            'userRoles' => auth()->user()->roles->pluck('name')
        ]);
    }

    /**
     * Lister toutes les réservations pour l'admin
     */
    public function adminIndex()
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Accès non autorisé');
        }

        $reservations = Reservation::with(['bien', 'client', 'clientDocuments'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Admin/ReservationIndex', [
            'reservations' => $reservations,
            'userRoles' => Auth::user()->roles->pluck('name')
        ]);
    }

    /**
     * Initier le paiement d'une réservation validée
     */
    public function initierPaiement(Reservation $reservation)
    {
        // Debug : Ajouter des logs pour tracer l'exécution
        \Log::info('=== DEBUG initierPaiement ===');
        \Log::info('Reservation ID: ' . $reservation->id);
        \Log::info('Client ID: ' . auth()->id());
        \Log::info('Reservation client_id: ' . $reservation->client_id);
        \Log::info('Reservation status: ' . $reservation->statut);

        // Vérification 1: Autorisation
        if ($reservation->client_id !== auth()->id()) {
            \Log::error('Accès refusé - IDs ne correspondent pas');
            abort(403, 'Accès non autorisé');
        }
        \Log::info('✅ Autorisation OK');

        // Vérification 2: Statut de la réservation
        if ($reservation->statut !== 'confirmée') {
            \Log::error('Statut incorrect: ' . $reservation->statut);
            return redirect()->back()->with('error', 'Cette réservation ne peut plus être payée. Statut: ' . $reservation->statut);
        }
        \Log::info('✅ Statut OK');

        // Vérification 3: Documents validés
        $documentsValides = $reservation->clientDocuments
            ->where('statut', 'valide')
            ->isNotEmpty();

        \Log::info('Documents validés: ' . ($documentsValides ? 'OUI' : 'NON'));
        \Log::info('Nombre total de documents: ' . $reservation->clientDocuments->count());

        foreach ($reservation->clientDocuments as $doc) {
            \Log::info('Document ID: ' . $doc->id . ', Statut: ' . $doc->statut);
        }

        if (!$documentsValides) {
            \Log::error('Documents non validés');
            return redirect()->back()->with('error', 'Vos documents doivent être validés avant de pouvoir effectuer le paiement.');
        }
        \Log::info('✅ Documents OK');

        // Vérification 4: Création/récupération du paiement
        try {
            $paiement = \App\Models\Paiement::firstOrCreate([
                'reservation_id' => $reservation->id,
                'type' => 'reservation',
            ], [
                'montant_total' => $reservation->montant,
                'montant_paye' => 0,
                'montant_restant' => $reservation->montant,
                'commission_agence' => $reservation->montant * 0.05,
                'mode_paiement' => 'mobile_money',
                'statut' => 'en_attente',
                'date_transaction' => now(),
            ]);

            \Log::info('✅ Paiement créé/récupéré - ID: ' . $paiement->id);
        } catch (\Exception $e) {
            \Log::error('Erreur création paiement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la création du paiement: ' . $e->getMessage());
        }

        // Vérification 5: Redirection finale
        $redirectUrl = route('paiement.initier', [
            'type' => 'reservation',
            'id' => $reservation->id,
            'paiement_id' => $paiement->id
        ]);

        \Log::info('✅ URL de redirection: ' . $redirectUrl);

        return redirect($redirectUrl);
    }
}
