<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use App\Services\CommissionService;
use App\Services\ContractPdfService;
use App\Services\PaydunyaService;
use App\Services\QuittanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaiementController extends Controller
{
    protected $contractPdfService;
    protected $paydunya;
    protected $commissionService;
    protected $quittanceService;


    const PAYDUNYA_MAX_AMOUNT = 3000000;

    public function __construct(CommissionService $commissionService,QuittanceService $quittanceService)
    {
        $this->contractPdfService = app(ContractPdfService::class);
        $this->paydunya = new PaydunyaService();
        $this->commissionService = $commissionService;
        $this->quittanceService = $quittanceService;
    }

    private function getActionsDisponibles(Paiement $paiement)
    {
        // ✅ CORRECTION: Relations correctes
        $paiement->load([
            'reservation.bien.mandat',
            'vente.bien.mandat',
            'location.reservation.bien.mandat'  // ✅ Via reservation
        ]);

        $actions = [
            'peutVisiter' => false,
            'peutProcederVente' => false,
            'peutProcederLocation' => false,
            'peutVoirVente' => false,
            'peutVoirLocation' => false,
            'bien' => null,
            'typeMandat' => null,
            'vente' => null,
            'location' => null
        ];

        if ($paiement->reservation_id && $paiement->statut === 'reussi') {
            $reservation = $paiement->reservation;
            $bien = $reservation->bien ?? null;
            $mandat = $bien->mandat ?? null;

            if ($bien && $mandat && $mandat->statut === 'actif') {
                $actions['bien'] = $bien;
                $actions['typeMandat'] = $mandat->type_mandat;
                $actions['peutVisiter'] = true;

                if ($mandat->type_mandat === 'vente' && $bien->status !== 'vendu') {
                    $venteExistante = Vente::where('reservation_id', $reservation->id)
                        ->where('acheteur_id', auth()->id())
                        ->exists();

                    if (!$venteExistante) {
                        $actions['peutProcederVente'] = true;
                    }
                } elseif ($mandat->type_mandat === 'gestion_locative' && $bien->status !== 'loue') {
                    // ✅ Vérifier via la relation reservation
                    $locationExistante = Location::whereHas('reservation', function($query) use ($bien) {
                        $query->where('bien_id', $bien->id);
                    })
                        ->where('client_id', auth()->id())
                        ->whereIn('statut', ['active', 'en_cours'])
                        ->exists();

                    if (!$locationExistante) {
                        $actions['peutProcederLocation'] = true;
                    }
                }
            }
        }

        if ($paiement->vente_id && $paiement->statut === 'reussi') {
            $actions['vente'] = $paiement->vente;
            $actions['peutVoirVente'] = true;
        }

        if ($paiement->location_id && $paiement->statut === 'reussi') {
            $actions['location'] = $paiement->location;
            $actions['peutVoirLocation'] = true;
        }

        return $actions;
    }
    private function initierPaiementSimple($paiement, $request, $montant)
    {
        $invoiceData = [
            'montant' => $montant,
            'description' => $request->description ?: "Paiement {$paiement->type} #{$paiement->id}",
            'custom_data' => [
                'paiement_id' => $paiement->id,
                'user_id' => auth()->id(),
                'type' => $paiement->type,
                'is_partial' => false,
                'montant_this_payment' => $montant
            ],
            'callback_url' => env('PAYDUNYA_CALLBACK_URL'),
            'return_url' => route('paiement.retour', $paiement->id),
            'cancel_url' => env('PAYDUNYA_CANCEL_URL')
        ];

        $result = $this->paydunya->createInvoice($invoiceData);

        if ($result['success']) {
            $paiement->update([
                'transaction_id' => $result['token'],
                'mode_paiement' => $request->mode_paiement,
                'statut' => 'en_attente'
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $result['url'],
                'token' => $result['token'],
                'message' => 'Paiement initié avec succès !'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    private function initierPaiementFractionne($paiement, $request, $montantRestant)
    {
        $trancheNumero = $request->input('tranche_numero', 1);
        $nombreTranches = ceil($montantRestant / self::PAYDUNYA_MAX_AMOUNT);
        $montantTranche = min(self::PAYDUNYA_MAX_AMOUNT, $montantRestant);

        $invoiceData = [
            'montant' => $montantTranche,
            'description' => sprintf(
                "Paiement %s #%d - Tranche %d/%d",
                $paiement->type,
                $paiement->id,
                $trancheNumero,
                $nombreTranches
            ),
            'custom_data' => [
                'paiement_id' => $paiement->id,
                'user_id' => auth()->id(),
                'type' => $paiement->type,
                'is_partial' => true,
                'tranche_numero' => $trancheNumero,
                'nombre_tranches' => $nombreTranches,
                'montant_tranche' => $montantTranche
            ],
            'callback_url' => env('PAYDUNYA_CALLBACK_URL'),
            'return_url' => route('paiement.retour', $paiement->id),
            'cancel_url' => env('PAYDUNYA_CANCEL_URL')
        ];

        $result = $this->paydunya->createInvoice($invoiceData);

        if ($result['success']) {
            $paiement->update([
                'transaction_id' => $result['token'],
                'mode_paiement' => $request->mode_paiement,
                'statut' => 'en_attente'
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => $result['url'],
                'token' => $result['token'],
                'is_partial' => true,
                'tranche_numero' => $trancheNumero,
                'nombre_tranches' => $nombreTranches,
                'montant_tranche' => $montantTranche,
                'montant_restant' => $montantRestant - $montantTranche,
                'message' => sprintf(
                    'Paiement de la tranche %d/%d initié avec succès ! (%s FCFA)',
                    $trancheNumero,
                    $nombreTranches,
                    number_format($montantTranche, 0, ',', ' ')
                )
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message']
        ], 500);
    }

    public function callback(Request $request)
    {
        try {
            $token = $request->input('token');

            if (!$token) {
                return response()->json(['status' => 'error'], 400);
            }

            $result = $this->paydunya->checkInvoiceStatus($token);

            if ($result['success'] && isset($result['status']) && $result['status'] == 'completed') {
                $customData = $result['custom_data'];
                $paiementId = $customData['paiement_id'] ?? null;

                if ($paiementId) {
                    $paiement = Paiement::find($paiementId);

                    if ($paiement && $paiement->statut !== 'reussi') {
                        $isPartial = $customData['is_partial'] ?? false;

                        if ($isPartial) {
                            $montantTranche = $customData['montant_tranche'] ?? 0;
                            $nouveauMontantPaye = $paiement->montant_paye + $montantTranche;
                            $nouveauMontantRestant = max(0, $paiement->montant_total - $nouveauMontantPaye);
                            $nouveauStatut = ($nouveauMontantRestant <= 0) ? 'reussi' : 'partiellement_paye';

                            $paiement->update([
                                'montant_paye' => $nouveauMontantPaye,
                                'montant_restant' => $nouveauMontantRestant,
                                'statut' => $nouveauStatut,
                                'date_transaction' => now(),
                            ]);

                            if ($nouveauStatut === 'reussi') {
                                $this->updateItemStatus($paiement);

                                // 🔥 ENVOI AUTOMATIQUE DES DOCUMENTS APRÈS PAIEMENT COMPLET
                                $this->envoyerDocumentsApresPaiement($paiement);
                            }
                        } else {
                            $paiement->update([
                                'statut' => 'reussi',
                                'montant_paye' => $paiement->montant_total,
                                'montant_restant' => 0,
                                'date_transaction' => now(),
                            ]);

                            $this->updateItemStatus($paiement);

                            // 🔥 ENVOI AUTOMATIQUE DES DOCUMENTS
                            $this->envoyerDocumentsApresPaiement($paiement);
                        }
                    }

                    return response()->json(['status' => 'success'], 200);
                }
            }

            return response()->json(['status' => 'failed'], 200);

        } catch (\Exception $e) {
            Log::error('❌ Erreur callback', [
                'message' => $e->getMessage(),
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function renvoyerDocument(Paiement $paiement)
    {
        $user = auth()->user();

        // Vérifier les autorisations
        if ($paiement->type === 'location') {
            if (!$paiement->location ||
                ($paiement->location->client_id !== $user->id && !$user->hasRole('admin'))) {
                abort(403);
            }
        } elseif ($paiement->type === 'vente') {
            if (!$paiement->vente ||
                ($paiement->vente->acheteur_id !== $user->id && !$user->hasRole('admin'))) {
                abort(403);
            }
        }

        try {
            $resultat = null;

            if ($paiement->type === 'location' && $paiement->location) {
                $resultat = $this->quittanceService->genererEtEnvoyerQuittanceLoyer($paiement);
            } elseif ($paiement->type === 'vente' && $paiement->vente) {
                $resultat = $this->quittanceService->genererEtEnvoyerRecuVente($paiement->vente, $paiement);
            }

            if ($resultat && $resultat['success']) {
                return back()->with('success', 'Document renvoyé avec succès par email');
            } else {
                return back()->with('error', 'Erreur lors de l\'envoi : ' . ($resultat['message'] ?? 'Erreur inconnue'));
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }
    // Méthodes standard CRUD
    public function index()
    {
        $paiements = Paiement::with(['vente', 'location', 'reservation'])->get();
        return response()->json($paiements);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:vente,location,reservation',
            'montant_total' => 'required|numeric|min:0',
            'montant_paye' => 'required|numeric|min:0',
            'mode_paiement' => 'required|in:carte,mobile_money,wave,orange_money,mtn_money,moov_money,virement',
            'transaction_id' => 'nullable|string|max:255',
            'vente_id' => 'nullable|exists:ventes,id',
            'location_id' => 'nullable|exists:locations,id',
            'reservation_id' => 'nullable|exists:reservations,id',
        ]);

        $paiement = Paiement::create([
            'type' => $request->type,
            'montant_total' => $request->montant_total,
            'montant_paye' => $request->montant_paye,
            'montant_restant' => $request->montant_total - $request->montant_paye,
            'commission_agence' => $request->montant_total * 0.05,
            'mode_paiement' => $request->mode_paiement,
            'transaction_id' => $request->transaction_id,
            'statut' => 'en_attente',
            'date_transaction' => now(),
            'vente_id' => $request->type === 'vente' ? $request->vente_id : null,
            'location_id' => $request->type === 'location' ? $request->location_id : null,
            'reservation_id' => $request->type === 'reservation' ? $request->reservation_id : null,
        ]);

        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'paiement' => $paiement
        ], 201);
    }

    public function show($id)
    {
        $paiement = Paiement::with(['vente', 'location', 'reservation'])->findOrFail($id);
        return response()->json($paiement);
    }

    public function update(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);

        $request->validate([
            'statut' => 'in:en_attente,reussi,echoue,partiellement_paye',
            'montant_paye' => 'nullable|numeric|min:0',
        ]);

        if ($request->has('statut')) {
            $paiement->statut = $request->statut;
        }

        if ($request->has('montant_paye')) {
            $paiement->montant_paye = $request->montant_paye;
            $paiement->montant_restant = $paiement->montant_total - $paiement->montant_paye;
        }

        $paiement->save();

        return response()->json([
            'message' => 'Paiement mis à jour avec succès.',
            'paiement' => $paiement
        ]);
    }

    public function destroy($id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->delete();
        return response()->json(['message' => 'Paiement supprimé avec succès.']);
    }

    public function showErreur(Request $request)
    {
        $message = $request->session()->get('error', 'Une erreur est survenue lors du paiement');
        return Inertia::render('Paiement/Erreur', [
            'message' => $message,
            'previous_url' => $request->session()->get('previous_url', route('home'))
        ]);
    }

    private function peutEtreFractionne(Paiement $paiement)
    {
        return $paiement->vente_id || $paiement->location_id;
    }

    public function showSucces(Paiement $paiement)
    {
        $paiement->refresh();

        // ✅ CORRECTION: Charger les relations correctement selon le type
        $paiement->load([
            'reservation.bien.mandat',
            'reservation.bien.proprietaire',
            'location.reservation.bien.mandat',      // ✅ Via reservation
            'location.reservation.bien.proprietaire', // ✅ Via reservation
            'location.client',
            'vente.bien.mandat',
            'vente.bien.proprietaire'
        ]);

        Log::info('=== PAGE SUCCES ===', [
            'paiement_id' => $paiement->id,
            'type' => $paiement->type,
            'statut' => $paiement->statut,
            'montant_total' => $paiement->montant_total,
            'montant_paye' => $paiement->montant_paye,
            'montant_restant' => $paiement->montant_restant
        ]);

        // ✅ VÉRIFIER ET METTRE À JOUR LE STATUT SI PAIEMENT COMPLET
        if ($paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
            Log::info('🎯 Paiement COMPLET détecté dans showSucces');

            $this->updateItemStatus($paiement);

            // 🆕 CRÉER LES COMMISSIONS
            try {
                $commissions = $this->commissionService->creerCommissionsApresPaiement($paiement);
                if ($commissions) {
                    Log::info('✅ Commissions créées avec succès', [
                        'nombre' => is_array($commissions) ? count($commissions) : 1
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Erreur création commissions', [
                    'error' => $e->getMessage()
                ]);
            }

            $paiement->refresh();
            $paiement->unsetRelation('vente');
            $paiement->unsetRelation('location');
            $paiement->unsetRelation('reservation');

            // ✅ CORRECTION: Recharger avec les bonnes relations
            $paiement->load([
                'reservation.bien.mandat',
                'reservation.bien.proprietaire',
                'location.reservation.bien.mandat',
                'location.reservation.bien.proprietaire',
                'location.client',
                'vente.bien.mandat',
                'vente.bien.proprietaire'
            ]);

            if ($paiement->vente_id) {
                $vente = Vente::find($paiement->vente_id);
                if ($vente) {
                    Log::info('Vente rechargée après updateItemStatus', [
                        'vente_id' => $vente->id,
                        'status' => $vente->status
                    ]);
                    $paiement->setRelation('vente', $vente);
                }
            }
        }

        $montantRestant = max(0, $paiement->montant_total - $paiement->montant_paye);
        $estPartiellementPaye = $montantRestant > 0 && $this->peutEtreFractionne($paiement);

        $infoFractionnement = null;
        if ($estPartiellementPaye) {
            $nombreTranches = ceil($montantRestant / self::PAYDUNYA_MAX_AMOUNT);
            $tranches = [];
            $montantTemp = $montantRestant;

            for ($i = 1; $i <= $nombreTranches; $i++) {
                $montantTranche = min(self::PAYDUNYA_MAX_AMOUNT, $montantTemp);
                $tranches[] = [
                    'numero' => $i,
                    'montant' => $montantTranche,
                    'statut' => 'en_attente'
                ];
                $montantTemp -= $montantTranche;
            }

            $infoFractionnement = [
                'montant_restant' => $montantRestant,
                'nombre_tranches_restantes' => $nombreTranches,
                'tranches' => $tranches,
                'pourcentage_paye' => ($paiement->montant_paye / $paiement->montant_total) * 100
            ];
        }

        $actionsDisponibles = $this->getActionsDisponibles($paiement);

        return Inertia::render('Paiement/Succes', [
            'paiement' => $paiement,
            'actionsDisponibles' => $actionsDisponibles,
            'estPartiellementPaye' => $estPartiellementPaye,
            'infoFractionnement' => $infoFractionnement
        ]);
    }

    private function updateItemStatus(Paiement $paiement)
    {
        try {
            $paiement->refresh();

            $statutEstReussi = ($paiement->statut === 'reussi');
            $montantRestantZero = ($paiement->montant_restant <= 0);
            $conditionRemplie = $statutEstReussi && $montantRestantZero;

            if ($conditionRemplie) {
                Log::info('✅ Condition REMPLIE - Traitement en cours');

                if ($paiement->vente_id) {
                    $this->traiterVenteComplete($paiement);
                } elseif ($paiement->location_id && $paiement->type === 'location') {
                    $this->traiterLoyerMensuelComplete($paiement);
                } elseif ($paiement->reservation_id) {
                    $this->traiterReservationComplete($paiement);
                } elseif ($paiement->location_id) {
                    $this->traiterLocationComplete($paiement);
                }

                // 🔥 AJOUT CRITIQUE : Envoi automatique après traitement
                $this->envoyerDocumentsApresPaiement($paiement);
            }

            Log::info('🏁 === FIN updateItemStatus ===');

        } catch (\Exception $e) {
            Log::error('💥 === ERREUR updateItemStatus ===', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Traiter un paiement de loyer mensuel complet
     */
    private function traiterLoyerMensuelComplete(Paiement $paiement)
    {
        $location = Location::find($paiement->location_id);
        if (!$location) return;

        DB::transaction(function () use ($location, $paiement) {
            // Le paiement est marqué comme réussi
            // Pas besoin de changer le statut de la location elle-même

            Log::info('✅ Loyer mensuel payé', [
                'location_id' => $location->id,
                'paiement_id' => $paiement->id,
                'mois' => Carbon::parse($paiement->created_at)->format('Y-m'),
                'montant' => $paiement->montant_total,
            ]);

            // Vérifier s'il y a des retards de paiement
            $loyersEnRetard = Paiement::where('location_id', $location->id)
                ->where('type', 'location')
                ->where('statut', '!=', 'reussi')
                ->whereRaw('DATE_ADD(DATE_FORMAT(created_at, "%Y-%m-01"), INTERVAL 1 MONTH) < CURDATE()')
                ->count();

            // Si la location était en retard et qu'il n'y a plus de retards
            if ($location->statut === 'en_retard' && $loyersEnRetard === 0) {
                DB::table('locations')
                    ->where('id', $location->id)
                    ->update([
                        'statut' => 'active',
                        'updated_at' => now()
                    ]);

                Log::info('✅ Location remise en statut actif (plus de retards)', [
                    'location_id' => $location->id
                ]);
            }
            // Si ce n'était pas en retard mais qu'il y a maintenant des retards
            elseif ($location->statut === 'active' && $loyersEnRetard > 0) {
                DB::table('locations')
                    ->where('id', $location->id)
                    ->update([
                        'statut' => 'en_retard',
                        'updated_at' => now()
                    ]);

                Log::info('⚠️ Location marquée en retard', [
                    'location_id' => $location->id,
                    'nombre_retards' => $loyersEnRetard
                ]);
            }
        });

        Log::info('✅ Traitement loyer mensuel terminé');
    }


    private function traiterVenteComplete(Paiement $paiement)
    {
        $vente = Vente::find($paiement->vente_id);
        if (!$vente) return;

        $statusAvant = DB::table('ventes')->where('id', $vente->id)->value('status');
        if ($statusAvant === 'confirmée') {
            Log::info('ℹ️ Vente déjà confirmée');
            return;
        }

        DB::transaction(function () use ($vente, $paiement) {
            // ✅ 1. Mettre à jour le statut de la vente à "confirmée"
            DB::table('ventes')
                ->where('id', $vente->id)
                ->update([
                    'status' => 'confirmée',
                    'updated_at' => now()
                ]);
            $vente->bien()->status = "vendu";


            Log::info('✅ Statut vente mis à jour : confirmée', [
                'vente_id' => $vente->id
            ]);


            // ✅ 2. Générer le contrat PDF (pour signature)
            try {
                $this->contractPdfService->generatePdf($vente, 'vente');
                Log::info('✅ PDF contrat généré');
            } catch (\Exception $e) {
                Log::error('⚠️ Erreur génération PDF', ['error' => $e->getMessage()]);
            }

            // ✅ 3. Sauvegarder l'ancien propriétaire
            $vente->load('reservation.bien.mandat');
            $bien = $vente->reservation?->bien;

            if ($bien && !$vente->ancien_proprietaire_id) {
                DB::table('ventes')
                    ->where('id', $vente->id)
                    ->update(['ancien_proprietaire_id' => $bien->proprietaire_id]);

                Log::info('✅ Ancien propriétaire sauvegardé', [
                    'ancien_proprietaire_id' => $bien->proprietaire_id
                ]);
            }

            // ✅ 4. CORRECTION CRITIQUE : NE PAS transférer la propriété maintenant
            // Le transfert se fera UNIQUEMENT après signature complète du contrat
            // (voir VenteController@signByAcheteur)

            // ✅ 5. Marquer le bien comme "réservé" (pas "vendu")
            if ($bien) {
                DB::table('biens')
                    ->where('id', $bien->id)
                    ->update([
                        'status' => 'reserve', // ✅ Réservé en attendant signature
                        'updated_at' => now()
                    ]);

                Log::info('✅ Bien marqué comme RÉSERVÉ (en attente de signature)', [
                    'bien_id' => $bien->id,
                    'status' => 'reserve'
                ]);
            }

            // ✅ 6. Mettre à jour la réservation
            if ($vente->reservation) {
                DB::table('reservations')
                    ->where('id', $vente->reservation->id)
                    ->update([
                        'statut' => 'confirmee',
                        'updated_at' => now()
                    ]);
            }

            Log::info('✅ Vente confirmée - En attente de signature du contrat', [
                'vente_id' => $vente->id,
                'bien_status' => 'reserve'
            ]);
        });
    }
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
                Log::info('🏢 Paiement réussi - Mise à jour statut appartement SEULEMENT', [
                    'appartement_id' => $reservation->appartement_id,
                    'bien_id' => $bien->id
                ]);

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

                Log::info('📊 Statut global du bien après paiement', [
                    'bien_id' => $bien->id,
                    'statut_final' => $bien->fresh()->status
                ]);
            } else {
                // ✅ Pour un BIEN CLASSIQUE : Marquer le bien comme réservé
                Log::info('🏠 Paiement réussi - Mise à jour statut du bien', [
                    'bien_id' => $bien->id
                ]);

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

        Log::info('✅ Réservation confirmée après paiement réussi');
    }

    private function traiterLocationComplete(Paiement $paiement)
    {
        // Recharger avec toutes les relations nécessaires
        $paiement->load([
            'location.reservation.bien.mandat',
            'location.reservation.appartement',
            'location.client'
        ]);

        $location = Location::with([
            'reservation.bien.mandat',
            'reservation.appartement',
            'client'
        ])->find($paiement->location_id);

        if (!$location) {
            Log::error('❌ Location introuvable', ['location_id' => $paiement->location_id]);
            return;
        }

        DB::transaction(function () use ($location, $paiement) {
            // ✅ 1. ACTIVER LA LOCATION
            DB::table('locations')
                ->where('id', $location->id)
                ->update([
                    'statut' => 'active',
                    'updated_at' => now()
                ]);

            Log::info('✅ Location activée après paiement complet', [
                'location_id' => $location->id,
                'ancien_statut' => $location->statut,
                'nouveau_statut' => 'active'
            ]);

            // ✅ 2. LIER LE PAIEMENT À LA RÉSERVATION
            if ($location->reservation_id && !$location->reservation->paiement_id) {
                DB::table('reservations')
                    ->where('id', $location->reservation_id)
                    ->update([
                        'paiement_id' => $paiement->id,
                        'statut' => 'confirmee',
                        'updated_at' => now()
                    ]);

                Log::info('✅ Paiement lié à la réservation', [
                    'reservation_id' => $location->reservation_id,
                    'paiement_id' => $paiement->id
                ]);
            }

            // ✅ 3. MARQUER L'APPARTEMENT COMME LOUÉ
            if ($location->reservation && $location->reservation->appartement_id) {
                DB::table('appartements')
                    ->where('id', $location->reservation->appartement_id)
                    ->update([
                        'statut' => 'loue',
                        'updated_at' => now()
                    ]);

                Log::info('🏠 Appartement marqué comme loué', [
                    'appartement_id' => $location->reservation->appartement_id,
                    'location_id' => $location->id
                ]);
            }

            // ✅ 4. METTRE À JOUR LE STATUT DU BIEN
            if ($location->reservation && $location->reservation->bien) {
                $bien = $location->reservation->bien;
                $bien->updateStatutGlobal();

                Log::info('🏢 Statut bien mis à jour', [
                    'bien_id' => $bien->id,
                    'nouveau_statut' => $bien->fresh()->status
                ]);
            }

            // ✅ 5. CRÉER LES COMMISSIONS
            try {
                $commissions = $this->commissionService->creerCommissionsApresPaiement($paiement);
                if ($commissions) {
                    Log::info('💰 Commissions créées', [
                        'location_id' => $location->id,
                        'nombre' => is_array($commissions) ? count($commissions) : 1
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('❌ Erreur création commissions', [
                    'error' => $e->getMessage()
                ]);
            }

            // ✅ 6. GÉNÉRER LE PDF DU CONTRAT
            try {
                $this->contractPdfService->generatePdf($location, 'location');
                Log::info('📄 PDF contrat généré', ['location_id' => $location->id]);
            } catch (\Exception $e) {
                Log::error('⚠️ Erreur PDF location', ['error' => $e->getMessage()]);
            }
        });

        Log::info('🎉 Location complètement traitée - Statut: ACTIVE', [
            'location_id' => $location->id,
            'paiement_id' => $paiement->id
        ]);
    }
    public function showInitierPaiement($id, $paiement_id)
    {
        try {
            $paiement = Paiement::findOrFail($paiement_id);

            // ✅ LOGS DÉTAILLÉS
            Log::info('📄 Accès page paiement', [
                'paiement_id' => $paiement->id,
                'user_id' => auth()->id(),
                'statut' => $paiement->statut,
                'montant_restant' => $paiement->montant_restant
            ]);

            // ✅ CONTRÔLE GLOBAL 1: Vérifier si le paiement est déjà complet
            if ($paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
                Log::warning('⚠️ Accès à une page de paiement déjà complété', [
                    'paiement_id' => $paiement->id,
                    'user_id' => auth()->id()
                ]);

                // Rediriger vers la page appropriée
                if ($paiement->reservation_id) {
                    return redirect()->route('reservations.show', $paiement->reservation_id)
                        ->with('info', '✅ Ce paiement a déjà été effectué avec succès.');
                } elseif ($paiement->vente_id) {
                    return redirect()->route('ventes.show', $paiement->vente_id)
                        ->with('info', '✅ Ce paiement a déjà été effectué avec succès.');
                } elseif ($paiement->location_id) {
                    return redirect()->route('locations.show', $paiement->location_id)
                        ->with('info', '✅ Ce paiement a déjà été effectué avec succès.');
                }

                return redirect()->route('home')
                    ->with('info', '✅ Ce paiement a déjà été effectué.');
            }

            // Déterminer le type et charger les données
            $type = null;
            $item = null;
            $itemUserId = null;
            $infoFractionnement = null;

            // ✅ GESTION PAR TYPE
            if ($paiement->vente_id) {
                $type = 'vente';
                $item = Vente::with(['reservation.bien'])->find($paiement->vente_id);

                if (!$item) {
                    Log::error('❌ Vente introuvable', ['vente_id' => $paiement->vente_id]);
                    return redirect()->route('home')
                        ->with('error', 'Transaction introuvable');
                }

                $itemUserId = $item->acheteur_id;

            } elseif ($paiement->reservation_id) {
                $type = 'reservation';
                $item = Reservation::with(['bien'])->find($paiement->reservation_id);

                if (!$item) {
                    Log::error('❌ Réservation introuvable', ['reservation_id' => $paiement->reservation_id]);
                    return redirect()->route('home')
                        ->with('error', 'Réservation introuvable');
                }

                $itemUserId = $item->client_id;

            } elseif ($paiement->location_id) {
                $type = 'location';
                // ✅ CORRECTION: Retirer 'bien' de with()
                $item = Location::with(['reservation.bien', 'client'])->find($paiement->location_id);

                if (!$item) {
                    Log::error('❌ Location introuvable', ['location_id' => $paiement->location_id]);
                    return redirect()->route('home')
                        ->with('error', 'Location introuvable');
                }

                $itemUserId = $item->client_id;
            }else {
                Log::error('❌ Type de paiement non reconnu', [
                    'paiement_id' => $paiement->id,
                    'reservation_id' => $paiement->reservation_id,
                    'location_id' => $paiement->location_id,
                    'vente_id' => $paiement->vente_id
                ]);

                return redirect()->route('home')
                    ->with('error', 'Type de paiement non reconnu');
            }

            // ✅ CONTRÔLE 2: Vérification de l'autorisation
            if ($itemUserId !== auth()->id() && !auth()->user()->hasRole('admin')) {
                Log::warning('⛔ Accès non autorisé au paiement', [
                    'paiement_id' => $paiement->id,
                    'user_id' => auth()->id(),
                    'item_user_id' => $itemUserId
                ]);

                abort(403, 'Accès non autorisé à ce paiement');
            }

            // ✅ CALCUL DU FRACTIONNEMENT SI NÉCESSAIRE
            $montantRestant = max(0, $paiement->montant_total - $paiement->montant_paye);
            $PAYDUNYA_MAX = 3000000;

            if ($montantRestant > $PAYDUNYA_MAX) {
                $nombreTranches = ceil($montantRestant / $PAYDUNYA_MAX);
                $montantAPayer = min($PAYDUNYA_MAX, $montantRestant);

                $infoFractionnement = [
                    'montant_restant_total' => $montantRestant,
                    'montant_a_payer' => $montantAPayer,
                    'nombre_tranches' => $nombreTranches,
                    'limite_paydunya' => $PAYDUNYA_MAX,
                    'pourcentage_paye' => ($paiement->montant_paye / $paiement->montant_total) * 100
                ];

                Log::info('💰 Paiement fractionné détecté', $infoFractionnement);
            }

            // ✅ LOGS AVANT RENDU
            Log::info('✅ Affichage page paiement', [
                'type' => $type,
                'item_id' => $item->id ?? null,
                'montant' => $paiement->montant_total,
                'fractionnement' => $infoFractionnement ? 'oui' : 'non'
            ]);

            return Inertia::render('Paiement/InitierPaiement', [
                'type' => $type,
                'item' => $item,
                'paiement' => $paiement,
                'user' => auth()->user(),
                'infoFractionnement' => $infoFractionnement
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ERREUR CRITIQUE - showInitierPaiement', [
                'paiement_id' => $paiement_id ?? null,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')
                ->with('error', 'Erreur lors du chargement de la page de paiement : ' . $e->getMessage());
        }
    }

    /**
     * ✅ Vérifier le statut d'une facture PayDunya
     */
    private function verifierStatutPayDunya($transactionId)
    {
        try {
            if (!$transactionId) {
                Log::warning('⚠️ Transaction ID manquant');
                return 'failed';
            }

            $result = $this->paydunya->checkInvoiceStatus($transactionId);

            Log::info('🔍 Vérification statut PayDunya', [
                'transaction_id' => $transactionId,
                'success' => $result['success'] ?? false,
                'status' => $result['status'] ?? 'unknown'
            ]);

            if ($result['success'] && isset($result['status'])) {
                return $result['status']; // 'completed', 'pending', 'cancelled'
            }

            return 'failed';

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification PayDunya', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);
            return 'failed';
        }
    }
// ✅ MÉTHODE: initier - AVEC CONTRÔLES AVANT TRAITEMENT
    public function initier(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'mode_paiement' => 'required|in:mobile_money,wave,orange_money,mtn_money,moov_money,carte,virement',
        ]);

        try {
            $paiement = Paiement::with(['reservation', 'location', 'vente'])
                ->findOrFail($request->paiement_id);

            // ✅ CONTRÔLE 1: Bloquer si COMPLÈTEMENT payé
            if ($paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
                Log::warning('⚠️ Tentative d\'initier un paiement déjà complet', [
                    'paiement_id' => $paiement->id,
                    'user_id' => auth()->id()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => '✅ Ce paiement a déjà été complété intégralement. Aucune action requise.'
                ], 422);
            }

            // ✅ CONTRÔLE 2: Vérifier les doublons selon le type
            if ($this->checkDuplicatePayment($paiement)) {
                return response()->json([
                    'success' => false,
                    'message' => '✅ Un paiement a déjà été effectué pour cet élément.'
                ], 422);
            }

            // Autoriser si partiellement payé (pour fractionnement)
            if ($paiement->statut === 'partiellement_paye' && $paiement->montant_restant > 0) {
                Log::info('✅ Paiement partiel autorisé - continuation des tranches', [
                    'paiement_id' => $paiement->id,
                    'montant_restant' => $paiement->montant_restant
                ]);
            }

            $montantRestant = max(0, $paiement->montant_total - $paiement->montant_paye);

            if ($montantRestant <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => '✅ Il n\'y a plus de montant à payer pour ce paiement.'
                ], 422);
            }

            // Continuer avec la logique de paiement...
            $peutFractionner = $this->peutEtreFractionne($paiement);
            $necessiteFractionnement = $montantRestant > self::PAYDUNYA_MAX_AMOUNT;

            if ($peutFractionner && $necessiteFractionnement) {
                return $this->initierPaiementFractionne($paiement, $request, $montantRestant);
            } else {
                return $this->initierPaiementSimple($paiement, $request, $montantRestant);
            }

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement', [
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement.'
            ], 500);
        }
    }

// ✅ MÉTHODE UTILITAIRE: Vérifier les doublons de paiement
    private function checkDuplicatePayment($paiement)
    {
        if ($paiement->reservation_id) {
            return Paiement::where('reservation_id', $paiement->reservation_id)
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
                ->where('id', '!=', $paiement->id)
                ->exists();
        } elseif ($paiement->location_id && $paiement->type === 'location') {
            // Paiement initial de location
            return Paiement::where('location_id', $paiement->location_id)
                ->where('type', 'location')
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
                ->where('id', '!=', $paiement->id)
                ->exists();
        } elseif ($paiement->location_id && $paiement->type === 'location') {
            // Loyer mensuel - vérifier pour le même mois
            $moisConcerne = Carbon::parse($paiement->created_at);
            return Paiement::where('location_id', $paiement->location_id)
                ->where('type', 'location')
                ->whereYear('created_at', $moisConcerne->year)
                ->whereMonth('created_at', $moisConcerne->month)
                ->where('statut', 'reussi')
                ->where('id', '!=', $paiement->id)
                ->exists();
        } elseif ($paiement->vente_id) {
            return Paiement::where('vente_id', $paiement->vente_id)
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
                ->where('id', '!=', $paiement->id)
                ->exists();
        }

        return false;
    }

    private function envoyerDocumentsApresPaiement(Paiement $paiement)
    {
        try {
            Log::info('📧 === DÉBUT ENVOI DOCUMENTS ===', [
                'paiement_id' => $paiement->id,
                'type' => $paiement->type,
            ]);

            $resultat = null;

            // 📄 LOYER MENSUEL
            if ($paiement->type === 'loyer_mensuel' && $paiement->location) {
                Log::info('📧 Envoi quittance loyer mensuel');
                $resultat = $this->quittanceService->genererEtEnvoyerQuittanceLoyer($paiement);
            }
            // 📄 PAIEMENT INITIAL LOCATION
            elseif ($paiement->type === 'location' && $paiement->location) {
                Log::info('📧 Envoi quittance paiement location');
                $resultat = $this->quittanceService->genererEtEnvoyerQuittancePaiementLocation($paiement);
            }
            // 📄 VENTE
            elseif ($paiement->type === 'vente' && $paiement->vente) {
                Log::info('📧 Envoi reçu vente');
                $resultat = $this->quittanceService->genererEtEnvoyerRecuVente($paiement->vente, $paiement);
            }

            if ($resultat && $resultat['success']) {
                Log::info('✅ Documents envoyés avec succès');
            } else {
                Log::error('❌ Échec envoi documents', [
                    'message' => $resultat['message'] ?? 'Erreur inconnue',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ ERREUR envoi documents', [
                'paiement_id' => $paiement->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
    public function retour(Request $request, $paiement)
    {
        try {
            $paiement = Paiement::with(['reservation', 'location', 'vente'])
                ->findOrFail($paiement);

            Log::info('📥 Callback retour PayDunya', [
                'paiement_id' => $paiement->id,
                'transaction_id' => $paiement->transaction_id,
                'statut_actuel' => $paiement->statut,
                'montant_total' => $paiement->montant_total,
                'montant_paye' => $paiement->montant_paye,
                'montant_restant' => $paiement->montant_restant
            ]);

            // ✅ CORRECTION: Vérifier si déjà complètement payé
            if ($paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
                Log::info('ℹ️ Callback reçu pour un paiement déjà validé et complet', [
                    'paiement_id' => $paiement->id
                ]);
                return redirect()->route('paiement.succes', $paiement->id);
            }

            $statut = $this->verifierStatutPayDunya($paiement->transaction_id);

            Log::info('🔍 Statut PayDunya vérifié', [
                'paiement_id' => $paiement->id,
                'statut' => $statut
            ]);

            if ($statut === 'completed') {
                DB::beginTransaction();

                try {
                    // ✅ CORRECTION: Récupérer les informations de la transaction PayDunya
                    $result = $this->paydunya->checkInvoiceStatus($paiement->transaction_id);
                    $customData = $result['custom_data'] ?? [];
                    $isPartial = $customData['is_partial'] ?? false;
                    $montantTranche = $customData['montant_tranche'] ?? null;

                    Log::info('💰 Informations transaction', [
                        'is_partial' => $isPartial,
                        'montant_tranche' => $montantTranche,
                        'montant_deja_paye' => $paiement->montant_paye
                    ]);

                    // ✅ CORRECTION: Calculer correctement le montant payé
                    if ($isPartial && $montantTranche) {
                        // Paiement fractionné - ajouter la tranche au montant déjà payé
                        $nouveauMontantPaye = $paiement->montant_paye + $montantTranche;
                    } else {
                        // Paiement simple - payer le montant total
                        $nouveauMontantPaye = $paiement->montant_total;
                    }

                    // ✅ Calculer le montant restant
                    $nouveauMontantRestant = max(0, $paiement->montant_total - $nouveauMontantPaye);

                    // ✅ CORRECTION CRITIQUE: Déterminer le statut selon le montant restant
                    $nouveauStatut = ($nouveauMontantRestant <= 0) ? 'reussi' : 'partiellement_paye';

                    Log::info('📊 Calculs de mise à jour', [
                        'ancien_montant_paye' => $paiement->montant_paye,
                        'nouveau_montant_paye' => $nouveauMontantPaye,
                        'nouveau_montant_restant' => $nouveauMontantRestant,
                        'nouveau_statut' => $nouveauStatut
                    ]);

                    // ✅ Mettre à jour le paiement
                    $paiement->update([
                        'statut' => $nouveauStatut,
                        'montant_paye' => $nouveauMontantPaye,
                        'montant_restant' => $nouveauMontantRestant,
                        'date_transaction' => now()
                    ]);

                    Log::info('✅ Paiement mis à jour', [
                        'paiement_id' => $paiement->id,
                        'nouveau_statut' => $nouveauStatut,
                        'montant_paye' => $nouveauMontantPaye,
                        'montant_restant' => $nouveauMontantRestant
                    ]);

                    // ✅ CORRECTION: Ne finaliser QUE si complètement payé
                    if ($nouveauStatut === 'reussi') {
                        Log::info('🎯 Paiement COMPLET - Traitement de la finalisation');
                        $this->updateItemStatus($paiement);

                        // Envoi automatique des documents
                        $this->envoyerDocumentsApresPaiement($paiement);
                    } else {
                        Log::info('⏳ Paiement PARTIEL - En attente des tranches suivantes', [
                            'montant_restant' => $nouveauMontantRestant,
                            'pourcentage_paye' => ($nouveauMontantPaye / $paiement->montant_total * 100)
                        ]);
                    }

                    DB::commit();

                    return redirect()->route('paiement.succes', $paiement->id);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('❌ Erreur traitement paiement dans retour()', [
                        'paiement_id' => $paiement->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            Log::warning('⚠️ Paiement non confirmé par PayDunya', [
                'paiement_id' => $paiement->id,
                'statut' => $statut
            ]);

            return redirect()->route('paiement.erreur')
                ->with('error', 'Le paiement n\'a pas été confirmé par PayDunya');

        } catch (\Exception $e) {
            Log::error('❌ Erreur callback retour paiement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('paiement.erreur')
                ->with('error', 'Une erreur est survenue lors de la vérification du paiement');
        }
    }
}
