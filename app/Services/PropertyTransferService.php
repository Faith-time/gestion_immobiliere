<?php

namespace App\Services;

use App\Models\Vente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyTransferService
{
    /**
     * Transfère la propriété du bien à l'acheteur
     */
    public function transferPropertyToBuyer(Vente $vente): bool
    {
        Log::info('🔄 Début transfert propriété', [
            'vente_id' => $vente->id,
            'acheteur_id' => $vente->acheteur_id
        ]);

        try {
            DB::beginTransaction();

            // ✅ Récupérer le bien via reservation
            $bien = $vente->reservation?->bien;

            if (!$bien) {
                Log::error('❌ Bien introuvable via reservation', [
                    'vente_id' => $vente->id,
                    'reservation_id' => $vente->reservation_id
                ]);
                DB::rollBack();
                return false;
            }

            Log::info('✅ Bien trouvé', [
                'bien_id' => $bien->id,
                'ancien_proprietaire_id' => $bien->proprietaire_id,
                'nouveau_proprietaire_id' => $vente->acheteur_id
            ]);

            // ✅ Sauvegarder l'ancien propriétaire si ce n'est pas déjà fait
            if (!$vente->ancien_proprietaire_id) {
                $vente->update([
                    'ancien_proprietaire_id' => $bien->proprietaire_id
                ]);

                Log::info('✅ Ancien propriétaire sauvegardé dans la vente', [
                    'ancien_proprietaire_id' => $bien->proprietaire_id
                ]);
            }

            // ✅ CORRECTION : On ne change PAS le proprietaire_id du bien
            // On marque juste le bien comme vendu
            $bien->update([
                'status' => 'vendu'
            ]);

            Log::info('✅ Bien marqué comme vendu (propriétaire inchangé)', [
                'bien_id' => $bien->id,
                'proprietaire_id' => $bien->proprietaire_id,
                'nouveau_status' => $bien->status
            ]);

            // ✅ Marquer le transfert comme effectué
            $vente->update([
                'property_transferred' => true,
                'property_transferred_at' => now()
            ]);

            Log::info('✅ Vente marquée comme transférée', [
                'vente_id' => $vente->id,
                'property_transferred' => true
            ]);

            // ✅ Mettre à jour le mandat si existe
            if ($bien->mandat) {
                $bien->mandat->update([
                    'statut' => 'termine'
                ]);

                Log::info('✅ Mandat terminé', [
                    'mandat_id' => $bien->mandat->id
                ]);
            }

            DB::commit();

            Log::info('✅ ✅ ✅ Transfert propriété RÉUSSI', [
                'vente_id' => $vente->id,
                'bien_id' => $bien->id,
                'ancien_proprietaire' => $vente->ancien_proprietaire_id,
                'proprietaire_actuel' => $bien->proprietaire_id,
                'acheteur' => $vente->acheteur_id,
                'note' => 'Le proprietaire_id du bien reste inchangé'
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('❌ ❌ ❌ ÉCHEC transfert propriété', [
                'vente_id' => $vente->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }
}
