<?php

namespace App\Services;

use App\Models\Vente;
use App\Models\Location;
use Illuminate\Support\Facades\Log;

class ContractElectronicSignatureService
{
    protected $contractPdfService;
    protected $contractNotificationService;

    public function __construct(
        ContractPdfService $contractPdfService,
        ContractNotificationService $contractNotificationService
    ) {
        $this->contractPdfService = $contractPdfService;
        $this->contractNotificationService = $contractNotificationService;
    }

    /**
     * Signer un contrat de location par le bailleur
     */
    public function signLocationByBailleur(Location $location, $signatureData)
    {
        try {
            if (!$this->validateSignatureData($signatureData)) {
                throw new \Exception('Données de signature invalides');
            }

            $location->update([
                'bailleur_signature_data' => $signatureData,
                'bailleur_signed_at' => now(),
                'bailleur_signature_ip' => request()->ip(),
            ]);

            $this->updateLocationSignatureStatus($location);
            $this->generateSignedPdf($location, 'location');

            // NOUVEAU: Vérifier si le contrat est maintenant entièrement signé
            $location->refresh();
            $wasJustFullySigned = $this->checkAndHandleFullSignature($location, 'bailleur');

            Log::info('Contrat de location signé par bailleur', [
                'location_id' => $location->id,
                'bailleur_id' => $location->bien->proprietaire_id,
                'signature_status' => $location->signature_status,
                'just_fully_signed' => $wasJustFullySigned
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur signature bailleur', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Signer un contrat de location par le locataire
     */
    public function signLocationByLocataire(Location $location, $signatureData)
    {
        try {
            if (!$this->validateSignatureData($signatureData)) {
                throw new \Exception('Données de signature invalides');
            }

            $location->update([
                'locataire_signature_data' => $signatureData,
                'locataire_signed_at' => now(),
                'locataire_signature_ip' => request()->ip(),
            ]);

            $this->updateLocationSignatureStatus($location);
            $this->generateSignedPdf($location, 'location');

            // NOUVEAU: Vérifier si le contrat est maintenant entièrement signé
            $location->refresh();
            $wasJustFullySigned = $this->checkAndHandleFullSignature($location, 'locataire');

            Log::info('Contrat de location signé par locataire', [
                'location_id' => $location->id,
                'locataire_id' => $location->client_id,
                'signature_status' => $location->signature_status,
                'just_fully_signed' => $wasJustFullySigned
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur signature locataire', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Signer un contrat de vente par le vendeur
     */
    public function signVenteByVendeur(Vente $vente, $signatureData)
    {
        try {
            if (!$this->validateSignatureData($signatureData)) {
                throw new \Exception('Données de signature invalides');
            }

            $vente->update([
                'vendeur_signature_data' => $signatureData,
                'vendeur_signed_at' => now(),
                'vendeur_signature_ip' => request()->ip(),
            ]);

            $this->updateVenteSignatureStatus($vente);
            $this->generateSignedPdf($vente, 'vente');

            Log::info('Contrat de vente signé par vendeur', [
                'vente_id' => $vente->id,
                'vendeur_id' => $vente->bien->proprietaire_id,
                'signature_status' => $vente->signature_status
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur signature vendeur', [
                'vente_id' => $vente->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Signer un contrat de vente par l'acheteur
     */
    public function signVenteByAcheteur(Vente $vente, $signatureData)
    {
        try {
            if (!$this->validateSignatureData($signatureData)) {
                throw new \Exception('Données de signature invalides');
            }

            $vente->update([
                'acheteur_signature_data' => $signatureData,
                'acheteur_signed_at' => now(),
                'acheteur_signature_ip' => request()->ip(),
            ]);

            $this->updateVenteSignatureStatus($vente);
            $this->generateSignedPdf($vente, 'vente');

            Log::info('Contrat de vente signé par acheteur', [
                'vente_id' => $vente->id,
                'acheteur_id' => $vente->acheteur_id,
                'signature_status' => $vente->signature_status
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur signature acheteur', [
                'vente_id' => $vente->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour le statut de signature d'une vente
     */
    private function updateVenteSignatureStatus(Vente $vente)
    {
        $vente->refresh();

        $vendeurSigned = $this->isVenteSignedByVendeur($vente);
        $acheteurSigned = $this->isVenteSignedByAcheteur($vente);

        if ($vendeurSigned && $acheteurSigned) {
            $status = 'entierement_signe';
        } elseif ($vendeurSigned || $acheteurSigned) {
            $status = 'partiellement_signe';
        } else {
            $status = 'non_signe';
        }

        $vente->update(['signature_status' => $status]);
    }

    /**
     * NOUVEAU: Vérifier si le contrat vient d'être entièrement signé et déclencher les notifications
     */
    private function checkAndHandleFullSignature(Location $location, string $lastSigner)
    {
        if ($location->signature_status === 'entierement_signe') {
            Log::info('Contrat entièrement signé détecté', [
                'location_id' => $location->id,
                'last_signer' => $lastSigner,
                'bailleur_signed_at' => $location->bailleur_signed_at,
                'locataire_signed_at' => $location->locataire_signed_at
            ]);

            // Programmer les notifications de test dans 5 et 10 minutes
            $this->contractNotificationService->programmerNotificationsApresSignature($location);

            return true;
        }

        return false;
    }

    /**
     * Mettre à jour le statut de signature d'une location
     */
    private function updateLocationSignatureStatus(Location $location)
    {
        $location->refresh();

        $bailleurSigned = $this->isLocationSignedByBailleur($location);
        $locataireSigned = $this->isLocationSignedByLocataire($location);

        if ($bailleurSigned && $locataireSigned) {
            $status = 'entierement_signe';
        } elseif ($bailleurSigned || $locataireSigned) {
            $status = 'partiellement_signe';
        } else {
            $status = 'non_signe';
        }

        $location->update(['signature_status' => $status]);
    }

    /**
     * Vérifier si une location est signée par le bailleur
     */
    public function isLocationSignedByBailleur(Location $location)
    {
        return !is_null($location->bailleur_signed_at) && !is_null($location->bailleur_signature_data);
    }

    /**
     * Vérifier si une location est signée par le locataire
     */
    public function isLocationSignedByLocataire(Location $location)
    {
        return !is_null($location->locataire_signed_at) && !is_null($location->locataire_signature_data);
    }

    /**
     * Vérifier si un contrat est entièrement signé
     */
    public function isFullySigned($contract)
    {
        return $contract->signature_status === 'entierement_signe';
    }

    /**
     * Générer le PDF signé
     */
    private function generateSignedPdf($contract, $type)
    {
        try {
            $pdfPath = $this->contractPdfService->regeneratePdf($contract, $type);

            if ($pdfPath) {
                Log::info('PDF signé généré', [
                    'contract_id' => $contract->id,
                    'type' => $type,
                    'path' => $pdfPath
                ]);
            }

            return $pdfPath;

        } catch (\Exception $e) {
            Log::error('Erreur génération PDF signé:', [
                'contract_id' => $contract->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Valider les données de signature
     */
    private function validateSignatureData($signatureData)
    {
        if (empty($signatureData)) {
            return false;
        }

        // Vérifier si c'est du SVG valide ou du base64
        if (strpos($signatureData, '<svg') !== false) {
            return true;
        }

        // Validation base64
        if (strpos($signatureData, 'data:image/') === 0) {
            return true;
        }

        // Validation base64 pur
        return base64_decode($signatureData, true) !== false;
    }

    /**
     * Obtenir les statistiques de signature
     */
    public function getSignatureStats($contract, $type)
    {
        $contract->refresh();

        if ($type === 'vente') {
            return [
                'vendeur_signed' => $this->isVenteSignedByVendeur($contract),
                'acheteur_signed' => $this->isVenteSignedByAcheteur($contract),
                'fully_signed' => $this->isFullySigned($contract),
                'signature_status' => $contract->signature_status,
                'vendeur_signed_at' => $contract->vendeur_signed_at,
                'acheteur_signed_at' => $contract->acheteur_signed_at,
                'can_sign_vendeur' => $this->canVenteBeSignedByVendeur($contract),
                'can_sign_acheteur' => $this->canVenteBeSignedByAcheteur($contract),
            ];
        } else {
            return [
                'bailleur_signed' => $this->isLocationSignedByBailleur($contract),
                'locataire_signed' => $this->isLocationSignedByLocataire($contract),
                'fully_signed' => $this->isFullySigned($contract),
                'signature_status' => $contract->signature_status,
                'bailleur_signed_at' => $contract->bailleur_signed_at,
                'locataire_signed_at' => $contract->locataire_signed_at,
                'can_sign_bailleur' => $this->canLocationBeSignedByBailleur($contract),
                'can_sign_locataire' => $this->canLocationBeSignedByLocataire($contract),
            ];
        }
    }

    /**
     * Vérifier si une location peut être signée par le bailleur
     */
    public function canLocationBeSignedByBailleur(Location $location)
    {
        return $location->statut !== 'terminee' && !$this->isLocationSignedByBailleur($location);
    }

    /**
     * Vérifier si une location peut être signée par le locataire
     */
    public function canLocationBeSignedByLocataire(Location $location)
    {
        return $location->statut !== 'terminee' && !$this->isLocationSignedByLocataire($location);
    }

    /**
     * Vérifier si une vente est signée par le vendeur
     */
    public function isVenteSignedByVendeur(Vente $vente)
    {
        return !is_null($vente->vendeur_signed_at) && !is_null($vente->vendeur_signature_data);
    }

    /**
     * Vérifier si une vente est signée par l'acheteur
     */
    public function isVenteSignedByAcheteur(Vente $vente)
    {
        return !is_null($vente->acheteur_signed_at) && !is_null($vente->acheteur_signature_data);
    }

    /**
     * Vérifier si une vente peut être signée par le vendeur
     */
    public function canVenteBeSignedByVendeur(Vente $vente)
    {
        return $vente->status !== 'annulee' && !$this->isVenteSignedByVendeur($vente);
    }

    /**
     * Vérifier si une vente peut être signée par l'acheteur
     */
    public function canVenteBeSignedByAcheteur(Vente $vente)
    {
        return $vente->status !== 'annulee' && !$this->isVenteSignedByAcheteur($vente);
    }

    /**
     * Méthode unifiée pour signer un contrat
     */
    public function signContract($contract, $signatoryType, $signatureData, $ipAddress = null)
    {
        try {
            // Déterminer le type de contrat
            $contractType = $contract instanceof Location ? 'location' : 'vente';

            if ($contractType === 'location') {
                if ($signatoryType === 'bailleur') {
                    $success = $this->signLocationByBailleur($contract, $signatureData);
                } elseif ($signatoryType === 'locataire') {
                    $success = $this->signLocationByLocataire($contract, $signatureData);
                } else {
                    throw new \Exception('Type de signataire invalide pour une location');
                }
            } else {
                if ($signatoryType === 'vendeur') {
                    $success = $this->signVenteByVendeur($contract, $signatureData);
                } elseif ($signatoryType === 'acheteur') {
                    $success = $this->signVenteByAcheteur($contract, $signatureData);
                } else {
                    throw new \Exception('Type de signataire invalide pour une vente');
                }
            }

            if ($success) {
                return [
                    'success' => true,
                    'message' => 'Signature enregistrée avec succès'
                ];
            }

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la signature'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur signature contrat:', [
                'contract_id' => $contract->id,
                'signatory_type' => $signatoryType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Annuler une signature - Version mise à jour
     */
    public function cancelSignature($contract, $signatoryType, $contractType = null)
    {
        try {
            // Auto-détection du type si non fourni
            if ($contractType === null) {
                $contractType = $contract instanceof Location ? 'location' : 'vente';
            }

            if ($contractType === 'location') {
                if ($signatoryType === 'bailleur') {
                    $contract->update([
                        'bailleur_signature_data' => null,
                        'bailleur_signed_at' => null,
                        'bailleur_signature_ip' => null,
                    ]);
                } elseif ($signatoryType === 'locataire') {
                    $contract->update([
                        'locataire_signature_data' => null,
                        'locataire_signed_at' => null,
                        'locataire_signature_ip' => null,
                    ]);
                }
                $this->updateLocationSignatureStatus($contract);

                // Annuler les notifications si nécessaire
                if ($contract->signature_status !== 'entierement_signe') {
                    $this->contractNotificationService->annulerNotificationsProgrammees($contract);
                }
            } elseif ($contractType === 'vente') {
                if ($signatoryType === 'vendeur') {
                    $contract->update([
                        'vendeur_signature_data' => null,
                        'vendeur_signed_at' => null,
                        'vendeur_signature_ip' => null,
                    ]);
                } elseif ($signatoryType === 'acheteur') {
                    $contract->update([
                        'acheteur_signature_data' => null,
                        'acheteur_signed_at' => null,
                        'acheteur_signature_ip' => null,
                    ]);
                }
                $this->updateVenteSignatureStatus($contract);
            }

            // Régénérer le PDF
            if ($contract->signature_status !== 'non_signe') {
                $this->generateSignedPdf($contract, $contractType);
            }

            Log::info('Signature annulée', [
                'contract_id' => $contract->id,
                'contract_type' => $contractType,
                'signatory_type' => $signatoryType,
                'new_status' => $contract->signature_status
            ]);

            return [
                'success' => true,
                'message' => 'Signature annulée avec succès'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur annulation signature:', [
                'contract_id' => $contract->id,
                'signatory_type' => $signatoryType,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
