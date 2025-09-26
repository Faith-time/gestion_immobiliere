<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvisRetard extends Model
{
    use HasFactory;

    protected $table = 'avis_retards';

    protected $fillable = [
        'location_id',
        'type', // 'rappel' ou 'retard'
        'date_echeance',
        'montant_du',
        'jours_retard',
        'statut', // 'envoye', 'paye', 'annule'
        'date_envoi',
        'date_paiement',
        'commentaires'
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'date_envoi' => 'datetime',
        'date_paiement' => 'datetime',
        'montant_du' => 'decimal:2',
        'jours_retard' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec la location
     */
    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Scopes
     */
    public function scopeRappels($query)
    {
        return $query->where('type', 'rappel');
    }

    public function scopeRetards($query)
    {
        return $query->where('type', 'retard');
    }

    public function scopeEnvoyes($query)
    {
        return $query->where('statut', 'envoye');
    }

    public function scopePayes($query)
    {
        return $query->where('statut', 'paye');
    }

    /**
     * Accesseurs
     */
    public function getIsRappelAttribute()
    {
        return $this->type === 'rappel';
    }

    public function getIsRetardAttribute()
    {
        return $this->type === 'retard';
    }

    public function getIsPayeAttribute()
    {
        return $this->statut === 'paye';
    }

    /**
     * Calculer les pénalités de retard si applicables
     */
    public function getPenalitesAttribute()
    {
        if ($this->type === 'retard' && $this->jours_retard > 0) {
            // Par exemple: 2% du loyer par semaine de retard
            $semaines = ceil($this->jours_retard / 7);
            return $this->montant_du * 0.02 * $semaines;
        }
        return 0;
    }

    /**
     * Montant total avec pénalités
     */
    public function getMontantTotalAttribute()
    {
        return $this->montant_du + $this->penalites;
    }
}
