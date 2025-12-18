<?php

namespace App\Http\Controllers;

use App\Models\Appartement;
use App\Models\Bien;
use App\Models\ClientDossier;
use App\Models\Paiement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReservationController extends Controller
{
    private function traiterReservationComplete(Paiement $paiement)
    {
        $reservation = Reservation::with(['bien.category', 'appartement'])->find($paiement->reservation_id);
        if (!$reservation) return;

        DB::transaction(function () use ($reservation) {
            // Mettre à jour le statut de la réservation
            DB::table('reservations')
                ->where('id', $reservation->id)
                ->update([
                    'statut' => 'confirmee',
                    'updated_at' => now()
                ]);

            $bien = $reservation->bien;

            // ✅ Vérifier si c'est un immeuble avec appartements
            $isImmeuble = $bien &&
                $bien->category &&
                strtolower($bien->category->name) === 'appartement' &&
                $bien->appartements()->count() > 0;

            if ($isImmeuble && $reservation->appartement_id) {
                // ✅ Pour un IMMEUBLE : Marquer UNIQUEMENT l'appartement comme réservé
                DB::table('appartements')
                    ->where('id', $reservation->appartement_id)
                    ->update([
                        'statut' => 'reserve',
                        'updated_at' => now()
                    ]);

                Log::info('✅ Appartement marqué comme réservé après paiement', [
                    'appartement_id' => $reservation->appartement_id
                ]);

                // ✅ Mettre à jour le statut GLOBAL du bien
                $bien->fresh()->updateStatutGlobal();
            } else {
                // ✅ Pour un BIEN CLASSIQUE : Marquer le bien comme réservé
                DB::table('biens')
                    ->where('id', $bien->id)
                    ->update([
                        'status' => 'reserve',
                        'updated_at' => now()
                    ]);

                Log::info('✅ Bien marqué comme réservé après paiement', [
                    'bien_id' => $bien->id
                ]);
            }
        });
    }
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

        // ✅ CHARGEMENT COMPLET DES RELATIONS
        $bien = Bien::with([
            'category',
            'mandat',
            'images',
            'appartements',
            'proprietaire'
        ])->find($bien_id);

        // ✅ VÉRIFICATION 1 : Le bien existe-t-il ?
        if (!$bien) {
            Log::error('❌ Bien introuvable', ['bien_id' => $bien_id]);
            return redirect()->route('home')
                ->with('error', '❌ Le bien demandé est introuvable.');
        }

        // ✅ VÉRIFICATION 2 : Le prix est-il défini ?
        if (!$bien->price || $bien->price <= 0) {
            Log::error('❌ Prix du bien invalide', [
                'bien_id' => $bien->id,
                'price' => $bien->price
            ]);
            return redirect()->route('biens.show', $bien->id)
                ->with('error', '❌ Le prix de ce bien n\'est pas défini. Veuillez contacter l\'administrateur.');
        }

        // ✅ VÉRIFICATION 3 : Propriétaire ne peut pas réserver son bien
        if (Auth::id() === $bien->proprietaire_id) {
            Log::warning('⛔ Tentative de réservation par le propriétaire', [
                'user_id' => Auth::id(),
                'bien_id' => $bien->id
            ]);
            return redirect()->back()->with('error',
                'Vous ne pouvez pas réserver votre propre bien. En tant que propriétaire, vous avez déjà accès à toutes les fonctionnalités de gestion.'
            );
        }

        $appartement_id = $request->input('appartement_id');

        $isImmeuble = $bien->category &&
            strtolower($bien->category->name) === 'appartement' &&
            $bien->appartements()->count() > 0;

        // ✅ GESTION DES IMMEUBLES
        if ($isImmeuble) {
            if ($appartement_id) {
                $appartement = $bien->appartements()
                    ->where('id', $appartement_id)
                    ->where('statut', 'disponible')
                    ->first();

                if (!$appartement) {
                    return redirect()->back()
                        ->with('error', 'Cet appartement n\'est pas disponible.');
                }
            } else {
                $appartementDisponible = $bien->appartements()
                    ->where('statut', 'disponible')
                    ->exists();

                if (!$appartementDisponible) {
                    return redirect()->back()
                        ->with('error', 'Aucun appartement disponible dans cet immeuble.');
                }
            }
        } else {
            // Pour un bien standard
            if ($bien->status !== 'disponible') {
                return redirect()->back()
                    ->with('error', 'Ce bien n\'est plus disponible.');
            }
        }

        // ✅ VÉRIFICATION DU MANDAT
        if (!$bien->mandat || !in_array($bien->mandat->type_mandat, ['vente', 'gestion_locative'])) {
            return redirect()->back()
                ->with('error', 'Ce bien n\'a pas de mandat valide pour une réservation.');
        }

        // Charger le dossier existant
        $user = Auth::user();
        $dossierExistant = ClientDossier::where('client_id', $user->id)->first();

        Log::info('✅ Données réservation prêtes', [
            'bien_id' => $bien->id,
            'prix' => $bien->price,
            'isImmeuble' => $isImmeuble,
            'has_dossier' => !!$dossierExistant
        ]);

        return Inertia::render('Reservation/Create', [
            'bien' => $bien,
            'appartement_id' => $appartement_id,
            'appartements_disponibles' => $isImmeuble ? $bien->getAppartementsDisponibles() : [],
            'dossier_existant' => $dossierExistant,
            'user' => $user,
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : []
        ]);
    }
