<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Mandat extends Model
{
    use HasFactory;

    protected $table = 'mandats';

    protected $fillable = [
        'bien_id',
        'type_mandat',
        'type_mandat_vente',
        'date_debut',
        'date_fin',
        'commission_pourcentage',
        'commission_fixe',
        'statut',
        'pdf_path',
        'pdf_generated_at',
        'proprietaire_signature_data',
        'proprietaire_signed_at',
        'proprietaire_signature_ip',
        'agence_signature_data',
        'agence_signed_at',
        'agence_signature_ip',
        'signature_status',
        'signed_pdf_path',
        'final_pdf_generated_at',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'commission_pourcentage' => 'decimal:2',
        'commission_fixe' => 'decimal:2',
        'pdf_generated_at' => 'datetime',
        'proprietaire_signed_at' => 'datetime',
        'agence_signed_at' => 'datetime',
        'final_pdf_generated_at' => 'datetime',
    ];

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    // Méthodes de signature
    public function isSignedByProprietaire()
    {
        return !is_null($this->proprietaire_signed_at) && !is_null($this->proprietaire_signature_data);
    }

    public function isSignedByAgence()
    {
        return !is_null($this->agence_signed_at) && !is_null($this->agence_signature_data);
    }

    /**
     * Activer le bien après signature complète
     */
    public function activateBienIfFullySigned()
    {
        // Recharger le mandat pour avoir les dernières données
        $this->refresh();

        if ($this->isFullySigned() && $this->bien) {
            $ancienStatut = $this->bien->status;

            \Log::info('🔄 Tentative activation bien après signature', [
                'mandat_id' => $this->id,
                'bien_id' => $this->bien->id,
                'is_fully_signed' => $this->isFullySigned(),
                'signature_status' => $this->signature_status,
                'bien_status_actuel' => $ancienStatut
            ]);

            // ✅ Passer le bien à "disponible" si c'était "en_validation"
            if ($ancienStatut === 'en_validation') {
                $this->bien->update(['status' => 'disponible']);

                \Log::info('✅ Bien passé à disponible après signature complète', [
                    'bien_id' => $this->bien->id,
                    'mandat_id' => $this->id,
                    'ancien_statut' => $ancienStatut,
                    'nouveau_statut' => $this->bien->fresh()->status
                ]);

                return true;
            } else {
                \Log::info('ℹ️ Bien non en validation, pas de changement de statut', [
                    'bien_id' => $this->bien->id,
                    'statut_actuel' => $ancienStatut
                ]);
            }
        } else {
            \Log::info('⚠️ Conditions non remplies pour activation', [
                'mandat_id' => $this->id,
                'is_fully_signed' => $this->isFullySigned(),
                'has_bien' => $this->bien ? true : false,
                'signature_status' => $this->signature_status
            ]);
        }

        return false;
    }
    public function isFullySigned()
    {
        return $this->signature_status === 'entierement_signe';
    }

    public function canBeSignedByProprietaire()
    {
        return $this->statut === 'actif' && !$this->isSignedByProprietaire();
    }

    public function canBeSignedByAgence()
    {
        return $this->statut === 'actif' && !$this->isSignedByAgence();
    }

    /**
     * Déterminer le statut de signature
     */

    /**
     * Obtenir le PDF final signé ou le PDF de base
     */
    public function getFinalPdfPath()
    {
        if ($this->signed_pdf_path && \Storage::disk('public')->exists($this->signed_pdf_path)) {
            return $this->signed_pdf_path;
        }

        return $this->pdf_path;
    }

    /**
     * Vérifier si le PDF final signé existe
     */
    public function hasSignedPdf()
    {
        return $this->signed_pdf_path && \Storage::disk('public')->exists($this->signed_pdf_path);
    }

    /**
     * Obtenir les données pour le PDF avec signatures
     */

    // Méthodes existantes...
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeVente($query)
    {
        return $query->where('type_mandat', 'vente');
    }

    public function scopeGestionLocative($query)
    {
        return $query->where('type_mandat', 'gestion_locative');
    }

    public function getPdfFileName()
    {
        $bien = $this->bien;
        $proprietaire = $bien->proprietaire;

        $type = $this->type_mandat === 'vente' ? 'vente' : 'gerance';
        $date = now()->format('Y-m-d');
        $proprietaireName = str_replace(' ', '_', $proprietaire->name);
        $suffix = $this->isFullySigned() ? '_signe' : '';

        return "mandat_{$type}_{$proprietaireName}_{$bien->id}_{$date}{$suffix}.pdf";
    }

    public function getPdfUrlAttribute()
    {
        $pdfPath = $this->getFinalPdfPath();
        if ($pdfPath && \Storage::disk('public')->exists($pdfPath)) {
            return asset('storage/' . $pdfPath);
        }
        return null;
    }

    public function hasPdf()
    {
        return $this->pdf_path && \Storage::disk('public')->exists($this->pdf_path);
    }

    public function getTypeMandatLabelAttribute()
    {
        if ($this->type_mandat === 'vente') {
            $typeVenteLabels = [
                'exclusif' => 'Mandat de Vente Exclusif',
                'simple' => 'Mandat de Vente Simple',
                'semi_exclusif' => 'Mandat de Vente Semi-Exclusif'
            ];
            return $typeVenteLabels[$this->type_mandat_vente] ?? 'Mandat de Vente';
        }

        return 'Mandat de Gérance';
    }

    public function getPdfData()
    {
        $bien = $this->bien;
        $proprietaire = $bien->proprietaire;

        // Configuration de l'agence
        $agence = [
            'nom' => 'CAURIS IMMOBILIER',
            'adresse' => 'Keur Massar Rond Point Jaxaay P.A.U 14',
            'ville' => 'Keur Massar',
            'representant' => 'Cauris Immobilier',
            'rc' => 'SN.DKR.2009.A.11649',
            'ninea' => '009017189',
            'tel' => '77 448 32 28 / 77 516 72 28 / 76 785 98 48',
            'email' => 'jacobleyla@hotmail.fr',
        ];

        $data = [
            'mandat' => $this,
            'bien' => $bien,
            'proprietaire' => $proprietaire,
            'agence' => $agence,
            'date_creation' => now()->format('d/m/Y'),
            'ville_signature' => 'Keur Massar',
        ];

        if ($this->type_mandat === 'vente') {
            $data['titre_mandat'] = strtoupper($this->getTypeMandatLabel());
            $data['objet'] = "Le mandant confie au mandataire le mandat " .
                ($this->type_mandat_vente === 'exclusif' ? 'exclusif' : '') .
                " de vendre son bien immobilier.";
        }
        if ($this->type_mandat === 'gestion_locative') {
            $loyerMensuel = (float) $bien->price;
            $tauxCommission = 10.00; // ← TOUJOURS 10% pour gérance
            $commissionMensuelle = round(($loyerMensuel * $tauxCommission) / 100, 2);

            $data['loyer_mensuel'] = $loyerMensuel;
            $data['taux_commission'] = $tauxCommission;
            $data['commission_mensuelle'] = $commissionMensuelle;
            $data['net_proprietaire'] = $loyerMensuel - $commissionMensuelle;
        }

        return $data;
    }
    public function getTypeMandatLabel()
    {
        if ($this->type_mandat === 'vente') {
            $typeVenteLabels = [
                'exclusif' => 'Mandat de Vente Exclusif',
                'simple' => 'Mandat de Vente Simple',
                'semi_exclusif' => 'Mandat de Vente Semi-Exclusif'
            ];
            return $typeVenteLabels[$this->type_mandat_vente] ?? 'Mandat de Vente';
        }

        return 'Mandat de Gérance';
    }


    /**
     * Signer le mandat par le propriétaire
     */
    public function signByProprietaire($signatureData, $ipAddress = null)
    {
        $this->update([
            'proprietaire_signature_data' => $signatureData,
            'proprietaire_signed_at' => now(),
            'proprietaire_signature_ip' => $ipAddress ?? request()->ip(),
        ]);

        // CORRECTION : Mettre à jour le statut de signature
        $this->updateSignatureStatus();

        return $this;
    }

    /**
     * Signer le mandat par l'agence
     */
    public function signByAgence($signatureData, $ipAddress = null)
    {
        $this->update([
            'agence_signature_data' => $signatureData,
            'agence_signed_at' => now(),
            'agence_signature_ip' => $ipAddress ?? request()->ip(),
        ]);

        // CORRECTION : Mettre à jour le statut de signature
        $this->updateSignatureStatus();

        return $this;
    }

    /**
     * Mettre à jour le statut de signature - MÉTHODE PUBLIQUE
     */
    /**
     * Mettre à jour le statut de signature - MÉTHODE PUBLIQUE
     */
    public function updateSignatureStatus()
    {
        $newStatus = $this->determineSignatureStatus();

        $this->update([
            'signature_status' => $newStatus
        ]);

        return $this;
    }

    /**
     * Déterminer le statut de signature
     */
    private function determineSignatureStatus()
    {
        // Recharger les données depuis la base
        $this->refresh();

        $proprietaireSigned = $this->isSignedByProprietaire();
        $agenceSigned = $this->isSignedByAgence();

        if ($proprietaireSigned && $agenceSigned) {
            return 'entierement_signe';
        } elseif ($proprietaireSigned || $agenceSigned) {
            return 'partiellement_signe';
        }

        return 'non_signe';
    }

    public function getPdfDataWithSignatures()
    {
        $this->refresh(); // Important : recharger les données
        $data = $this->getPdfData();

        // Utiliser le service pour préparer les signatures
        $signatureService = app(\App\Services\ElectronicSignatureService::class);
        $signatures = $signatureService->prepareSignatureForPdf($this);

        // Ajouter le statut de signature
        $signatures['signature_status'] = $this->signature_status;
        $signatures['is_fully_signed'] = $this->isFullySigned();

        // Debug log
        \Log::info('getPdfDataWithSignatures - Données finales:', [
            'mandat_id' => $this->id,
            'proprietaire_has_data' => !empty($signatures['proprietaire_signature']['data']),
            'agence_has_data' => !empty($signatures['agence_signature']['data']),
            'signature_status' => $signatures['signature_status']
        ]);

        // Fusionner les données
        return array_merge($data, $signatures);
    }
}
