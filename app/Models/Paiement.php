<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'montant_total',
        'montant_paye',
        'montant_restant',
        'commission_agence',
        'transaction_id',
        'mode_paiement',
        'statut',
        'date_transaction',
        'vente_id',
        'location_id',
        'reservation_id'
    ];

    protected $casts = [
        'date_transaction' => 'datetime',
        'montant_total' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'montant_restant' => 'decimal:2',
        'commission_agence' => 'decimal:2',
    ];

    public function vente()
    {
        return $this->belongsTo(Vente::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function reservation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
