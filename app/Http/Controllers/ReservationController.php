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
    public function create(Request $request, $bien_id = null)
    {
        if (!$bien_id) {
            $bien_id = $request->input('bien_id');
        }

        // ✅ CORRECTION : Charger aussi la relation 'mandat'
        $bien = Bien::with(['category', 'mandat'])->findOrFail($bien_id);

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

            $bien = Bien::with('mandat')->findOrFail($request->bien_id);

            // ✅ Vérification 1: Statut du bien
            if (!in_array($bien->status, ['disponible'])) {
                DB::rollback();
                return redirect()->back()
                    ->with('error', '❌ Ce bien ne peut pas être réservé car son statut est : ' . $bien->status)
                    ->withInput();
            }

            // ✅ Vérification 2: Réservation existante pour CE client
            $maReservationExistante = Reservation::where('bien_id', $request->bien_id)
                ->where('client_id', Auth::id())
                ->whereIn('statut', ['en_attente', 'confirmée'])
                ->first();

            if ($maReservationExistante) {
                DB::rollback();
                return redirect()->route('reservations.show', $maReservationExistante->id)
                    ->with('warning', '⚠️ Vous avez déjà une réservation active pour ce bien. Consultez-la ci-dessous.');
            }

            // ✅ Vérification 3: Réservation existante par un autre client
            $reservationAutreClient = Reservation::where('bien_id', $request->bien_id)
                ->where('client_id', '!=', Auth::id())
                ->whereIn('statut', ['en_attente', 'confirmée'])
                ->exists();

            if ($reservationAutreClient) {
                DB::rollback();
                return redirect()->back()
                    ->with('error', '❌ Ce bien est déjà réservé par un autre client.')
                    ->withInput();
            }

            // Créer la réservation
            $reservation = Reservation::create([
                'client_id' => Auth::id(),
                'bien_id' => $request->bien_id,
                'montant' => $this->calculateReservationAmount($bien),
                'statut' => 'en_attente',
                'paiement_id' => null,
                'date_reservation' => now()
            ]);

            // Upload du document
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

            $bien->update(['status' => 'reserve']);

            DB::commit();

            return redirect()->route('reservations.show', $reservation->id)
                ->with('success', '✅ Réservation créée avec succès.');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Erreur création réservation: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', '❌ Erreur: ' . $e->getMessage())
                ->withInput();
        }
    }    /**
     * Calculer le montant de réservation selon le type de mandat
     */
    private function calculateReservationAmount(Bien $bien)
    {
        // ✅ DEBUG : Ajouter des logs pour diagnostiquer
        \Log::info('calculateReservationAmount - Bien ID: ' . $bien->id);
        \Log::info('calculateReservationAmount - Prix: ' . $bien->price);
        \Log::info('calculateReservationAmount - Mandat existe: ' . ($bien->mandat ? 'OUI' : 'NON'));

        if (!$bien->mandat) {
            \Log::info('calculateReservationAmount - Pas de mandat, retour 25000');
            return 25000; // Montant par défaut si pas de mandat
        }

        \Log::info('calculateReservationAmount - Type mandat: ' . $bien->mandat->type_mandat);

        switch ($bien->mandat->type_mandat) {
            case 'vente':
                // 5% du prix de vente
                $montant = $bien->price * 0.05;
                \Log::info('calculateReservationAmount - Vente, montant calculé: ' . $montant);
                return $montant;

            case 'gestion_locative':
                // 1 mois de loyer (équivalent au prix du bien pour la location)
                $montant = $bien->price;
                \Log::info('calculateReservationAmount - Location, montant calculé: ' . $montant);
                return $montant;

            default:
                \Log::info('calculateReservationAmount - Type inconnu, retour 25000');
                return 25000; // Montant par défaut
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
        $reservations = Reservation::with([
            'bien.mandat' => function($query) {
                $query->where('statut', 'actif');
            },
            'bien.category',
            'client',
            'clientDocuments',
            'paiement'
        ])
            ->where('client_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                // Vérifier si les documents sont validés
                $documentsValides = $reservation->clientDocuments
                    ->where('statut', 'valide')
                    ->isNotEmpty();

                // ✅ Vérifier si déjà payée
                $dejaPaye = \App\Models\Paiement::where('reservation_id', $reservation->id)
                    ->where('statut', 'reussi')
                    ->exists();

                $reservation->documents_valides = $documentsValides;
                $reservation->deja_payee = $dejaPaye;

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
        \Log::info('=== INITIER PAIEMENT RÉSERVATION ===');
        \Log::info('Reservation ID: ' . $reservation->id);
        \Log::info('Client ID: ' . auth()->id());
        \Log::info('Reservation client_id: ' . $reservation->client_id);
        \Log::info('Reservation statut: ' . $reservation->statut);

        // ✅ VÉRIFICATION 1: Autorisation
        if ($reservation->client_id !== auth()->id()) {
            \Log::error('Accès refusé - IDs ne correspondent pas');
            abort(403, 'Accès non autorisé');
        }

        // ✅ VÉRIFICATION 2: Statut de la réservation
        if ($reservation->statut !== 'confirmée') {
            \Log::error('Statut incorrect: ' . $reservation->statut);
            return redirect()->back()->with('error', 'Cette réservation ne peut pas être payée. Statut actuel : ' . $reservation->statut);
        }

        // ✅ VÉRIFICATION 3: Paiement déjà effectué
        $paiementExistant = \App\Models\Paiement::where('reservation_id', $reservation->id)
            ->where('statut', 'reussi')
            ->first();

        if ($paiementExistant) {
            \Log::warning('Tentative de re-paiement', [
                'reservation_id' => $reservation->id,
                'paiement_existant_id' => $paiementExistant->id,
                'transaction_id' => $paiementExistant->transaction_id
            ]);

            return redirect()->route('reservations.show', $reservation->id)
                ->with('error', '⚠️ Cette réservation a déjà été payée le ' .
                    $paiementExistant->date_transaction->format('d/m/Y à H:i') .
                    '. Montant : ' . number_format($paiementExistant->montant_paye, 0, '', ' ') . ' FCFA');
        }

        // ✅ VÉRIFICATION 4: Documents validés
        $documentsValides = $reservation->clientDocuments
            ->where('statut', 'valide')
            ->isNotEmpty();

        if (!$documentsValides) {
            \Log::error('Documents non validés');
            return redirect()->route('reservations.show', $reservation->id)
                ->with('error', 'Vos documents doivent être validés par un administrateur avant de pouvoir effectuer le paiement.');
        }

        // ✅ TOUT EST OK - Créer ou récupérer le paiement EN ATTENTE
        try {
            // Chercher d'abord un paiement en attente existant
            $paiement = \App\Models\Paiement::where('reservation_id', $reservation->id)
                ->where('statut', 'en_attente')
                ->first();

            // Si aucun paiement en attente n'existe, en créer un nouveau
            if (!$paiement) {
                $paiement = \App\Models\Paiement::create([
                    'reservation_id' => $reservation->id,
                    'type' => 'reservation',
                    'montant_total' => $reservation->montant,
                    'montant_paye' => 0,
                    'montant_restant' => $reservation->montant,
                    'commission_agence' => $reservation->montant * 0.05,
                    'mode_paiement' => 'mobile_money',
                    'statut' => 'en_attente',
                    'transaction_id' => 'RES_' . $reservation->id . '_' . time(),
                    'date_transaction' => now(),
                ]);

                \Log::info('✅ Nouveau paiement créé - ID: ' . $paiement->id);
            } else {
                \Log::info('✅ Paiement en attente récupéré - ID: ' . $paiement->id);
            }

            // Redirection vers la page d'initiation du paiement
            return redirect()->route('paiement.initier.show', [
                'type' => 'reservation',
                'id' => $reservation->id,
                'paiement_id' => $paiement->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Erreur création paiement: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la préparation du paiement. Veuillez réessayer.');
        }
    }
}
