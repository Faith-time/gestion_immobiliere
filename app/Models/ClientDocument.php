<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    protected $fillable = [
        'client_id',
        'reservation_id',
        'type_document',
        'fichier_path',
        'statut'
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
