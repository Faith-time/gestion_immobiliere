<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Commission extends Model
{
    const TAUX_COMMISSION = 10.00;

    protected $fillable = [
        'type',
        'commissionable_id',
        'commissionable_type',
        'bien_id',
        'mois_numero',
        'mois_concerne',
        'montant_base',
        'taux_commission',
        'montant_commission',
        'montant_net_proprietaire',
        'statut',
        'date_paiement',
        'paiement_id',
        'notes',
    ];

    protected $casts = [
        'montant_base' => 'decimal:2',
        'taux_commission' => 'decimal:2',
        'montant_commission' => 'decimal:2',
        'montant_net_proprietaire' => 'decimal:2',
        'date_paiement' => 'datetime',
        'mois_concerne' => 'date',
    ];

    // Relations
    public function commissionable()
    {
        return $this->morphTo();
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    // Scopes
    public function scopeLocation($query)
    {
        return $query->where('type', 'location');
    }

    public function scopeVente($query)
    {
        return $query->where('type', 'vente');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopePayee($query)
    {
        return $query->where('statut', 'payee');
    }

    public function scopeDuMois($query, $date)
    {
        return $query->whereYear('mois_concerne', $date->year)
            ->whereMonth('mois_concerne', $date->month);
    }

    // Méthodes
    public function marquerCommePaye($paiementId = null)
    {
        $this->update([
            'statut' => 'payee',
            'date_paiement' => now(),
            'paiement_id' => $paiementId,
        ]);
    }

    /**
     * Calculer la répartition à partir du montant de base
     */
    public static function calculerRepartition($montantBase)
    {
        $commission = round(($montantBase * self::TAUX_COMMISSION) / 100, 2);
        $montantNet = $montantBase - $commission;

        return [
            'montant_commission' => $commission, // 10% agence
            'montant_net_proprietaire' => $montantNet, // 90% proprio
        ];
    }
}
