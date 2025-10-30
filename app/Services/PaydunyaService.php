<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaydunyaService
{
    private $masterKey;
    private $privateKey;
    private $token;
    private $mode;
    private $baseUrl;

    public function __construct()
    {
        $this->masterKey = env('PAYDUNYA_MASTER_KEY');
        $this->privateKey = env('PAYDUNYA_PRIVATE_KEY');
        $this->token = env('PAYDUNYA_TOKEN');
        $this->mode = env('PAYDUNYA_MODE', 'test');
        $this->baseUrl = $this->mode === 'live'
            ? 'https://app.paydunya.com/api/v1'
            : 'https://app.paydunya.com/sandbox-api/v1';
    }

    /**
     * Créer une facture de paiement
     */
    public function createInvoice($data)
    {
        try {
            // ✅ Augmenter le timeout à 30 secondes et retry 2 fois
            $response = Http::timeout(30)
                ->retry(2, 1000) // Retry 2 fois avec 1 seconde entre chaque tentative
                ->withHeaders([
                    'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                    'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                    'PAYDUNYA-TOKEN' => $this->token,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->baseUrl . '/checkout-invoice/create', [
                    'invoice' => [
                        'total_amount' => $data['montant'],
                        'description' => $data['description']
                    ],
                    'store' => [
                        'name' => env('PAYDUNYA_STORE_NAME'),
                        'website_url' => env('PAYDUNYA_STORE_WEBSITE'),
                        'phone' => env('PAYDUNYA_STORE_PHONE'),
                        'logo_url' => env('PAYDUNYA_STORE_LOGO', '')
                    ],
                    'custom_data' => $data['custom_data'] ?? [],
                    'actions' => [
                        'cancel_url' => $data['cancel_url'],
                        'return_url' => $data['return_url'],
                        'callback_url' => $data['callback_url']
                    ]
                ]);

            $result = $response->json();

            Log::info('✅ Création facture PayDunya', [
                'status' => $response->status(),
                'response' => $result
            ]);

            if ($response->successful() && isset($result['response_code']) && $result['response_code'] == '00') {
                return [
                    'success' => true,
                    'token' => $result['token'],
                    'url' => $result['response_text']
                ];
            }

            Log::warning('⚠️ Réponse PayDunya non réussie', [
                'response_code' => $result['response_code'] ?? 'N/A',
                'message' => $result['response_text'] ?? 'Aucun message'
            ]);

            return [
                'success' => false,
                'message' => $result['response_text'] ?? 'Erreur lors de la création de la facture'
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('❌ Erreur de connexion PayDunya (Timeout ou réseau)', [
                'message' => $e->getMessage(),
                'url' => $this->baseUrl . '/checkout-invoice/create'
            ]);

            return [
                'success' => false,
                'message' => 'Impossible de se connecter à PayDunya. Vérifiez votre connexion internet ou réessayez plus tard.'
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur création facture PayDunya', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur technique lors de la création du paiement'
            ];
        }
    }

    /**
     * Vérifier le statut d'une facture
     */
    public function checkInvoiceStatus($token)
    {
        try {
            $response = Http::timeout(30)
                ->retry(2, 1000)
                ->withHeaders([
                    'PAYDUNYA-MASTER-KEY' => $this->masterKey,
                    'PAYDUNYA-PRIVATE-KEY' => $this->privateKey,
                    'PAYDUNYA-TOKEN' => $this->token,
                    'Content-Type' => 'application/json'
                ])
                ->get($this->baseUrl . '/checkout-invoice/confirm/' . $token);

            $result = $response->json();

            Log::info('✅ Vérification statut PayDunya', [
                'token' => $token,
                'response' => $result
            ]);

            if ($response->successful() && isset($result['response_code']) && $result['response_code'] == '00') {
                return [
                    'success' => true,
                    'status' => $result['status'],
                    'custom_data' => $result['custom_data'] ?? []
                ];
            }

            return [
                'success' => false,
                'message' => 'Facture non trouvée ou paiement non complété'
            ];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('❌ Erreur de connexion vérification PayDunya', [
                'token' => $token,
                'message' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Impossible de vérifier le statut du paiement'
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur vérification PayDunya', [
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur de vérification'
            ];
        }
    }
}
