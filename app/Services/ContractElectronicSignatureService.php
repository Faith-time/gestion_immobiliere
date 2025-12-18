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
     * ✅ CORRECTION : Signer un contrat de location par le bailleur
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

            // ✅ Mettre à jour le statut de signature
            $this->updateLocationSignatureStatus($location);

            // ✅ Recharger pour avoir le nouveau statut
            $location->refresh();

            // ✅ Générer le PDF avec les signatures
            $this->generateSignedPdf($location, 'location');

            // ✅ SI ENTIÈREMENT SIGNÉ → ACTIVER LA LOCATION
            if ($location->isFullySigned()) {
                Log::info('🎯 Contrat de location ENTIÈREMENT SIGNÉ !', [
                    'location_id' => $location->id,
                    'ancien_statut' => $location->statut
                ]);

                // ✅ ACTIVER LA LOCATION
                $this->activerLocationApresSignature($location);
            }

            Log::info('✅ Contrat de location signé par bailleur', [
                'location_id' => $location->id,
                'signature_status' => $location->signature_status,
                'statut_location' => $location->statut
            ]);

            // ✅ RETOURNER l'objet rechargé
            return $location;

        } catch (\Exception $e) {
            Log::error('❌ Erreur signature bailleur', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ CORRECTION : Signer un contrat de location par le locataire
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

            // ✅ Mettre à jour le statut de signature
            $this->updateLocationSignatureStatus($location);

            // ✅ Recharger pour avoir le nouveau statut
            $location->refresh();

            // ✅ Générer le PDF avec les signatures
            $this->generateSignedPdf($location, 'location');

            // ✅ SI ENTIÈREMENT SIGNÉ → ACTIVER LA LOCATION
            if ($location->isFullySigned()) {
                Log::info('🎯 Contrat de location ENTIÈREMENT SIGNÉ !', [
                    'location_id' => $location->id,
                    'ancien_statut' => $location->statut
                ]);

                // ✅ ACTIVER LA LOCATION
                $this->activerLocationApresSignature($location);
            }

            Log::info('✅ Contrat de location signé par locataire', [
                'location_id' => $location->id,
                'signature_status' => $location->signature_status,
                'statut_location' => $location->statut
            ]);

            // ✅ RETOURNER l'objet rechargé
            return $location;

        } catch (\Exception $e) {
            Log::error('❌ Erreur signature locataire', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ NOUVELLE MÉTHODE : Activer une location après signature complète
     */
    private function activerLocationApresSignature(Location $location)
    {
        try {
            // ✅ 1. VÉRIFIER QUE LE PAIEMENT INITIAL EST VALIDÉ
            $paiementInitial = $location->paiements()
                ->where('type', 'location')
                ->where('statut', 'reussi')
                ->first();

            if (!$paiementInitial) {
                Log::warning('⚠️ Paiement initial non validé, location reste en attente', [
                    'location_id' => $location->id
                ]);
                return false;
            }

            // ✅ 2. METTRE À JOUR LE STATUT DE LA LOCATION
            $location->update(['statut' => 'active']);

            Log::info('✅ Location activée après signature complète', [
                'location_id' => $location->id,
                'nouveau_statut' => 'active'
            ]);

            // ✅ 3. MARQUER L'APPARTEMENT COMME LOUÉ (si applicable)
            if ($location->reservation && $location->reservation->appartement_id) {
                $appartement = $location->reservation->appartement;

                if ($appartement) {
                    $appartement->update(['statut' => 'loue']);

                    Log::info('🏠 Appartement marqué comme loué', [
                        'appartement_id' => $appartement->id,
                        'numero' => $appartement->numero,
                        'location_id' => $location->id
                    ]);
                }
            }

            // ✅ 4. METTRE À JOUR LE STATUT GLOBAL DU BIEN
            if ($location->reservation && $location->reservation->bien) {
                $location->reservation->bien->updateStatutGlobal();

                Log::info('🏢 Statut du bien mis à jour', [
                    'bien_id' => $location->reservation->bien->id,
                    'nouveau_statut' => $location->reservation->bien->fresh()->status
                ]);
            }

            // ✅ 5. PROGRAMMER LES NOTIFICATIONS (si service disponible)
            if ($this->contractNotificationService) {
                $this->contractNotificationService->programmerNotificationsApresSignature($location);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('❌ Erreur activation location', [
                'location_id' => $location->id,
                'error' => $e->getMessage()
            ]);
            return false;
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
            $vente->refresh();
            $this->generateSignedPdf($vente, 'vente');

            Log::info('✅ Contrat de vente signé par vendeur', [
                'vente_id' => $vente->id,
                'signature_status' => $vente->signature_status,
                'is_fully_signed' => $vente->isFullySigned()
            ]);

            return $vente;

        } catch (\Exception $e) {
            Log::error('❌ Erreur signature vendeur', [
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
            $vente->refresh();
            $this->generateSignedPdf($vente, 'vente');

            Log::info('✅ Contrat de vente signé par acheteur', [
                'vente_id' => $vente->id,
                'signature_status' => $vente->signature_status,
                'is_fully_signed' => $vente->isFullySigned()
            ]);

            return $vente;

        } catch (\Exception $e) {
            Log::error('❌ Erreur signature acheteur', [
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

        Log::info('📝 Statut de signature vente mis à jour', [
            'vente_id' => $vente->id,
            'vendeur_signed' => $vendeurSigned,
            'acheteur_signed' => $acheteurSigned,
            'new_status' => $status
        ]);
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

        Log::info('📝 Statut de signature location mis à jour', [
            'location_id' => $location->id,
            'bailleur_signed' => $bailleurSigned,
            'locataire_signed' => $locataireSigned,
            'new_status' => $status
        ]);
    }

    // Méthodes de vérification
    public function isVenteSignedByVendeur(Vente $vente)
    {
        return !is_null($vente->vendeur_signed_at) && !is_null($vente->vendeur_signature_data);
    }

    public function isVenteSignedByAcheteur(Vente $vente)
    {
        return !is_null($vente->acheteur_signed_at) && !is_null($vente->acheteur_signature_data);
    }

    public function isLocationSignedByBailleur(Location $location)
    {
        return !is_null($location->bailleur_signed_at) && !is_null($location->bailleur_signature_data);
    }

    public function isLocationSignedByLocataire(Location $location)
    {
        return !is_null($location->locataire_signed_at) && !is_null($location->locataire_signature_data);
    }

    public function isFullySigned($contract)
    {
        return $contract->signature_status === 'entierement_signe';
    }

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

    private function validateSignatureData($signatureData)
    {
        if (empty($signatureData)) {
            return false;
        }

        if (strpos($signatureData, '<svg') !== false) {
            return true;
        }

        if (strpos($signatureData, 'data:image/') === 0) {
            return true;
        }

        return base64_decode($signatureData, true) !== false;
    }

    public function getSignatureStats($contract, $type)
    {
        $contract->refresh();

        if ($type === 'vente') {
            return [
                'vendeur_signe' => $this->isVenteSignedByVendeur($contract),
                'acheteur_signe' => $this->isVenteSignedByAcheteur($contract),
                'fully_signed' => $this->isFullySigned($contract),
                'signature_status' => $contract->signature_status,
                'vendeur_signed_at' => $contract->vendeur_signed_at,
                'acheteur_signed_at' => $contract->acheteur_signed_at,
                'can_sign_vendeur' => $this->canVenteBeSignedByVendeur($contract),
                'can_sign_acheteur' => $this->canVenteBeSignedByAcheteur($contract),
            ];
        } else {
            return [
                'bailleur_signe' => $this->isLocationSignedByBailleur($contract),
                'locataire_signe' => $this->isLocationSignedByLocataire($contract),
                'fully_signed' => $this->isFullySigned($contract),
                'signature_status' => $contract->signature_status,
                'bailleur_signed_at' => $contract->bailleur_signed_at,
                'locataire_signed_at' => $contract->locataire_signed_at,
                'can_sign_bailleur' => $this->canLocationBeSignedByBailleur($contract),
                'can_sign_locataire' => $this->canLocationBeSignedByLocataire($contract),
            ];
        }
    }

    public function canVenteBeSignedByVendeur(Vente $vente)
    {
        return $vente->status !== 'annulee' && !$this->isVenteSignedByVendeur($vente);
    }

    public function canVenteBeSignedByAcheteur(Vente $vente)
    {
        return $vente->status !== 'annulee' && !$this->isVenteSignedByAcheteur($vente);
    }

    public function canLocationBeSignedByBailleur(Location $location)
    {
        return $location->statut !== 'terminee' && !$this->isLocationSignedByBailleur($location);
    }

    public function canLocationBeSignedByLocataire(Location $location)
    {
        return $location->statut !== 'terminee' && !$this->isLocationSignedByLocataire($location);
    }

    public function cancelSignature($entity, string $signatoryType, string $entityType = 'vente'): array
    {
        try {
            if ($entityType === 'vente') {
                if ($signatoryType === 'vendeur') {
                    $entity->update([
                        'vendeur_signature_data' => null,
                        'vendeur_signed_at' => null,
                        'vendeur_signature_ip' => null,
                    ]);
                } elseif ($signatoryType === 'acheteur') {
                    $entity->update([
                        'acheteur_signature_data' => null,
                        'acheteur_signed_at' => null,
                        'acheteur_signature_ip' => null,
                    ]);
                }

                $this->updateVenteSignatureStatus($entity);
            } else {
                if ($signatoryType === 'bailleur') {
                    $entity->update([
                        'bailleur_signature_data' => null,
                        'bailleur_signed_at' => null,
                        'bailleur_signature_ip' => null,
                    ]);
                } elseif ($signatoryType === 'locataire') {
                    $entity->update([
                        'locataire_signature_data' => null,
                        'locataire_signed_at' => null,
                        'locataire_signature_ip' => null,
                    ]);
                }

                $this->updateLocationSignatureStatus($entity);
            }

            return [
                'success' => true,
                'message' => 'Signature annulée avec succès.'
            ];

        } catch (\Exception $e) {
            Log::error('Erreur annulation signature', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'annulation : ' . $e->getMessage()
            ];
        }
    }
}
