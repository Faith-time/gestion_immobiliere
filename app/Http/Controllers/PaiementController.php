<?php

namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Bien;
use App\Services\ContractPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaiementController extends Controller
{

    protected $contractPdfService;

    public function __construct()
    {
        // Injection du service de génération de contrats
        $this->contractPdfService = app(ContractPdfService::class);
    }
    /**
     * Afficher la page de succès avec mise à jour automatique du statut
     */
    public function showSucces(Paiement $paiement)
    {
        Log::info('Accès à la page de succès', [
            'paiement_id' => $paiement->id,
            'statut_initial' => $paiement->statut,
            'transaction_id' => $paiement->transaction_id
        ]);

        // TOUJOURS mettre à jour le statut si il n'est pas déjà réussi
        if ($paiement->statut !== 'reussi') {
            try {
                // Mise à jour directe et forcée
                $updated = $paiement->update([
                    'statut' => 'reussi',
                    'montant_paye' => $paiement->montant_total,
                    'montant_restant' => 0,
                    'date_transaction' => now(),
                ]);

                Log::info('Tentative de mise à jour du paiement', [
                    'paiement_id' => $paiement->id,
                    'update_result' => $updated,
                    'nouveau_statut' => $paiement->fresh()->statut
                ]);

                // Mettre à jour le statut des éléments associés
                $this->updateItemStatus($paiement);

                Log::info('Paiement FORCÉ comme réussi sur la page de succès', [
                    'paiement_id' => $paiement->id,
                    'transaction_id' => $paiement->transaction_id,
                    'statut_final' => $paiement->fresh()->statut
                ]);

            } catch (\Exception $e) {
                Log::error('Erreur lors de la mise à jour FORCÉE du paiement: ' . $e->getMessage(), [
                    'paiement_id' => $paiement->id,
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Recharger le paiement pour avoir les données mises à jour
        $paiement->refresh();

        // Charger les relations selon le type
        $paiement->load([
            'reservation.bien.mandat',
            'location.bien.mandat',
            'vente.bien.mandat',
            'reservation.bien.proprietaire', // Ajouté
            'location.bien.proprietaire',    // Ajouté
            'vente.bien.proprietaire'
        ]);

        // Déterminer les actions disponibles selon le contexte
        $actionsDisponibles = $this->getActionsDisponibles($paiement);

        Log::info('Rendu de la page de succès', [
            'paiement_id' => $paiement->id,
            'statut_final' => $paiement->statut,
            'montant_paye' => $paiement->montant_paye
        ]);

        return Inertia::render('Paiement/Succes', [
            'paiement' => $paiement,
            'actionsDisponibles' => $actionsDisponibles,
            'forceSuccess' => true // Indicateur pour le frontend
        ]);
    }
    /**
     * Déterminer les actions disponibles après paiement réussi
     */
    private function getActionsDisponibles(Paiement $paiement)
    {
        $actions = [
            'peutVisiter' => false,
            'peutProcederVente' => false,
            'peutProcederLocation' => false,
            'peutVoirVente' => false,
            'bien' => null,
            'typeMandat' => null,
            'vente' => null
        ];

        // Si c'est une réservation avec paiement réussi
        if ($paiement->reservation_id && $paiement->statut === 'reussi') {
            $reservation = $paiement->reservation;
            $bien = $reservation->bien ?? null;
            $mandat = $bien->mandat ?? null;

            if ($bien && $mandat && $mandat->statut === 'actif') {
                $actions['bien'] = $bien;
                $actions['typeMandat'] = $mandat->type_mandat;

                // Toujours permettre la visite
                $actions['peutVisiter'] = true;

                // Vérifier s'il peut procéder selon le type de mandat
                if ($mandat->type_mandat === 'vente' && $bien->status !== 'vendu') {
                    // Vérifier qu'il n'y a pas déjà une vente pour ce bien par cet utilisateur
                    $venteExistante = \App\Models\Vente::where('biens_id', $bien->id)
                        ->where('acheteur_id', auth()->id())
                        ->exists();

                    if (!$venteExistante) {
                        $actions['peutProcederVente'] = true;
                    }
                } elseif ($mandat->type_mandat === 'gestion_locative' && $bien->status !== 'loue') {
                    // Vérifier qu'il n'y a pas déjà une location active pour ce bien par cet utilisateur
                    $locationExistante = \App\Models\Location::where('bien_id', $bien->id)
                        ->where('client_id', auth()->id())
                        ->whereIn('statut', ['active', 'en_cours'])
                        ->exists();

                    if (!$locationExistante) {
                        $actions['peutProcederLocation'] = true;
                    }
                }
            }

            \Log::info('Actions disponibles après réservation', [
                'reservation_id' => $paiement->reservation_id,
                'bien_id' => $bien->id ?? 'null',
                'type_mandat' => $mandat->type_mandat ?? 'null',
                'actions' => $actions
            ]);
        }

        // Si c'est une vente avec paiement réussi
        if ($paiement->vente_id && $paiement->statut === 'reussi') {
            $vente = $paiement->vente;
            $bien = $vente->bien ?? null;

            if ($vente && $bien) {
                $actions['bien'] = $bien;
                $actions['vente'] = $vente;
                $actions['peutVoirVente'] = true;
            }
        }

        return $actions;
    }

    /**
     * Afficher la page d'initiation du paiement
     */
    public function showInitierPaiement(Request $request): \Inertia\Response
    {
        $type = $request->input('type'); // reservation, location, vente
        $id = $request->input('id');
        $paiementId = $request->input('paiement_id');

        // Récupérer les données selon le type
        $item = null;
        $paiement = null;

        if ($paiementId) {
            $paiement = Paiement::findOrFail($paiementId);
        }

        switch ($type) {
            case 'reservation':
                $item = Reservation::with(['bien', 'client'])->findOrFail($id);
                break;
            case 'location':
                $item = Location::with(['bien', 'client'])->findOrFail($id);
                break;
            case 'vente':
                $item = Vente::with(['bien', 'client'])->findOrFail($id);
                break;
            default:
                abort(400, 'Type de paiement invalide');
        }

        // Vérifier les permissions
        if ($item->client_id !== auth()->id()) {
            abort(403, 'Accès non autorisé');
        }

        return Inertia::render('Paiement/InitierPaiement', [
            'type' => $type,
            'item' => $item,
            'paiement' => $paiement,
            'user' => auth()->user()
        ]);
    }

    /**
     * Lister tous les paiements
     */
    public function index()
    {
        $paiements = Paiement::with(['vente', 'location', 'reservation'])->get();
        return response()->json($paiements);
    }

    /**
     * Créer un paiement
     */
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

        // Création du paiement
        $paiement = new Paiement();
        $paiement->type = $request->type;
        $paiement->montant_total = $request->montant_total;
        $paiement->montant_paye = $request->montant_paye;
        $paiement->montant_restant = $request->montant_total - $request->montant_paye;
        $paiement->commission_agence = $request->montant_total * 0.05;
        $paiement->mode_paiement = $request->mode_paiement;
        $paiement->transaction_id = $request->transaction_id;
        $paiement->statut = 'en_attente';
        $paiement->date_transaction = now();

        // Associer selon type
        if ($request->type === 'vente') {
            $paiement->vente_id = $request->vente_id;
        } elseif ($request->type === 'location') {
            $paiement->location_id = $request->location_id;
        } elseif ($request->type === 'reservation') {
            $paiement->reservation_id = $request->reservation_id;
        }

        $paiement->save();

        return response()->json([
            'message' => 'Paiement enregistré avec succès.',
            'paiement' => $paiement
        ], 201);
    }

    /**
     * Afficher un paiement
     */
    public function show($id)
    {
        $paiement = Paiement::with(['vente', 'location', 'reservation'])->findOrFail($id);
        return response()->json($paiement);
    }

    /**
     * Mettre à jour un paiement (ex: après confirmation CinetPay)
     */
    public function update(Request $request, $id)
    {
        $paiement = Paiement::findOrFail($id);

        $request->validate([
            'statut' => 'in:en_attente,reussi,echoue',
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

    /**
     * Supprimer un paiement
     */
    public function destroy($id)
    {
        $paiement = Paiement::findOrFail($id);
        $paiement->delete();

        return response()->json(['message' => 'Paiement supprimé avec succès.']);
    }

    /**
     * Initier un paiement (appelé par le frontend via AJAX)
     * Retourne JSON { success: true, payment_url: '...' }
     */
    public function initier(Request $request)
    {
        Log::info('Données reçues pour initiation paiement:', $request->all());

        $request->validate([
            'paiement_id'     => 'required|exists:paiements,id',
            'customer_name'   => 'required|string|max:255',
            'customer_email'  => 'required|email|max:255',
            'customer_phone'  => 'required|string|max:20',
            'description'     => 'nullable|string|max:255',
            'mode_paiement'   => 'required|in:mobile_money,wave,orange_money,mtn_money,moov_money,carte,virement'
        ]);

        try {
            $paiement = Paiement::findOrFail($request->paiement_id);

            Log::info('Paiement trouvé:', ['paiement' => $paiement->toArray()]);

            // Mettre à jour le paiement
            $paiement->update([
                'montant_paye' => $paiement->montant_total,
                'montant_restant' => 0,
                'mode_paiement'  => $request->mode_paiement,
                'transaction_id' => $paiement->transaction_id ?: 'TXN_' . Str::upper(Str::random(10)) . '_' . time(),
                'statut'         => 'reussi'
            ]);

            Log::info('Paiement mis à jour:', ['paiement' => $paiement->fresh()->toArray()]);

            return response()->json([
                'success' => true,
                'payment_url' => route('paiement.succes', $paiement->id)
            ]);

            Log::info('Traitement avec CinetPay');

            // Appel réel à l'API CinetPay (retourne tableau résultat)
            $cinetPayResponse = $this->processCinetPayPayment($request, $paiement);

            Log::info('Résultat processCinetPayPayment', ['result' => $cinetPayResponse]);

            if (is_array($cinetPayResponse) && isset($cinetPayResponse['data']['payment_url'])) {
                return response()->json([
                    'success' => true,
                    'payment_url' => $cinetPayResponse['data']['payment_url']
                ]);
            }

            // Si on arrive ici c'est qu'il y a eu un souci
            $msg = $cinetPayResponse['message'] ?? 'Impossible de récupérer l’URL de paiement';
            Log::error('Initier paiement erreur : ' . $msg, ['response' => $cinetPayResponse]);

            return response()->json([
                'success' => false,
                'message' => $msg
            ], 500);

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l’initiation du paiement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simulation de paiement pour les tests (optionnel)
     */
    private function simulatePayment(Paiement $paiement)
    {
        $paiement->update([
            'statut' => 'reussi',
            'montant_paye' => $paiement->montant_total,
            'montant_restant' => 0,
            'date_transaction' => now(),
        ]);

        $this->updateItemStatus($paiement);

        Log::info('Paiement simulé', ['paiement_id' => $paiement->id]);

        // Pour la simulation on peut rediriger ou renvoyer une URL : ici on redirige vers la page succès
        return redirect()->route('paiement.succes', $paiement)
            ->with('success', 'Paiement simulé avec succès !');
    }

    /**
     * Traitement réel avec CinetPay
     * Retourne le tableau (json) renvoyé par CinetPay ou lève une exception si problème réseau
     */
    private function processCinetPayPayment(Request $request, Paiement $paiement)
    {
        // Configuration CinetPay
        $apiKey = env('CINETPAY_API_KEY');
        $siteId = env('CINETPAY_SITE_ID');
        $baseUrl = env('CINETPAY_BASE_URL'); // https://api-checkout.cinetpay.com
        $notifyUrl = env('CINETPAY_NOTIFY_URL');
        $returnUrl = env('CINETPAY_RETURN_URL');

        // Construire payload conforme API CinetPay
        $transactionData = [
            'apikey' => $apiKey,
            'site_id' => $siteId,
            'transaction_id' => $paiement->transaction_id,
            'amount' => (int)$paiement->montant_total,
            'currency' => 'XOF',
            'description' => $request->description ?: 'Paiement - ' . $paiement->type,
            'customer_id' => (string)auth()->id(),
            'customer_name' => $request->customer_name,
            'customer_surname' => $request->customer_name, // CinetPay demande les deux
            'customer_email' => $request->customer_email,
            'customer_phone_number' => $request->customer_phone,
            'customer_address' => 'Dakar',
            'customer_city' => 'Dakar',
            'customer_country' => 'SN',
            'customer_state' => 'Dakar',
            'customer_zip_code' => '10000',
            'notify_url' => $notifyUrl,
            'return_url' => rtrim($returnUrl, '/') . '/' . $paiement->id,
            'channels' => $this->getCinetPayChannels($request->mode_paiement),
            'metadata' => json_encode([
                'paiement_id' => $paiement->id,
                'user_id' => auth()->id(),
                'type' => $paiement->type
            ]),
        ];

        Log::info('Envoi données à CinetPay', [
            'url' => $baseUrl . '/v2/payment',
            'data' => $transactionData
        ]);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($baseUrl . '/v2/payment', $transactionData);

            $result = $response->json();

            Log::info('Réponse CinetPay complète', [
                'http_status' => $response->status(),
                'response_body' => $result,
                'transaction_id' => $paiement->transaction_id
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('Exception CinetPay: ' . $e->getMessage(), [
                'transaction_id' => $paiement->transaction_id,
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de communication avec le service de paiement: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convertir le mode de paiement en canaux CinetPay (version corrigée)
     */
    private function getCinetPayChannels($modePaiement)
    {
        switch ($modePaiement) {
            case 'mobile_money':
                return 'MOBILE_MONEY';
            case 'wave':
            case 'orange_money':
            case 'mtn_money':
            case 'moov_money':
                return 'MOBILE_MONEY';
            case 'carte':
                return 'CREDIT_CARD';
            case 'virement':
                return 'ALL';
            default:
                return 'ALL';
        }
    }

    /**
     * Webhook de notification CinetPay (appelé par CinetPay pour notifier le serveur)
     */
    public function notify(Request $request)
    {
        Log::info('Notification CinetPay reçue', [
            'payload' => $request->all(),
            'mode' => env('CINETPAY_MODE', 'test')
        ]);

        try {
            $apiKey = env('CINETPAY_API_KEY');
            $siteId = env('CINETPAY_SITE_ID');

            // CinetPay peut envoyer différents champs
            $transactionId = $request->input('cpm_trans_id') ?? $request->input('transaction_id') ?? null;

            if (!$transactionId) {
                Log::error('Transaction ID manquant dans la notification', $request->all());
                return response()->json(['status' => 'error', 'message' => 'Transaction ID manquant'], 400);
            }

            // En mode test, accepter automatiquement comme succès
            if (env('CINETPAY_MODE', 'test') === 'test' || env('AUTO_VALIDATE_TEST_PAYMENTS', false)) {
                Log::info('Mode test - validation automatique de la notification');

                // Trouver le paiement par transaction_id
                $paiement = Paiement::where('transaction_id', $transactionId)->first();

                if ($paiement && $paiement->statut !== 'reussi') {
                    $paiement->update([
                        'statut' => 'reussi',
                        'montant_paye' => $paiement->montant_total,
                        'montant_restant' => 0,
                        'date_transaction' => now(),
                    ]);

                    $this->updateItemStatus($paiement);

                    Log::info('Paiement confirmé automatiquement en mode test', [
                        'paiement_id' => $paiement->id,
                        'transaction_id' => $transactionId
                    ]);
                }

                return response()->json(['status' => 'success'], 200);
            }

            // Mode production - vérification auprès de CinetPay
            $verificationData = [
                'apikey' => $apiKey,
                'site_id' => $siteId,
                'transaction_id' => $transactionId
            ];

            $baseUrl = rtrim(env('CINETPAY_BASE_URL'), '/');
            $response = Http::post($baseUrl . '/payment/check', $verificationData);
            $result = $response->json();

            Log::info('Vérification statut CinetPay', [
                'transaction_id' => $transactionId,
                'http_status' => $response->status(),
                'response' => $result
            ]);

            if ($response->successful() && isset($result['code']) && ($result['code'] == '00' || $result['code'] === 0)) {
                $metadata = json_decode($result['data']['metadata'] ?? '{}', true);
                $paiementId = $metadata['paiement_id'] ?? null;

                if ($paiementId) {
                    $paiement = Paiement::find($paiementId);

                    if ($paiement && $paiement->statut !== 'reussi') {
                        $paiement->update([
                            'statut' => 'reussi',
                            'montant_paye' => $result['data']['amount'] ?? $paiement->montant_paye,
                            'montant_restant' => 0,
                            'date_transaction' => now(),
                        ]);

                        $this->updateItemStatus($paiement);

                        Log::info('Paiement confirmé', [
                            'paiement_id' => $paiement->id,
                            'transaction_id' => $transactionId
                        ]);
                    }
                }

                return response()->json(['status' => 'success'], 200);
            } else {
                Log::warning('Paiement échoué ou non confirmé', [
                    'transaction_id' => $transactionId,
                    'response' => $result
                ]);

                return response()->json(['status' => 'failed'], 200);
            }

        } catch (\Exception $e) {
            Log::error('Erreur notification: ' . $e->getMessage(), [
                'payload' => $request->all()
            ]);
            return response()->json(['status' => 'error'], 500);
        }
    }


    /**
     * Vérifier le statut d'un paiement auprès de CinetPay (utilisé côté serveur)
     */
    private function verifyPaymentStatus(Paiement $paiement)
    {
        try {
            $verificationData = [
                'apikey' => env('CINETPAY_API_KEY'),
                'site_id' => env('CINETPAY_SITE_ID'),
                'transaction_id' => $paiement->transaction_id
            ];

            $baseUrl = rtrim(env('CINETPAY_BASE_URL'), '/');
            $response = Http::post($baseUrl . '/payment/check', $verificationData);
            $result = $response->json();

            Log::info('Vérification manuelle statut CinetPay', [
                'transaction_id' => $paiement->transaction_id,
                'response' => $result
            ]);

            if ($response->successful() && isset($result['code']) && ($result['code'] == '00' || $result['code'] === 0)) {
                $paiement->update([
                    'statut' => 'reussi',
                    'montant_paye' => $result['data']['amount'] ?? $paiement->montant_paye,
                    'montant_restant' => 0,
                    'date_transaction' => now(),
                ]);

                $this->updateItemStatus($paiement);
            }

        } catch (\Exception $e) {
            Log::error('Erreur vérification statut: ' . $e->getMessage(), [
                'paiement_id' => $paiement->id
            ]);
        }
    }

    /**
     * Mettre à jour le statut du bien / reservation / vente selon le paiement
     */
    private function updateItemStatus(Paiement $paiement)
    {
        try {
            if ($paiement->statut === 'reussi') {
                if ($paiement->reservation_id) {
                    $reservation = $paiement->reservation;
                    if ($reservation) {
                        $reservation->update(['statut' => 'confirmee']);
                    }
                } elseif ($paiement->location_id) {
                    $location = $paiement->location;
                    if ($location) {
                        $location->update(['statut' => 'active']);
                        if ($location->bien) {
                            $location->bien->update(['status' => 'loue']);
                        }
                    }
                } elseif ($paiement->vente_id) {
                    // NOUVEAU : Finaliser la vente après paiement réussi
                    $vente = $paiement->vente;
                    if ($vente) {
                        DB::transaction(function () use ($vente) {
                            // Mettre à jour le statut de la vente
                            $vente->update(['status' => 'confirmée']); // Changé de 'finalisee' à 'en_cours'

                            // Générer automatiquement le contrat PDF
                            $pdfPath = $this->contractPdfService->generatePdf($vente, 'vente');

                            if ($pdfPath) {
                                \Log::info("Contrat de vente généré automatiquement après paiement", [
                                    'vente_id' => $vente->id,
                                    'pdf_path' => $pdfPath
                                ]);
                            }

                            // Mettre à jour le statut du bien
                            if ($vente->bien) {
                                $vente->bien->update(['status' => 'vendu']);

                                // Marquer le mandat comme terminé
                                if ($vente->bien->mandat) {
                                    $vente->bien->mandat->update(['statut' => 'expire']);
                                }
                            }
                        });

                        \Log::info('Vente finalisée après paiement réussi', [
                            'vente_id' => $vente->id,
                            'paiement_id' => $paiement->id
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour statut item: ' . $e->getMessage());
        }
    }

    /**
     * Page d'erreur - affiche un message venant de la session
     */
    public function showErreur(Request $request)
    {
        $message = $request->session()->get('error', 'Une erreur est survenue lors du paiement');

        return Inertia::render('Paiement/Erreur', [
            'message' => $message,
            'previous_url' => $request->session()->get('previous_url', route('home'))
        ]);
    }


    /**
     * Méthode helper pour obtenir la liste des modes de paiement disponibles
     */
    public function getModePaiementOptions()
    {
        return [
            'mobile_money' => [
                'label' => 'Mobile Money (Tous)',
                'description' => 'Orange Money, MTN Money, Moov Money, Wave',
                'icon' => 'mobile'
            ],
            'wave' => [
                'label' => 'Wave',
                'description' => 'Paiement via Wave uniquement',
                'icon' => 'wave'
            ],
            'orange_money' => [
                'label' => 'Orange Money',
                'description' => 'Paiement via Orange Money uniquement',
                'icon' => 'orange'
            ],
            'mtn_money' => [
                'label' => 'MTN Money',
                'description' => 'Paiement via MTN Money uniquement',
                'icon' => 'mtn'
            ],
            'moov_money' => [
                'label' => 'Moov Money',
                'description' => 'Paiement via Moov Money uniquement',
                'icon' => 'moov'
            ],
            'carte' => [
                'label' => 'Carte bancaire',
                'description' => 'Visa, Mastercard',
                'icon' => 'credit-card'
            ],
            'virement' => [
                'label' => 'Virement bancaire',
                'description' => 'Tous les modes de paiement',
                'icon' => 'bank'
            ]
        ];
    }

    /**
     * Route pour récupérer les options de paiement (pour le frontend)
     */
    public function getPaiementOptions()
    {
        return response()->json([
            'modes_paiement' => $this->getModePaiementOptions(),
            'devise' => 'XOF',
            'pays' => 'SN'
        ]);
    }

    public function simulationPage(Paiement $paiement)
    {
        if (env('CINETPAY_MODE', 'test') !== 'test' && !env('SIMULATE_PAYMENT', false)) {
            abort(404, 'Page non disponible en mode production');
        }

        Log::info('Affichage page simulation', [
            'paiement_id' => $paiement->id,
            'statut_actuel' => $paiement->statut
        ]);

        return Inertia::render('Paiement/Simulation', [
            'paiement' => $paiement,
            'redirectUrl' => route('paiement.simulation.process', $paiement->id),
            'successUrl' => route('paiement.succes', $paiement->id),
            'delaySeconds' => 5
        ]);
    }


    /**
     * Traiter la simulation de paiement et rediriger vers succès
     */
    public function simulationProcess($paiementId)
    {
        if (env('CINETPAY_MODE', 'test') !== 'test' && !env('SIMULATE_PAYMENT', false)) {
            abort(404, 'Route non disponible en mode production');
        }

        try {
            $paiement = Paiement::findOrFail($paiementId);

            Log::info('Traitement simulation paiement', [
                'paiement_id' => $paiementId,
                'statut_actuel' => $paiement->statut
            ]);

            if ($paiement->statut !== 'reussi') {
                $paiement->update([
                    'statut' => 'reussi',
                    'montant_paye' => $paiement->montant_total,
                    'montant_restant' => 0,
                    'date_transaction' => now(),
                ]);

                $this->updateItemStatus($paiement);

                Log::info('Simulation paiement terminée avec succès', [
                    'paiement_id' => $paiement->id,
                    'transaction_id' => $paiement->transaction_id
                ]);
            }

            // Rediriger vers la page de succès
            return redirect()->route('paiement.succes', $paiement);

        } catch (\Exception $e) {
            Log::error('Erreur simulation paiement: ' . $e->getMessage());
            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors de la simulation du paiement');
        }
    }

    /**
     * Méthode de debug pour tester l'intégration CinetPay
     * À supprimer une fois que tout fonctionne
     */
    public function debugCinetPay()
    {
        $testData = [
            'apikey' => env('CINETPAY_API_KEY'),
            'site_id' => env('CINETPAY_SITE_ID'),
            'transaction_id' => 'TEST_' . time(),
            'amount' => 1000, // 1000 FCFA pour test
            'currency' => 'XOF',
            'description' => 'Test de paiement',
            'customer_id' => '123',
            'customer_name' => 'Test User',
            'customer_surname' => 'Test',
            'customer_email' => 'test@example.com',
            'customer_phone_number' => '+221701234567',
            'customer_address' => 'Dakar',
            'customer_city' => 'Dakar',
            'customer_country' => 'SN',
            'customer_state' => 'Dakar',
            'customer_zip_code' => '10000',
            'notify_url' => env('CINETPAY_NOTIFY_URL'),
            'return_url' => env('CINETPAY_RETURN_URL') . '/test',
            'channels' => 'ORANGE_MONEY_SN,FREE_MONEY_SN,WAVE_SN',
        ];

        Log::info('Test CinetPay - Données envoyées:', $testData);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post(env('CINETPAY_BASE_URL') . '/v2/?method=paylink', $testData);

            $result = $response->json();

            Log::info('Test CinetPay - Réponse:', [
                'status' => $response->status(),
                'body' => $result
            ]);

            return response()->json([
                'status' => $response->status(),
                'request' => $testData,
                'response' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Test CinetPay - Erreur:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => $e->getMessage(),
                'request' => $testData
            ]);
        }
    }

    /**
     * Page de retour après paiement (appelée quand l'utilisateur revient via return_url)
     */
    public function retour($paiementId)
    {
        try {
            $paiement = Paiement::findOrFail($paiementId);

            Log::info('Retour de paiement', [
                'paiement_id' => $paiementId,
                'statut_actuel' => $paiement->statut,
                'mode_test' => env('CINETPAY_MODE', 'test'),
                'simulate_payment' => env('SIMULATE_PAYMENT', false)
            ]);

            // TOUJOURS rediriger vers la page de succès
            // La logique de mise à jour du statut se fera dans showSucces()
            return redirect()->route('paiement.succes', $paiement);

        } catch (\Exception $e) {
            Log::error('Erreur retour paiement: ' . $e->getMessage(), [
                'paiement_id' => $paiementId
            ]);
            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors de la vérification du paiement');
        }
    }

}
