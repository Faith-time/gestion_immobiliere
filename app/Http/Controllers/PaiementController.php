<?php
namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaiementController extends Controller
{
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
            'type'            => 'required|in:vente,location,reservation',
            'montant_total'   => 'required|numeric|min:0',
            'montant_paye'    => 'required|numeric|min:0',
            'mode_paiement'   => 'required|in:carte,mobile_money,virement',
            'transaction_id'  => 'nullable|string|max:255',
            'vente_id'        => 'nullable|exists:ventes,id',
            'location_id'     => 'nullable|exists:locations,id',
            'reservation_id'  => 'nullable|exists:reservations,id',
        ]);

        // Création du paiement
        $paiement = new Paiement();
        $paiement->type             = $request->type;
        $paiement->montant_total    = $request->montant_total;
        $paiement->montant_paye     = $request->montant_paye;
        $paiement->montant_restant  = $request->montant_total - $request->montant_paye;
        $paiement->commission_agence = $request->montant_total * 0.05;
        $paiement->mode_paiement    = $request->mode_paiement;
        $paiement->transaction_id   = $request->transaction_id;
        $paiement->statut           = 'en_attente';
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
            'statut' => 'in:en_attente,termine,echoue',
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
     * Initier un paiement avec CinetPay
     */
    public function initier(Request $request)
    {
        $request->validate([
            'paiement_id' => 'required|exists:paiements,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $paiement = Paiement::findOrFail($request->paiement_id);

            // Générer un transaction_id unique si pas déjà défini
            if (!$paiement->transaction_id) {
                $paiement->transaction_id = 'TXN_' . Str::upper(Str::random(10)) . '_' . time();
                $paiement->save();
            }

            // Configuration CinetPay
            $apiKey = env('CINETPAY_API_KEY');
            $siteId = env('CINETPAY_SITE_ID');
            $secretKey = env('CINETPAY_SECRET_KEY');

            // URLs de callback
            $notifyUrl = route('paiement.notify');
            $returnUrl = route('paiement.retour', ['paiement_id' => $paiement->id]);

            // Données pour CinetPay
            $data = [
                'amount' => (int)$paiement->montant_total,
                'currency' => 'XOF', // ou votre devise
                'apikey' => $apiKey,
                'site_id' => $siteId,
                'transaction_id' => $paiement->transaction_id,
                'description' => $request->description ?: "Paiement {$paiement->type} #{$paiement->id}",
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
                'customer_name' => $request->customer_name,
                'customer_surname' => '', // optionnel
                'customer_email' => $request->customer_email,
                'customer_phone_number' => $request->customer_phone,
                'customer_address' => '', // optionnel
                'customer_city' => '', // optionnel
                'customer_country' => 'SN', // ou votre pays
                'customer_state' => '', // optionnel
                'customer_zip_code' => '', // optionnel
            ];

            // Appel API CinetPay pour initier le paiement
            $response = Http::timeout(30)->post('https://api-checkout.cinetpay.com/v2/payment', $data);

            if ($response->successful()) {
                $responseData = $response->json();

                if (isset($responseData['data']) && isset($responseData['data']['payment_url'])) {
                    // Mettre à jour le statut du paiement
                    $paiement->statut = 'en_attente';
                    $paiement->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'Paiement initié avec succès',
                        'payment_url' => $responseData['data']['payment_url'],
                        'transaction_id' => $paiement->transaction_id,
                        'paiement_id' => $paiement->id
                    ]);
                }
            }

            Log::error('Erreur CinetPay initiation', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'initiation du paiement'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur interne lors de l\'initiation du paiement'
            ], 500);
        }
    }

    /**
     * Notification de CinetPay (webhook)
     */
    public function notify(Request $request)
    {
        try {
            $transactionId = $request->input('cpm_trans_id');
            $amount = $request->input('cpm_amount');
            $currency = $request->input('cpm_currency');
            $signature = $request->input('signature');

            // Vérifier la signature
            $apiKey = env('CINETPAY_API_KEY');
            $siteId = env('CINETPAY_SITE_ID');

            $expectedSignature = hash('sha256', $transactionId . $amount . $currency . $siteId . $apiKey);

            if ($signature !== $expectedSignature) {
                Log::warning('Signature invalide pour la notification CinetPay', [
                    'transaction_id' => $transactionId,
                    'received_signature' => $signature,
                    'expected_signature' => $expectedSignature
                ]);
                return response('Signature invalide', 400);
            }

            // Vérifier le statut du paiement auprès de CinetPay
            $verifyData = [
                'apikey' => $apiKey,
                'site_id' => $siteId,
                'transaction_id' => $transactionId
            ];

            $verifyResponse = Http::post('https://api-checkout.cinetpay.com/v2/payment/check', $verifyData);

            if ($verifyResponse->successful()) {
                $verifyResult = $verifyResponse->json();

                if (isset($verifyResult['data'])) {
                    $paymentData = $verifyResult['data'];

                    // Trouver le paiement dans la base
                    $paiement = Paiement::where('transaction_id', $transactionId)->first();

                    if ($paiement) {
                        // Mettre à jour le statut selon la réponse CinetPay
                        switch ($paymentData['cpm_result']) {
                            case '00': // Succès
                                $paiement->statut = 'termine';
                                $paiement->montant_paye = $paymentData['cpm_amount'];
                                $paiement->montant_restant = $paiement->montant_total - $paiement->montant_paye;
                                break;
                            case '01': // Échec
                                $paiement->statut = 'echoue';
                                break;
                            default:
                                $paiement->statut = 'en_attente';
                        }

                        $paiement->save();

                        Log::info('Paiement mis à jour via notification CinetPay', [
                            'paiement_id' => $paiement->id,
                            'transaction_id' => $transactionId,
                            'statut' => $paiement->statut
                        ]);
                    }
                }
            }

            return response('OK', 200);

        } catch (\Exception $e) {
            Log::error('Erreur notification CinetPay: ' . $e->getMessage());
            return response('Erreur', 500);
        }
    }

    /**
     * Page de retour après paiement
     */
    public function retour(Request $request)
    {
        try {
            $paiementId = $request->route('paiement_id');
            $transactionId = $request->input('transaction_id');
            $token = $request->input('token');

            $paiement = Paiement::findOrFail($paiementId);

            // Vérifier le statut final auprès de CinetPay
            $apiKey = env('CINETPAY_API_KEY');
            $siteId = env('CINETPAY_SITE_ID');

            $verifyData = [
                'apikey' => $apiKey,
                'site_id' => $siteId,
                'transaction_id' => $paiement->transaction_id
            ];

            $verifyResponse = Http::post('https://api-checkout.cinetpay.com/v2/payment/check', $verifyData);

            $message = '';
            $success = false;

            if ($verifyResponse->successful()) {
                $verifyResult = $verifyResponse->json();

                if (isset($verifyResult['data'])) {
                    $paymentData = $verifyResult['data'];

                    switch ($paymentData['cpm_result']) {
                        case '00':
                            $paiement->statut = 'termine';
                            $paiement->montant_paye = $paymentData['cpm_amount'];
                            $paiement->montant_restant = $paiement->montant_total - $paiement->montant_paye;
                            $message = 'Paiement effectué avec succès !';
                            $success = true;
                            break;
                        case '01':
                            $paiement->statut = 'echoue';
                            $message = 'Le paiement a échoué. Veuillez réessayer.';
                            break;
                        default:
                            $message = 'Paiement en cours de traitement...';
                    }

                    $paiement->save();
                }
            } else {
                $message = 'Erreur lors de la vérification du paiement.';
            }

            // Si c'est une requête API, retourner JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => $success,
                    'message' => $message,
                    'paiement' => $paiement
                ]);
            }

            // Sinon, rediriger vers une vue ou une URL
            return redirect()->to(env('FRONTEND_URL', '/') . '/paiement/resultat')
                ->with('success', $success)
                ->with('message', $message)
                ->with('paiement', $paiement);

        } catch (\Exception $e) {
            Log::error('Erreur retour paiement: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur lors du traitement du retour de paiement'
                ], 500);
            }

            return redirect()->to(env('FRONTEND_URL', '/') . '/paiement/erreur')
                ->with('error', 'Erreur lors du traitement du paiement');
        }
    }
}
