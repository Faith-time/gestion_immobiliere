<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyer_mensuel',
        'caution',
        'statut',
        'bien_id',
        'client_id',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'loyer_mensuel' => 'float',
        'caution' => 'float',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'location_id');
    }
}
