<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use App\Services\ContractPdfService;
use App\Services\PaydunyaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class PaiementController extends Controller
{
    protected $contractPdfService;
    protected $paydunya;

    const PAYDUNYA_MAX_AMOUNT = 3000000;

    public function __construct()
    {
        $this->contractPdfService = app(ContractPdfService::class);
        $this->paydunya = new PaydunyaService();
    }

    private function peutEtreFractionne(Paiement $paiement)
    {
        return $paiement->vente_id || $paiement->location_id;
    }

    public function showSucces(Paiement $paiement)
    {
        $paiement->refresh();
        $paiement->load([
            'reservation.bien.mandat',
            'location.bien.mandat',
            'vente.bien.mandat',
            'reservation.bien.proprietaire',
            'location.bien.proprietaire',
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

    private function getActionsDisponibles(Paiement $paiement)
    {
        $paiement->load([
            'reservation.bien.mandat',
            'vente.bien.mandat',
            'location.bien.mandat'
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
                    $venteExistante = Vente::where('biens_id', $bien->id)
                        ->where('acheteur_id', auth()->id())
                        ->exists();

                    if (!$venteExistante) {
                        $actions['peutProcederVente'] = true;
                    }
                } elseif ($mandat->type_mandat === 'gestion_locative' && $bien->status !== 'loue') {
                    $locationExistante = Location::where('bien_id', $bien->id)
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

    public function showInitierPaiement($id, $paiementId)
    {
        try {
            $paiement = Paiement::findOrFail($paiementId);

            if ($paiement->reservation_id) {
                $type = 'reservation';
                $item = Reservation::with(['bien', 'client'])->findOrFail($paiement->reservation_id);
                $itemUserId = $item->client_id;
            } elseif ($paiement->vente_id) {
                $type = 'vente';
                $item = Vente::with(['bien', 'acheteur'])->findOrFail($paiement->vente_id);
                $itemUserId = $item->acheteur_id;
            } elseif ($paiement->location_id) {
                $type = 'location';
                $item = Location::with(['bien', 'client'])->findOrFail($paiement->location_id);
                $itemUserId = $item->client_id;
            } else {
                abort(400, 'Type de paiement non reconnu');
            }

            if ($itemUserId !== auth()->id()) {
                abort(403, 'Accès non autorisé');
            }

            $montantRestant = max(0, $paiement->montant_total - $paiement->montant_paye);
            $infoFractionnement = null;

            if ($this->peutEtreFractionne($paiement) && $montantRestant > self::PAYDUNYA_MAX_AMOUNT) {
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
                    'necessite_fractionnement' => true,
                    'limite_paydunya' => self::PAYDUNYA_MAX_AMOUNT,
                    'nombre_tranches' => $nombreTranches,
                    'tranches' => $tranches,
                    'type' => $type,
                    'montant_a_payer' => min(self::PAYDUNYA_MAX_AMOUNT, $montantRestant)
                ];
            }

            $paiementAffichage = $paiement->toArray();
            $paiementAffichage['montant_a_payer'] = $montantRestant;

            return Inertia::render('Paiement/Index', [
                'type' => $type,
                'item' => $item,
                'paiement' => $paiementAffichage,
                'user' => auth()->user(),
                'infoFractionnement' => $infoFractionnement
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur affichage page paiement', [
                'error' => $e->getMessage()
            ]);
            return redirect()->route('home')->with('error', 'Impossible d\'accéder à la page de paiement');
        }
    }

    public function initier(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
            'mode_paiement' => 'required|in:mobile_money,wave,orange_money,mtn_money,moov_money,carte,virement',
            'tranche_numero' => 'nullable|integer|min:1'
        ]);

        try {
            $paiement = Paiement::with(['reservation', 'location', 'vente'])
                ->findOrFail($request->paiement_id);

            // Bloquer uniquement si COMPLÈTEMENT payé
            if ($paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce paiement a déjà été complété intégralement.'
                ], 422);
            }

            // Autoriser si partiellement payé
            if ($paiement->statut === 'partiellement_paye' && $paiement->montant_restant > 0) {
                Log::info('Paiement partiel autorisé - continuation des tranches', [
                    'paiement_id' => $paiement->id,
                    'montant_restant' => $paiement->montant_restant
                ]);
            }

            // Vérifier les doublons
            if ($this->checkDuplicatePayment($paiement)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Un paiement a déjà été effectué pour cet élément.'
                ], 422);
            }

            $montantRestant = max(0, $paiement->montant_total - $paiement->montant_paye);

            if ($montantRestant <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Il n\'y a plus de montant à payer pour ce paiement.'
                ], 422);
            }

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

                    if ($paiement) {
                        $isPartial = $customData['is_partial'] ?? false;

                        if ($isPartial) {
                            $montantTranche = $customData['montant_tranche'];
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
                            }
                        } else {
                            if ($paiement->statut !== 'reussi') {
                                $paiement->update([
                                    'statut' => 'reussi',
                                    'montant_paye' => $paiement->montant_total,
                                    'montant_restant' => 0,
                                    'date_transaction' => now(),
                                ]);
                                $this->updateItemStatus($paiement);
                            }
                        }
                    }
                }

                return response()->json(['status' => 'success'], 200);
            }

            return response()->json(['status' => 'failed'], 200);

        } catch (\Exception $e) {
            Log::error('Erreur callback', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error'], 500);
        }
    }

    public function retour($paiementId)
    {
        try {
            $paiement = Paiement::findOrFail($paiementId);

            if ($paiement->statut === 'en_attente' && $paiement->transaction_id) {
                $result = $this->paydunya->checkInvoiceStatus($paiement->transaction_id);

                if ($result['success'] && isset($result['status']) && $result['status'] == 'completed') {
                    $customData = $result['custom_data'] ?? [];
                    $isPartial = $customData['is_partial'] ?? false;

                    if ($isPartial) {
                        $montantTranche = $customData['montant_tranche'];
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
                        }
                    } else {
                        $paiement->update([
                            'statut' => 'reussi',
                            'montant_paye' => $paiement->montant_total,
                            'montant_restant' => 0,
                            'date_transaction' => now(),
                        ]);
                        $this->updateItemStatus($paiement);
                    }

                    $paiement->refresh();
                }
            }

            return redirect()->route('paiement.succes', $paiement);

        } catch (\Exception $e) {
            Log::error('Erreur retour paiement: ' . $e->getMessage());
            return redirect()->route('paiement.erreur')->with('error', 'Erreur lors de la vérification du paiement');
        }
    }

    private function updateItemStatus(Paiement $paiement)
    {
        try {
            if ($paiement->statut === 'reussi') {
                if ($paiement->reservation_id) {
                    $reservation = $paiement->reservation;
                    if ($reservation) {
                        $reservation->update(['statut' => 'confirmee']);
                        if ($reservation->bien && $reservation->bien->mandat) {
                            $reservation->bien->mandat->update(['statut' => 'actif']);
                        }
                    }
                } elseif ($paiement->location_id) {
                    $location = $paiement->location;
                    if ($location) {
                        DB::transaction(function () use ($location) {
                            $location->update(['statut' => 'active']);
                            $this->contractPdfService->generatePdf($location, 'location');
                            if ($location->bien) {
                                $location->bien->update(['status' => 'loue']);
                            }
                        });
                    }
                } elseif ($paiement->vente_id) {
                    $vente = $paiement->vente;
                    if ($vente) {
                        DB::transaction(function () use ($vente) {
                            $vente->update(['status' => 'confirmée']);
                            $this->contractPdfService->generatePdf($vente, 'vente');
                            if ($vente->bien) {
                                $vente->bien->update(['status' => 'vendu']);
                                if ($vente->bien->mandat) {
                                    $vente->bien->mandat->update(['statut' => 'expire']);
                                }
                            }
                        });
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour statut: ' . $e->getMessage());
        }
    }

    private function checkDuplicatePayment($paiement)
    {
        if ($paiement->reservation_id) {
            return Paiement::where('reservation_id', $paiement->reservation_id)
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
                ->where('id', '!=', $paiement->id)
                ->exists();
        } elseif ($paiement->location_id) {
            return Paiement::where('location_id', $paiement->location_id)
                ->where('statut', 'reussi')
                ->where('montant_restant', '<=', 0)
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
}