// ReservationController.php

    public function show($id)
    {
        $reservation = Reservation::with([
            'client',
            'bien.category',
            'bien.images',
            'bien.mandat',
            'bien.proprietaire',
            'appartement',
            'paiement'
        ])->find($id);

        if (!$reservation) {
            return redirect()->route('reservations.index')
                ->with('error', '❌ Réservation introuvable.');
        }

        // ✅ AUTO-RÉPARATION : Lier le paiement si manquant
        if (!$reservation->paiement_id) {
            $paiement = Paiement::where('reservation_id', $reservation->id)
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
                ->first();

            if ($paiement) {
                Log::info('🔧 Auto-réparation : Lien paiement manquant détecté', [
                    'reservation_id' => $reservation->id,
                    'paiement_id' => $paiement->id
                ]);

                $reservation->update(['paiement_id' => $paiement->id]);
                $reservation->refresh();
            }
        }

        // ✅ Charger explicitement le paiement
        $paiement = $reservation->paiement_id
            ? Paiement::find($reservation->paiement_id)
            : null;

        Log::info('📊 Paiement chargé', [
            'reservation_id' => $reservation->id,
            'paiement_id' => $paiement?->id,
            'statut' => $paiement?->statut,
            'montant_restant' => $paiement?->montant_restant
        ]);

        // ✅ VÉRIFIER SI VENTE/LOCATION EXISTE
        $venteExiste = \App\Models\Vente::where('reservation_id', $reservation->id)
            ->whereIn('status', ['en_cours', 'confirmée', 'en_attente_paiement'])
            ->exists();

        $locationExiste = \App\Models\Location::where('reservation_id', $reservation->id)
            ->whereIn('statut', ['active', 'en_attente_paiement', 'en_retard'])
            ->exists();

        return Inertia::render('Reservation/Show', [
            'reservation' => $reservation,
            'paiement' => $paiement,
            'vente_existe' => $venteExiste,
            'location_existe' => $locationExiste,
            'userRoles' => Auth::user()->roles->pluck('name')
        ]);
    }
    /**
     * 🔧 Diagnostic et réparation d'une réservation
     */
    public function reparer($id)
    {
        if (!Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $reservation = Reservation::findOrFail($id);

        // Chercher un paiement existant pour cette réservation
        $paiement = Paiement::where('reservation_id', $reservation->id)
            ->where('statut', 'reussi')
            ->first();

        if ($paiement && !$reservation->paiement_id) {
            // Réparer le lien manquant
            $reservation->update([
                'paiement_id' => $paiement->id
            ]);

            return redirect()->route('reservations.show', $id)
                ->with('success', '✅ Réservation réparée : paiement #' . $paiement->id . ' lié avec succès.');
        }

        return back()->with('error', '❌ Aucun paiement réussi trouvé pour cette réservation.');
    }
    /**
     * ✅ MÉTHODE INDEX CORRIGÉE
     */
    public function index()
    {
        $reservations = Reservation::with([
            'bien.mandat',
            'bien.category',
            'bien.images',
            'bien.proprietaire',
            'appartement',
            'client',
            'paiement'
        ])
            ->where('client_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($reservation) {
                if (!$reservation->bien) {
                    Log::warning('⚠️ Réservation sans bien', [
                        'reservation_id' => $reservation->id
                    ]);
                    return null;
                }

                // ✅ VÉRIFIER DOSSIER
                $dossierValide = ClientDossier::where('client_id', $reservation->client_id)
                    ->whereNotNull('carte_identite_path')
                    ->exists();

                // ✅ VÉRIFIER PAIEMENT AVEC AUTO-RÉPARATION
                $dejaPaye = false;

                if ($reservation->paiement_id) {
                    $paiement = Paiement::find($reservation->paiement_id);
                    $dejaPaye = $paiement &&
                        $paiement->statut === 'reussi' &&
                        $paiement->montant_restant <= 0;
                } else {
                    // ✅ AUTO-RÉPARATION : Chercher un paiement orphelin
                    $paiement = Paiement::where('reservation_id', $reservation->id)
                        ->where('statut', 'reussi')
                        ->where('montant_restant', '<=', 0)
                        ->first();

                    if ($paiement) {
                        Log::info('🔧 Auto-réparation dans index', [
                            'reservation_id' => $reservation->id,
                            'paiement_id' => $paiement->id
                        ]);

                        $reservation->update(['paiement_id' => $paiement->id]);
                        $dejaPaye = true;
                    }
                }

                // ✅ VÉRIFIER LOCATION EXISTANTE (TOUS STATUTS)
                $locationExiste = \App\Models\Location::where('reservation_id', $reservation->id)
                    ->whereIn('statut', ['active', 'en_attente_paiement', 'en_retard', 'terminee'])
                    ->exists();

                // ✅ VÉRIFIER VENTE EXISTANTE
                $venteExiste = \App\Models\Vente::where('reservation_id', $reservation->id)
                    ->whereIn('status', ['en_cours', 'confirmée', 'en_attente_paiement'])
                    ->exists();

                $reservation->documents_valides = $dossierValide;
                $reservation->deja_payee = $dejaPaye;
                $reservation->location_existe = $locationExiste;
                $reservation->vente_existe = $venteExiste;

                return $reservation;
            })
            ->filter()
            ->values();

        Log::info('✅ Liste réservations chargée', [
            'user_id' => Auth::id(),
            'count' => $reservations->count()
        ]);

        return Inertia::render('Reservation/Index', [
            'reservations' => $reservations,
            'userRoles' => Auth::user()->roles->pluck('name')->toArray()
        ]);
    }
    /**
     * ✅ MÉTHODE INITIER PAIEMENT CORRIGÉE
     */
    public function initierPaiement(Reservation $reservation)
    {
        if ($reservation->client_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        // ✅ CONTRÔLE 1: Vérifier si le paiement existe déjà et est réussi
        $paiementExistant = Paiement::where('reservation_id', $reservation->id)
            ->where('statut', 'reussi')
            ->where('montant_restant', '<=', 0)
            ->first();

        if ($paiementExistant) {
            Log::warning('⚠️ Tentative de paiement doublon pour réservation', [
                'reservation_id' => $reservation->id,
                'user_id' => auth()->id(),
                'paiement_existant_id' => $paiementExistant->id
            ]);

            return redirect()->route('reservations.show', $reservation->id)
                ->with('error', '✅ Cette réservation a déjà été payée intégralement. Aucun paiement supplémentaire n\'est nécessaire.');
        }

        // ✅ CONTRÔLE 2: Vérifier si la réservation est déjà confirmée
        if ($reservation->statut === 'confirmée' && $reservation->paiement_id) {
            $paiement = Paiement::find($reservation->paiement_id);

            if ($paiement && $paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
                return redirect()->route('reservations.show', $reservation->id)
                    ->with('info', '✅ Le paiement de cette réservation est déjà finalisé.');
            }
        }

        try {
            // ✅ CHARGER LE BIEN ET LE MANDAT
            $reservation->load('bien.mandat', 'bien.proprietaire');
            $bien = $reservation->bien;

            if (!$bien) {
                return redirect()->back()
                    ->with('error', 'Le bien associé est introuvable.');
            }

            if (!$bien->price || $bien->price <= 0) {
                return redirect()->back()
                    ->with('error', 'Le prix du bien n\'est pas défini.');
            }

            if (!$bien->mandat || !in_array($bien->mandat->type_mandat, ['vente', 'gestion_locative'])) {
                return redirect()->back()
                    ->with('error', 'Ce bien n\'a pas de mandat valide.');
            }

            $typeMandat = $bien->mandat->type_mandat;

            // ✅ CALCUL DU MONTANT
            if ($typeMandat === 'vente') {
                $montantInitial = $bien->price * 0.10;
                $messageInfo = 'Acompte : 10% du prix de vente.';
            } elseif ($typeMandat === 'gestion_locative') {
                $montantInitial = $bien->price;
                $messageInfo = 'Dépôt de garantie : 1 mois de loyer (caution restituable).';
            } else {
                return redirect()->back()
                    ->with('error', 'Type de mandat non reconnu.');
            }

            // ✅ RÉCUPÉRER OU CRÉER LE PAIEMENT (seulement si non payé)
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
                    'montant' => $montantInitial
                ]);
            }

            return redirect()->route('paiement.initier.show', [$reservation->id, $paiement->id])
                ->with('info', $messageInfo);

        } catch (\Exception $e) {
            Log::error('❌ Erreur initialisation paiement réservation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()
                ->with('error', 'Erreur lors de la préparation du paiement.');
        }
    }
    public function store(Request $request)
    {
        try {
            Log::info('📥 === DÉBUT CRÉATION RÉSERVATION ===', [
                'user_id' => auth()->id(),
                'request_all' => $request->all()
            ]);

            // Validation de base
            $validated = $request->validate([
                'bien_id' => 'required|exists:biens,id',
                'appartement_id' => 'nullable|exists:appartements,id',
                'profession' => 'required|string|max:255',
                'numero_cni' => 'required|string|max:50',
                'personne_contact' => 'required|string|max:255',
                'telephone_contact' => 'required|string|max:20',
                'revenus_mensuels' => 'required|in:plus_100000,plus_200000,plus_300000,plus_500000',
                'carte_identite' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'derniere_quittance' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ]);

            $bien = Bien::with(['mandat', 'appartements', 'category'])->findOrFail($validated['bien_id']);

            // ✅ VÉRIFICATION CRITIQUE : Le propriétaire ne peut pas réserver son propre bien
            if (Auth::id() === $bien->proprietaire_id) {
                Log::warning('⛔ Tentative de réservation par le propriétaire', [
                    'user_id' => Auth::id(),
                    'bien_id' => $bien->id,
                    'proprietaire_id' => $bien->proprietaire_id
                ]);

                return redirect()->back()->withErrors([
                    'general' => 'Vous ne pouvez pas réserver votre propre bien. En tant que propriétaire, vous avez déjà accès à toutes les fonctionnalités de gestion de ce bien.'
                ])->withInput();
            }

            $typeMandat = $bien->mandat->type_mandat;


            $isImmeuble = $bien->category &&
                strtolower($bien->category->name) === 'appartement' &&
                $bien->appartements()->count() > 0;

            if ($isImmeuble) {
                if (!isset($validated['appartement_id'])) {
                    return redirect()->back()->withErrors([
                        'appartement' => 'Vous devez sélectionner un appartement spécifique.'
                    ]);
                }

                $appartement = Appartement::where('id', $validated['appartement_id'])
                    ->where('bien_id', $bien->id)
                    ->first();

                if (!$appartement || $appartement->statut !== 'disponible') {
                    return redirect()->back()->withErrors([
                        'appartement' => 'Cet appartement n\'est plus disponible.'
                    ]);
                }
            } else {
                if (!in_array($bien->status, ['disponible', 'en_vente'])) {
                    return redirect()->back()->withErrors([
                        'bien' => 'Ce bien n\'est plus disponible.'
                    ]);
                }
            }

            if (!$bien->mandat || !in_array($typeMandat, ['vente', 'gestion_locative'])) {
                return redirect()->back()->withErrors([
                    'bien' => 'Ce bien n\'a pas de mandat valide.'
                ]);
            }

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

            if ($typeMandat === 'vente') {
                $montantInitial = $bien->price * 0.10;
                $typeMontant = 'acompte';
            } else {
                $montantInitial = $bien->price;
                $typeMontant = 'depot_garantie';
            }

            $reservation = DB::transaction(function () use ($validated, $request, $bien, $montantInitial, $typeMontant, $isImmeuble, $typeMandat) {

                $user = auth()->user();

                $carteIdentitePath = $request->file('carte_identite')->store('documents/cartes_identite', 'public');

                $derniereQuittancePath = null;
                if ($request->hasFile('derniere_quittance')) {
                    $derniereQuittancePath = $request->file('derniere_quittance')->store('documents/quittances', 'public');
                }

                $dossierData = [
                    'client_id' => $user->id,
                    'profession' => $validated['profession'],
                    'numero_cni' => $validated['numero_cni'],
                    'personne_contact' => $validated['personne_contact'],
                    'telephone_contact' => $validated['telephone_contact'],
                    'revenus_mensuels' => $validated['revenus_mensuels'],
                    'carte_identite_path' => $carteIdentitePath,
                    'derniere_quittance_path' => $derniereQuittancePath,
                ];

                if ($isImmeuble && isset($validated['appartement_id'])) {
                    $appartement = Appartement::find($validated['appartement_id']);
                    $dossierData['nbchambres'] = $appartement->chambres;
                    $dossierData['nbsalons'] = $appartement->salons;
                    $dossierData['nbcuisines'] = $appartement->cuisines;
                    $dossierData['nbsalledebains'] = $appartement->salles_bain;
                    $dossierData['quartier_souhaite'] = $bien->address . ', ' . $bien->city;
                } else {
                    $dossierData['nbchambres'] = $bien->rooms;
                    $dossierData['nbsalons'] = $bien->living_rooms;
                    $dossierData['nbcuisines'] = $bien->kitchens;
                    $dossierData['nbsalledebains'] = $bien->bathrooms;
                    $dossierData['quartier_souhaite'] = $bien->address . ', ' . $bien->city;
                }

                ClientDossier::updateOrCreate(
                    ['client_id' => $user->id],
                    $dossierData
                );

                Log::info('✅ Dossier client créé/mis à jour', [
                    'client_id' => $user->id,
                    'has_quittance' => !is_null($derniereQuittancePath)
                ]);

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
                    'type_mandat' => $typeMandat
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

                return $reservation;
            });

            Log::info('🎉 === FIN CRÉATION RÉSERVATION AVEC SUCCÈS ===', [
                'reservation_id' => $reservation->id
            ]);

            $messageInfo = $typeMandat === 'vente'
                ? 'L\'acompte représente 10% du prix de vente.'
                : 'Le dépôt de garantie correspond à 1 mois de loyer.';

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

            if ($reservation->statut_before_update === 'confirmée') {
                if ($reservation->appartement_id) {
                    Appartement::where('id', $reservation->appartement_id)
                        ->update(['statut' => 'disponible']);
                    $reservation->bien->refresh();
                    $reservation->bien->updateStatutGlobal();
                } else {
                    $reservation->bien->update(['status' => 'disponible']);
                }
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
                $reservation->dossier_client = ClientDossier::where('client_id', $reservation->client_id)->first();
                return $reservation;
            });

        return Inertia::render('Admin/ReservationIndex', [
            'reservations' => $reservations ?? [],
            'userRoles' => Auth::user()->roles->pluck('name')->toArray()
        ]);
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
    }
}
