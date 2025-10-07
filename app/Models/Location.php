<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyer_mensuel',
        'statut',
        'bien_id',
        'client_id',
        'date_debut',
        'date_fin',
        // Champs de signature
        'bailleur_signature_data',
        'bailleur_signed_at',
        'bailleur_signature_ip',
        'locataire_signature_data',
        'locataire_signed_at',
        'locataire_signature_ip',
        'signature_status',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'loyer_mensuel' => 'float',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'bailleur_signed_at' => 'datetime',
        'locataire_signed_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function bien()
    {
        return $this->belongsTo(Bien::class, 'bien_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function paiement()
    {
        return $this->hasOne(Paiement::class, 'location_id');
    }


    public function avisRetards()
    {
        return $this->hasMany(AvisRetard::class);
    }

    // Méthodes de signature
    public function isSignedByBailleur()
    {
        return !is_null($this->bailleur_signed_at) && !is_null($this->bailleur_signature_data);
    }

    public function isSignedByLocataire()
    {
        return !is_null($this->locataire_signed_at) && !is_null($this->locataire_signature_data);
    }

    public function isFullySigned()
    {
        return $this->signature_status === 'entierement_signe';
    }

    public function canBeSignedByBailleur()
    {
        return $this->statut !== 'terminee' && !$this->isSignedByBailleur();
    }

    public function canBeSignedByLocataire()
    {
        return $this->statut !== 'terminee' && !$this->isSignedByLocataire();
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
    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeFullySigned($query)
    {
        return $query->where('signature_status', 'entierement_signe');
    }

    public function isValidRental()
    {
        return $this->client_id !== $this->bien->proprietaire_id;
    }
}
