<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Vente extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'acheteur_id',
        'prix_vente',
        'date_vente',
        'status',
        'vendeur_signature_data',
        'vendeur_signed_at',
        'vendeur_signature_ip',
        'acheteur_signature_data',
        'acheteur_signed_at',
        'acheteur_signature_ip',
        'signature_status',
        'pdf_path',
        'pdf_generated_at',
        'property_transferred',
        'property_transferred_at',
        'ancien_proprietaire_id',
        'ancien_proprietaire_id',
    ];

    protected $casts = [
        'prix_vente' => 'float',
        'date_vente' => 'date',
        'vendeur_signed_at' => 'datetime',
        'acheteur_signed_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Statuts possibles
    public const STATUT_EN_ATTENTE_PAIEMENT = 'en_attente_paiement';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_CONFIRMEE = 'confirmée';
    public const STATUT_ANNULEE = 'annulée';
    // Relations
    public function bien()
    {
        return $this->hasOneThrough(
            Bien::class,
            Reservation::class,
            'id', // clé étrangère sur reservations
            'id', // clé étrangère sur biens
            'reservation_id', // clé locale sur ventes
            'bien_id' // clé locale sur reservations
        );
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }

    // Méthodes de signature
    public function isSignedByVendeur()
    {
        return !is_null($this->vendeur_signed_at) && !is_null($this->vendeur_signature_data);
    }

    public function isSignedByAcheteur()
    {
        return !is_null($this->acheteur_signed_at) && !is_null($this->acheteur_signature_data);
    }

    public function isFullySigned()
    {
        return $this->signature_status === 'entierement_signe';
    }

    public function canBeSignedByVendeur()
    {
        return $this->statut !== 'annulee' && !$this->isSignedByVendeur();
    }

    public function canBeSignedByAcheteur()
    {
        return $this->statut !== 'annulee' && !$this->isSignedByAcheteur();
    }

    // Méthodes PDF
    public function hasPdf()
    {
        return $this->pdf_path && Storage::disk('public')->exists($this->pdf_path);
    }

    public function getPdfUrl()
    {
        if ($this->hasPdf()) {
            return asset('storage/' . $this->pdf_path);
        }
        return null;
    }

    // Scopes
    public function scopeConfirmees($query)
    {
        return $query->where('statut', self::STATUT_CONFIRMEE);
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', self::STATUT_EN_COURS);
    }

    public function scopeFullySigned($query)
    {
        return $query->where('signature_status', 'entierement_signe');
    }


// NOUVELLES MÉTHODES
    public function isPropertyTransferred()
    {
        return $this->property_transferred;
    }

    public function canTransferProperty()
    {
        return $this->isFullySigned() && !$this->property_transferred && $this->statut !== 'annulee';
    }

    public function paiement()
    {
        return $this->hasOne(Paiement::class, 'vente_id');
    }

    public function ancienProprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ancien_proprietaire_id');
    }

    /**
     * Obtenir le statut de la transaction
     */
    public function getTransactionStatus(): array
    {
        $paiement = $this->paiement;
        $paiementComplet = $paiement &&
            $paiement->statut === 'reussi' &&
            $paiement->montant_restant <= 0;

        return [
            'paiement_complet' => $paiementComplet,
            'montant_paye' => $paiement?->montant_paye ?? 0,
            'montant_restant' => $paiement?->montant_restant ?? $this->prix_vente,
            'signatures_completes' => $this->isFullySigned(),
            'vendeur_signe' => $this->isSignedByVendeur(),
            'acheteur_signe' => $this->isSignedByAcheteur(),
            'propriete_transferee' => $this->isPropertyTransferred(),
            'date_transfert' => $this->property_transferred_at,
            'statut_vente' => $this->status,
        ];
    }
}
