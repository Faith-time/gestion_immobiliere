<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'bien_id',
        'montant',
        'statut',
        'paiement_id',
        'date_reservation',
        'motif_rejet'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_reservation' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function clientDocuments()
    {
        return $this->hasMany(ClientDocument::class, 'reservation_id');
    }

}
