<?php

namespace App\Services;

use App\Models\Vente;
use App\Models\Bien;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyTransferService
{
    /**
     * Transférer la propriété du bien à l'acheteur après paiement complet
     */
    public function transferPropertyToBuyer(Vente $vente): bool
    {
        try {
            return DB::transaction(function () use ($vente) {
                // Vérifier que le paiement est complètement effectué
                $paiement = $vente->paiement;

                if (!$paiement || $paiement->statut !== 'reussi' || $paiement->montant_restant > 0) {
                    Log::warning('❌ Tentative de transfert avec paiement incomplet', [
                        'vente_id' => $vente->id,
                        'paiement_statut' => $paiement?->statut,
                        'montant_restant' => $paiement?->montant_restant
                    ]);
                    return false;
                }

                // Vérifier que la vente est entièrement signée
                if (!$vente->isFullySigned()) {
                    Log::warning('❌ Tentative de transfert avec signatures incomplètes', [
                        'vente_id' => $vente->id
                    ]);
                    return false;
                }

                // Charger la réservation et le bien
                $vente->load('reservation.bien');
                $bien = $vente->reservation?->bien;

                if (!$bien) {
                    Log::error('❌ Bien introuvable pour la vente', [
                        'vente_id' => $vente->id
                    ]);
                    return false;
                }

                // Sauvegarder l'ancien propriétaire si pas déjà fait
                if (!$vente->ancien_proprietaire_id) {
                    $vente->update([
                        'ancien_proprietaire_id' => $bien->proprietaire_id
                    ]);
                }

                // 1. Transférer la propriété du bien à l'acheteur
                $bien->update([
                    'proprietaire_id' => $vente->acheteur_id,
                    'status' => 'vendu'
                ]);

                Log::info('✅ Propriété transférée avec succès', [
                    'vente_id' => $vente->id,
                    'bien_id' => $bien->id,
                    'ancien_proprietaire_id' => $vente->ancien_proprietaire_id,
                    'nouveau_proprietaire_id' => $vente->acheteur_id
                ]);

                // 2. Mettre à jour le statut de la vente
                $vente->update([
                    'status' => Vente::STATUT_CONFIRMEE,
                    'property_transferred' => true,
                    'property_transferred_at' => now()
                ]);

                // 3. Mettre à jour la réservation
                if ($vente->reservation) {
                    $vente->reservation->update([
                        'statut' => 'confirmee'
                    ]);
                }

                // 4. Terminer le mandat
                if ($bien->mandat) {
                    $bien->mandat->update([
                        'statut' => 'termine',
                        'date_cloture' => now()
                    ]);

                    Log::info('✅ Mandat terminé', [
                        'mandat_id' => $bien->mandat->id
                    ]);
                }

                Log::info('✅ Transfert de propriété complété', [
                    'vente_id' => $vente->id,
                    'bien_id' => $bien->id,
                    'nouveau_proprietaire' => $vente->acheteur->name ?? 'N/A'
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('❌ Erreur lors du transfert de propriété', [
                'vente_id' => $vente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Vérifier si le transfert de propriété peut être effectué
     */
    public function canTransferProperty(Vente $vente): bool
    {
        // Charger les relations nécessaires
        $vente->load('paiement', 'reservation.bien');

        // Vérifier le paiement
        $paiement = $vente->paiement;
        if (!$paiement || $paiement->statut !== 'reussi' || $paiement->montant_restant > 0) {
            return false;
        }

        // Vérifier les signatures
        if (!$vente->isFullySigned()) {
            return false;
        }

        // Vérifier que le bien existe
        if (!$vente->reservation?->bien) {
            return false;
        }

        // Vérifier que le transfert n'a pas déjà été effectué
        if ($vente->property_transferred) {
            return false;
        }

        return true;
    }

    /**
     * Obtenir le statut du transfert de propriété
     */
    public function getTransferStatus(Vente $vente): array
    {
        $vente->load('paiement', 'reservation.bien');

        $paiement = $vente->paiement;
        $paiementComplet = $paiement &&
            $paiement->statut === 'reussi' &&
            $paiement->montant_restant <= 0;

        $signaturesCompletes = $vente->isFullySigned();
        $bienExiste = (bool) $vente->reservation?->bien;

        return [
            'peut_transferer' => $this->canTransferProperty($vente),
            'transfere' => $vente->property_transferred ?? false,
            'date_transfert' => $vente->property_transferred_at,
            'conditions' => [
                'paiement_complet' => $paiementComplet,
                'signatures_completes' => $signaturesCompletes,
                'bien_existe' => $bienExiste
            ],
            'ancien_proprietaire_id' => $vente->ancien_proprietaire_id,
            'nouveau_proprietaire_id' => $vente->property_transferred
                ? $vente->acheteur_id
                : null
        ];
    }
}
