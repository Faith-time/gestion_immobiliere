<?php
namespace App\Http\Controllers;

use App\Models\Paiement;
use App\Models\Vente;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\Bien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PaiementController extends Controller
{
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
     * Afficher la page de succès
     */
    public function showSucces(Paiement $paiement)
    {
        // Charger les relations selon le type
        $paiement->load([
            'reservation.bien',
            'location.bien',
            'vente.bien'
        ]);

        return Inertia::render('Paiement/Succes', [
            'paiement' => $paiement
        ]);
    }

    /**
     * Afficher la page d'erreur
     */
    public function showErreur(Request $request)
    {
        $message = $request->session()->get('error', 'Une erreur est survenue lors du paiement');

        return Inertia::render('Paiement/Erreur', [
            'message' => $message
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
            'mode_paiement' => 'required|in:mobile_money,carte,virement'
        ]);

        try {
            $paiement = Paiement::findOrFail($request->paiement_id);

            // Mettre à jour le mode de paiement
            $paiement->mode_paiement = $request->mode_paiement;

            // Générer un transaction_id unique si pas déjà défini
            if (!$paiement->transaction_id) {
                $paiement->transaction_id = 'TXN_' . Str::upper(Str::random(10)) . '_' . time();
            }

            $paiement->save();

            // Configuration CinetPay
            $apiKey = env('CINETPAY_API_KEY');
            $siteId = env('CINETPAY_SITE_ID');
            $secretKey = env('CINETPAY_SECRET_KEY');

            // URLs de callback
            $notifyUrl = env('CINETPAY_NOTIFY_URL');
            $returnUrl = env('CINETPAY_RETURN_URL') . '/' . $paiement->id;

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

                    // Rediriger vers CinetPay
                    return redirect()->away($responseData['data']['payment_url']);
                }
            }

            Log::error('Erreur CinetPay initiation', [
                'response' => $response->body(),
                'status' => $response->status()
            ]);

            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors de l\'initiation du paiement');

        } catch (\Exception $e) {
            Log::error('Erreur initiation paiement: ' . $e->getMessage());

            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur interne lors de l\'initiation du paiement');
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

                                // Mettre à jour le statut de l'élément associé
                                $this->updateItemStatus($paiement, 'confirmee');
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

            if ($verifyResponse->successful()) {
                $verifyResult = $verifyResponse->json();

                if (isset($verifyResult['data'])) {
                    $paymentData = $verifyResult['data'];

                    switch ($paymentData['cpm_result']) {
                        case '00':
                            $paiement->statut = 'termine';
                            $paiement->montant_paye = $paymentData['cpm_amount'];
                            $paiement->montant_restant = $paiement->montant_total - $paiement->montant_paye;

                            // Mettre à jour le statut de l'élément associé
                            $this->updateItemStatus($paiement, 'confirmee');

                            $paiement->save();

                            return redirect()->route('paiement.succes', $paiement);

                        case '01':
                            $paiement->statut = 'echoue';
                            $paiement->save();

                            return redirect()->route('paiement.erreur')
                                ->with('error', 'Le paiement a échoué. Veuillez réessayer.');

                        default:
                            return redirect()->route('paiement.erreur')
                                ->with('error', 'Paiement en cours de traitement...');
                    }
                }
            }

            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors de la vérification du paiement.');

        } catch (\Exception $e) {
            Log::error('Erreur retour paiement: ' . $e->getMessage());

            return redirect()->route('paiement.erreur')
                ->with('error', 'Erreur lors du traitement du retour de paiement');
        }
    }

    /**
     * Mettre à jour le statut de l'élément associé au paiement
     */
    private function updateItemStatus(Paiement $paiement, string $statut)
    {
        if ($paiement->reservation_id) {
            $paiement->reservation->update(['statut' => 'confirmee']); // Passer directement à confirmée
        } elseif ($paiement->location_id) {
            $paiement->location->update(['statut' => 'confirmee']);
        } elseif ($paiement->vente_id) {
            $paiement->vente->update(['statut' => 'confirmee']);
        }
    }
}
