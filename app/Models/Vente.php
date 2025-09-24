<?php

// Mise à jour du modèle Vente avec signatures électroniques
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Vente extends Model
{
    use HasFactory;

    protected $fillable = [
        'biens_id',
        'acheteur_id',
        'prix_vente',
        'date_vente',
        'statut',
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
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_CONFIRMEE = 'confirmée';
    public const STATUT_ANNULEE = 'annulée';

    // Relations
    public function bien()
    {
        return $this->belongsTo(Bien::class, 'biens_id');
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


// NOUVELLE RELATION pour l'ancien propriétaire
    public function ancienProprietaire()
    {
        return $this->belongsTo(User::class, 'ancien_proprietaire_id');
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
}
