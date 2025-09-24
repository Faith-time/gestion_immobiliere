<?php
// app/Services/PropertyTransferService.php

namespace App\Services;

use App\Models\Vente;
use App\Models\Bien;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class PropertyTransferService
{
    /**
     * Effectuer le transfert de propriété automatique
     */
    public function transferProperty(Vente $vente): bool
    {
        try {
            return DB::transaction(function () use ($vente) {
                $bien = $vente->bien;
                $ancienProprietaire = $bien->proprietaire;
                $nouveauProprietaire = $vente->acheteur;

                // Vérifications de sécurité
                if (!$bien || !$ancienProprietaire || !$nouveauProprietaire) {
                    throw new \Exception('Données manquantes pour le transfert de propriété');
                }

                if ($vente->property_transferred) {
                    throw new \Exception('La propriété a déjà été transférée');
                }

                if (!$vente->isFullySigned()) {
                    throw new \Exception('Le contrat n\'est pas entièrement signé');
                }

                // 1. Sauvegarder l'ancien propriétaire dans la vente
                $vente->update([
                    'ancien_proprietaire_id' => $ancienProprietaire->id,
                    'property_transferred' => true,
                    'property_transferred_at' => now(),
                ]);

                // 2. Transférer la propriété du bien
                $bien->update([
                    'proprietaire_id' => $nouveauProprietaire->id,
                    'status' => 'vendu', // S'assurer que le statut est correct
                ]);

                // 3. Attribuer le rôle propriétaire au nouvel acheteur s'il ne l'a pas
                if (!$nouveauProprietaire->hasRole('proprietaire') && !$nouveauProprietaire->hasRole('admin')) {
                    $proprietaireRole = Role::firstOrCreate(['name' => 'proprietaire']);
                    $nouveauProprietaire->assignRole('proprietaire');
                }

                // 4. Créer un nouveau mandat de gestion pour le nouveau propriétaire si nécessaire
                $this->createPostSaleMandat($bien, $nouveauProprietaire);

                // 5. Mettre à jour le statut de la vente
                $vente->update(['status' => 'confirmée']);

                // 6. Logger l'événement
                Log::info('Transfert de propriété effectué', [
                    'vente_id' => $vente->id,
                    'bien_id' => $bien->id,
                    'ancien_proprietaire_id' => $ancienProprietaire->id,
                    'nouveau_proprietaire_id' => $nouveauProprietaire->id,
                    'date_transfert' => now(),
                ]);

                return true;
            });

        } catch (\Exception $e) {
            Log::error('Erreur lors du transfert de propriété', [
                'vente_id' => $vente->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Créer un mandat post-vente si nécessaire
     */
    private function createPostSaleMandat(Bien $bien, User $nouveauProprietaire): void
    {
        // Terminer l'ancien mandat de vente
        if ($bien->mandat && $bien->mandat->type_mandat === 'vente') {
            $bien->mandat->update([
                'statut' => 'expire',
                'date_fin_effective' => now(),
            ]);
        }

        // Optionnel : Créer un nouveau mandat de gestion locative
        // (À activer selon vos besoins métier)
        /*
        $nouveauMandat = $bien->mandat()->create([
            'type_mandat' => 'gestion_locative',
            'date_debut' => now(),
            'date_fin' => now()->addYear(),
            'commission_pourcentage' => 10,
            'commission_fixe' => 0,
            'statut' => 'en_attente',
        ]);
        */
    }

    /**
     * Vérifier si un transfert peut être effectué
     */
    public function canTransferProperty(Vente $vente): bool
    {
        return $vente->isFullySigned()
            && !$vente->property_transferred
            && $vente->status !== 'annulée'
            && $vente->bien
            && $vente->acheteur;
    }

    /**
     * Obtenir l'historique des transferts pour un bien
     */
    public function getTransferHistory(Bien $bien): array
    {
        return Vente::where('biens_id', $bien->id)
            ->where('property_transferred', true)
            ->with(['acheteur', 'ancienProprietaire'])
            ->orderBy('property_transferred_at', 'desc')
            ->get()
            ->map(function ($vente) {
                return [
                    'date_transfert' => $vente->property_transferred_at,
                    'ancien_proprietaire' => $vente->ancienProprietaire?->name,
                    'nouveau_proprietaire' => $vente->acheteur?->name,
                    'prix_vente' => $vente->prix_vente,
                ];
            })
            ->toArray();
    }
}
