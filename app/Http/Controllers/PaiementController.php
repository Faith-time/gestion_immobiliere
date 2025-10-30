<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use App\Services\CommissionService;
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
    protected $commissionService;

    const PAYDUNYA_MAX_AMOUNT = 3000000;

    public function __construct(CommissionService $commissionService)
    {
        $this->contractPdfService = app(ContractPdfService::class);
        $this->paydunya = new PaydunyaService();
        $this->commissionService = $commissionService;
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
                    $venteExistante = Vente::where('reservation_id', $reservation->id)
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

    /**
     * Afficher la page d'initiation de paiement
     * Route: /paiement/initier/{id}/{paiement_id}
     */
    /**
     * Afficher la page d'initiation de paiement
     * Route: /paiement/initier/{id}/{paiement_id}
     */
    public function showInitierPaiement($id, $paiement_id)
    {
        try {
            $paiement = Paiement::findOrFail($paiement_id);

            $type = null;
            $item = null;
            $itemUserId = null;
            $depotGarantie = null;
            $prixTotal = null;
            $premierMoisLoyer = null;

            // ==========================================
            // TRAITEMENT DES VENTES
            // ==========================================
            if ($paiement->vente_id) {
                $type = 'vente';
                $item = Vente::with(['reservation.bien.category', 'reservation.bien.mandat', 'acheteur'])
                    ->findOrFail($paiement->vente_id);
                $itemUserId = $item->acheteur_id;

                // Charger le bien via la réservation
                if ($item->reservation && $item->reservation->bien) {
                    $item->bien = $item->reservation->bien;
                }

                // Calculer le montant en déduisant le dépôt de garantie (10%)
                $prixVenteTotal = $item->prix_vente;
                $depotGarantie = $prixVenteTotal * 0.10; // 10% du prix
                $montantRestantAPayer = $prixVenteTotal - $depotGarantie; // 90% du prix

                // Mettre à jour le montant total du paiement si nécessaire
                if ($paiement->montant_total == $prixVenteTotal) {
                    $paiement->update([
                        'montant_total' => $montantRestantAPayer,
                        'montant_restant' => $montantRestantAPayer - $paiement->montant_paye
                    ]);
                    $paiement->refresh();
                }

                Log::info('💰 Calcul paiement vente avec dépôt déduit', [
                    'prix_vente_total' => $prixVenteTotal,
                    'depot_garantie_10%' => $depotGarantie,
                    'montant_a_payer_90%' => $montantRestantAPayer
                ]);

                $prixTotal = $prixVenteTotal;
            }
            // ==========================================
            // TRAITEMENT DES RÉSERVATIONS
            // ==========================================
            elseif ($paiement->reservation_id) {
                $type = 'reservation';
                $item = Reservation::with(['bien.category', 'bien.mandat', 'client'])
                    ->findOrFail($paiement->reservation_id);
                $itemUserId = $item->client_id;

                // Calculer le montant du dépôt pour les réservations
                $bien = $item->bien;

                if ($bien && $bien->mandat) {
                    $typeMandat = $bien->mandat->type_mandat;

                    if ($typeMandat === 'vente') {
                        // Pour une vente : dépôt de garantie = 10% du prix
                        $prixTotal = $bien->price;
                        $depotGarantie = $prixTotal * 0.10;

                        if ($paiement->montant_total != $depotGarantie) {
                            $paiement->update([
                                'montant_total' => $depotGarantie,
                                'montant_restant' => $depotGarantie - $paiement->montant_paye
                            ]);
                            $paiement->refresh();

                            Log::info('🔄 Montant paiement réservation vente corrigé', [
                                'prix_bien' => $prixTotal,
                                'depot_10%' => $depotGarantie
                            ]);
                        }

                        Log::info('💰 Paiement réservation vente', [
                            'prix_bien_total' => $prixTotal,
                            'depot_garantie_10%' => $depotGarantie
                        ]);

                    } elseif ($typeMandat === 'gestion_locative') {
                        // Pour une location : dépôt = 1 mois de loyer (100% du prix mensuel)
                        $montantDepot = $bien->price;

                        if ($paiement->montant_total != $montantDepot) {
                            $paiement->update([
                                'montant_total' => $montantDepot,
                                'montant_restant' => $montantDepot - $paiement->montant_paye
                            ]);
                            $paiement->refresh();

                            Log::info('🔄 Montant paiement réservation location corrigé', [
                                'loyer_mensuel' => $montantDepot
                            ]);
                        }

                        Log::info('💰 Paiement réservation location', [
                            'loyer_mensuel' => $montantDepot,
                            'type' => 'Dépôt de garantie (1 mois)'
                        ]);

                        $prixTotal = $bien->price;
                    }
                }
            }
            // ==========================================
            // TRAITEMENT DES LOCATIONS
            // ==========================================
            elseif ($paiement->location_id) {
                $type = 'location';
                $item = Location::with(['reservation.bien.category', 'reservation.bien.mandat', 'client', 'bien'])
                    ->findOrFail($paiement->location_id);
                $itemUserId = $item->client_id;

                // Charger le bien
                if ($item->reservation && $item->reservation->bien) {
                    $item->bien = $item->reservation->bien;
                }

                // Récupérer le bien pour calculer le loyer
                $bien = $item->bien ?? ($item->reservation ? $item->reservation->bien : null);

                if ($bien) {
                    $loyerMensuel = $bien->price;

                    // ✅ IMPORTANT : Pour une location, le montant initial est :
                    // Caution (1 loyer) + Premier mois (1 loyer) = 2x le loyer
                    // Le dépôt de garantie (1 loyer) a déjà été payé lors de la réservation

                    $montantPaiementAttendu = $loyerMensuel * 2; // Caution + 1er mois

                    // NE PAS modifier le montant_total si c'est déjà le bon montant
                    if ($paiement->montant_total != $montantPaiementAttendu) {
                        Log::warning('⚠️ Montant paiement location incorrect - Correction', [
                            'location_id' => $item->id,
                            'montant_actuel' => $paiement->montant_total,
                            'montant_attendu' => $montantPaiementAttendu,
                            'loyer_mensuel' => $loyerMensuel
                        ]);

                        $paiement->update([
                            'montant_total' => $montantPaiementAttendu,
                            'montant_restant' => $montantPaiementAttendu - $paiement->montant_paye
                        ]);
                        $paiement->refresh();
                    }

                    Log::info('💰 Paiement location - Paiement initial', [
                        'loyer_mensuel' => $loyerMensuel,
                        'caution_locative' => $loyerMensuel,
                        'premier_mois' => $loyerMensuel,
                        'montant_total_a_payer' => $montantPaiementAttendu,
                        'depot_deja_paye' => 'Via réservation (' . $loyerMensuel . ' FCFA)',
                        'duree_location_mois' => $item->duree_mois ?? 'Non définie'
                    ]);

                    $premierMoisLoyer = $loyerMensuel;
                    $depotGarantie = $loyerMensuel; // Déjà payé lors de la réservation
                    $prixTotal = $loyerMensuel;
                } else {
                    Log::error('❌ Bien introuvable pour la location', [
                        'location_id' => $item->id
                    ]);
                    abort(404, 'Bien associé à la location introuvable');
                }
            }
            elseif ($paiement->location_id && $paiement->type === 'loyer_mensuel') {
                $type = 'loyer_mensuel';
                $item = Location::with(['reservation.bien.category', 'reservation.bien.mandat', 'client', 'bien'])
                    ->findOrFail($paiement->location_id);
                $itemUserId = $item->client_id;

                // Charger le bien
                if ($item->reservation && $item->reservation->bien) {
                    $item->bien = $item->reservation->bien;
                }

                // Récupérer le bien pour afficher les infos
                $bien = $item->bien ?? ($item->reservation ? $item->reservation->bien : null);

                if ($bien) {
                    $loyerMensuel = $item->loyer_mensuel;

                    // Déterminer le mois concerné par ce paiement
                    // On utilise created_at du paiement pour identifier le mois
                    $moisPaiement = Carbon::parse($paiement->created_at);

                    Log::info('💰 Paiement loyer mensuel', [
                        'location_id' => $item->id,
                        'loyer_mensuel' => $loyerMensuel,
                        'mois_concerne' => $moisPaiement->format('F Y'),
                        'date_echeance' => $moisPaiement->copy()->addMonth()->day(10)->format('Y-m-d'),
                    ]);

                    $prixTotal = $loyerMensuel;

                    // Informations sur le mois concerné
                    $paiementAffichage['mois_concerne'] = $moisPaiement->translatedFormat('F Y');
                    $paiementAffichage['date_echeance'] = $moisPaiement->copy()->addMonth()->day(10)->format('Y-m-d');
                } else {
                    Log::error('❌ Bien introuvable pour le loyer mensuel', [
                        'location_id' => $item->id
                    ]);
                    abort(404, 'Bien associé à la location introuvable');
                }
            }
            // ==========================================
            // TYPE NON RECONNU
            // ==========================================
            else {
                Log::error('❌ Type de paiement non reconnu', [
                    'paiement_id' => $paiement->id,
                    'vente_id' => $paiement->vente_id,
                    'location_id' => $paiement->location_id,
                    'reservation_id' => $paiement->reservation_id
                ]);
                abort(400, 'Type de paiement non reconnu');
            }

            // ==========================================
            // VÉRIFICATION DE L'AUTORISATION
            // ==========================================
            if ($itemUserId !== auth()->id() && !auth()->user()->hasRole('admin')) {
                Log::warning('⚠️ Tentative d\'accès non autorisé au paiement', [
                    'paiement_id' => $paiement->id,
                    'user_id' => auth()->id(),
                    'item_user_id' => $itemUserId
                ]);
                abort(403, 'Accès non autorisé à ce paiement');
            }

            // ==========================================
            // CALCUL DU MONTANT RESTANT
            // ==========================================
            $montantRestant = max(0, $paiement->montant_total - $paiement->montant_paye);
            $infoFractionnement = null;

            // ==========================================
            // GESTION DU FRACTIONNEMENT
            // ==========================================
            // Gérer le fractionnement pour les ventes et locations (pas les réservations)
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
                    'montant_a_payer' => min(self::PAYDUNYA_MAX_AMOUNT, $montantRestant),
                    'montant_restant_total' => $montantRestant,
                    'pourcentage_paye' => $paiement->montant_total > 0
                        ? ($paiement->montant_paye / $paiement->montant_total) * 100
                        : 0
                ];

                Log::info('📊 Fractionnement nécessaire', [
                    'type' => $type,
                    'montant_restant' => $montantRestant,
                    'nombre_tranches' => $nombreTranches,
                    'premiere_tranche' => $infoFractionnement['montant_a_payer']
                ]);
            }

            // ==========================================
            // PRÉPARATION DES DONNÉES D'AFFICHAGE
            // ==========================================
            $paiementAffichage = $paiement->toArray();
            $paiementAffichage['montant_a_payer'] = $infoFractionnement
                ? $infoFractionnement['montant_a_payer']
                : $montantRestant;

            // Ajouter les infos spécifiques selon le type
            if ($type === 'vente' && isset($depotGarantie)) {
                $paiementAffichage['depot_garantie_deduit'] = $depotGarantie;
                $paiementAffichage['prix_vente_total'] = $prixTotal;
            }

            if ($type === 'reservation' && isset($depotGarantie)) {
                $paiementAffichage['depot_garantie'] = $depotGarantie;
                $paiementAffichage['prix_bien_total'] = $prixTotal;
            }

            if ($type === 'location' && isset($premierMoisLoyer)) {
                $paiementAffichage['premier_mois_loyer'] = $premierMoisLoyer;
                $paiementAffichage['depot_deja_paye'] = $premierMoisLoyer; // Le dépôt = 1 mois déjà payé lors de la réservation
                $paiementAffichage['duree_location_mois'] = $item->duree_mois ?? null;
            }

            Log::info('📄 Affichage page paiement', [
                'type' => $type,
                'item_id' => $id,
                'paiement_id' => $paiement->id,
                'montant_total' => $paiement->montant_total,
                'montant_paye' => $paiement->montant_paye,
                'montant_restant' => $montantRestant,
                'montant_a_payer' => $paiementAffichage['montant_a_payer'],
                'fractionnement' => $infoFractionnement ? 'oui' : 'non'
            ]);

            // ==========================================
            // RENDU DE LA VUE INERTIA
            // ==========================================
            return Inertia::render('Paiement/InitierPaiement', [
                'type' => $type,
                'item' => $item,
                'paiement' => $paiementAffichage,
                'user' => auth()->user(),
                'infoFractionnement' => $infoFractionnement
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('❌ Paiement ou item introuvable', [
                'paiement_id' => $paiement_id,
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return redirect()->route('home')->with('error', 'Paiement introuvable');

        } catch (\Exception $e) {
            Log::error('❌ Erreur affichage page paiement', [
                'paiement_id' => $paiement_id,
                'id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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

            Log::info('🔙 RETOUR PAIEMENT', [
                'paiement_id' => $paiement->id,
                'statut_actuel' => $paiement->statut,
                'transaction_id' => $paiement->transaction_id
            ]);

            if ($paiement->statut === 'en_attente' && $paiement->transaction_id) {
                Log::info('🔍 Vérification statut PayDunya');

                $result = $this->paydunya->checkInvoiceStatus($paiement->transaction_id);

                Log::info('📊 Résultat vérification', [
                    'success' => $result['success'],
                    'status' => $result['status'] ?? 'unknown'
                ]);

                if ($result['success'] && isset($result['status']) && $result['status'] == 'completed') {
                    $customData = $result['custom_data'] ?? [];
                    $isPartial = $customData['is_partial'] ?? false;

                    if ($isPartial) {
                        $montantTranche = $customData['montant_tranche'];
                        $nouveauMontantPaye = $paiement->montant_paye + $montantTranche;
                        $nouveauMontantRestant = max(0, $paiement->montant_total - $nouveauMontantPaye);
                        $nouveauStatut = ($nouveauMontantRestant <= 0) ? 'reussi' : 'partiellement_paye';

                        Log::info('💰 Mise à jour paiement partiel', [
                            'nouveau_statut' => $nouveauStatut,
                            'montant_restant' => $nouveauMontantRestant
                        ]);

                        $paiement->update([
                            'montant_paye' => $nouveauMontantPaye,
                            'montant_restant' => $nouveauMontantRestant,
                            'statut' => $nouveauStatut,
                            'date_transaction' => now(),
                        ]);

                        if ($nouveauStatut === 'reussi') {
                            Log::info('✅ Paiement complet - Appel updateItemStatus depuis retour');
                            $this->updateItemStatus($paiement);
                        }
                    } else {
                        Log::info('💰 Mise à jour paiement complet');

                        $paiement->update([
                            'statut' => 'reussi',
                            'montant_paye' => $paiement->montant_total,
                            'montant_restant' => 0,
                            'date_transaction' => now(),
                        ]);

                        Log::info('✅ Appel updateItemStatus depuis retour');
                        $this->updateItemStatus($paiement);
                    }

                    $paiement->refresh();

                    Log::info('✅ Paiement rechargé après mise à jour', [
                        'statut_final' => $paiement->statut,
                        'montant_restant' => $paiement->montant_restant
                    ]);
                }
            } else {
                Log::info('ℹ️ Paiement déjà traité ou pas de transaction_id', [
                    'statut' => $paiement->statut
                ]);

                // ✅ AJOUT : Même si le statut n'est pas "en_attente",
                // vérifier si la vente doit être mise à jour
                if ($paiement->statut === 'reussi' && $paiement->montant_restant <= 0) {
                    Log::info('🔄 Vérification updateItemStatus pour paiement déjà réussi');
                    $this->updateItemStatus($paiement);
                    $paiement->refresh();
                }
            }

            return redirect()->route('paiement.succes', $paiement);

        } catch (\Exception $e) {
            Log::error('❌ Erreur retour paiement: ' . $e->getMessage());
            return redirect()->route('paiement.erreur')->with('error', 'Erreur lors de la vérification du paiement');
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

            $paiement->load([
                'reservation.bien.mandat',
                'location.bien.mandat',
                'vente.bien.mandat',
                'reservation.bien.proprietaire',
                'location.bien.proprietaire',
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
                }elseif ($paiement->location_id && $paiement->type === 'loyer_mensuel') {
                    $this->traiterLoyerMensuelComplete($paiement);
                } elseif ($paiement->reservation_id) {
                    $this->traiterReservationComplete($paiement);
                } elseif ($paiement->location_id) {
                    $this->traiterLocationComplete($paiement);
                }
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
                ->where('type', 'loyer_mensuel')
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
            DB::table('ventes')
                ->where('id', $vente->id)
                ->update([
                    'status' => 'confirmée',
                    'updated_at' => now()
                ]);

            try {
                $this->contractPdfService->generatePdf($vente, 'vente');
            } catch (\Exception $e) {
                Log::error('⚠️ Erreur génération PDF', ['error' => $e->getMessage()]);
            }

            $vente->load('reservation.bien.mandat');
            $bien = $vente->reservation?->bien;

            if ($bien) {
                if (!$vente->ancien_proprietaire_id) {
                    DB::table('ventes')
                        ->where('id', $vente->id)
                        ->update(['ancien_proprietaire_id' => $bien->proprietaire_id]);
                }

                DB::table('biens')
                    ->where('id', $bien->id)
                    ->update([
                        'proprietaire_id' => $vente->acheteur_id,
                        'status' => 'vendu',
                        'updated_at' => now()
                    ]);

                DB::table('ventes')
                    ->where('id', $vente->id)
                    ->update([
                        'property_transferred' => true,
                        'property_transferred_at' => now()
                    ]);

                if ($bien->mandat) {
                    DB::table('mandats')
                        ->where('id', $bien->mandat->id)
                        ->update([
                            'statut' => 'termine',
                            'date_fin' => now(),
                            'updated_at' => now()
                        ]);
                }
            }

            if ($vente->reservation) {
                DB::table('reservations')
                    ->where('id', $vente->reservation->id)
                    ->update([
                        'statut' => 'confirmee',
                        'updated_at' => now()
                    ]);
            }
        });

        Log::info('✅ Vente complétée', ['vente_id' => $vente->id]);
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
        $location = Location::find($paiement->location_id);
        if (!$location) return;

        DB::transaction(function () use ($location) {
            DB::table('locations')
                ->where('id', $location->id)
                ->update([
                    'statut' => 'active',
                    'updated_at' => now()
                ]);

            try {
                $this->contractPdfService->generatePdf($location, 'location');
            } catch (\Exception $e) {
                Log::error('⚠️ Erreur PDF location', ['error' => $e->getMessage()]);
            }

            $location->load('reservation.bien');
            if ($location->reservation && $location->reservation->bien) {
                DB::table('biens')
                    ->where('id', $location->reservation->bien->id)
                    ->update([
                        'status' => 'loue',
                        'updated_at' => now()
                    ]);
            }

            if ($location->reservation) {
                DB::table('reservations')
                    ->where('id', $location->reservation->id)
                    ->update([
                        'statut' => 'confirmee',
                        'updated_at' => now()
                    ]);
            }
        });

        Log::info('✅ Location activée');
    }

}
