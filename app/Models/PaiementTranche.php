<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaiementTranche extends Model
{
    use HasFactory;

    protected $fillable = [
        'paiement_id',
        'numero_tranche',
        'total_tranches',
        'montant_tranche',
        'transaction_id',
        'statut',
        'date_paiement'
    ];

    protected $casts = [
        'montant_tranche' => 'decimal:2',
        'date_paiement' => 'datetime',
        'numero_tranche' => 'integer',
        'total_tranches' => 'integer'
    ];

    /**
     * Relations
     */
    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    /**
     * Accesseurs
     */
    public function getEstPayeeAttribute()
    {
        return $this->statut === 'reussi';
    }

    public function getEstEnAttenteAttribute()
    {
        return $this->statut === 'en_attente';
    }

    public function getEstDerniereTrancheAttribute()
    {
        return $this->numero_tranche === $this->total_tranches;
    }

    /**
     * Scopes
     */
    public function scopeReussies($query)
    {
        return $query->where('statut', 'reussi');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeEchouees($query)
    {
        return $query->where('statut', 'echoue');
    }
}
