<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mandat extends Model
{
    use HasFactory;

    protected $fillable = [
        'bien_id',
        'type_mandat',
        'date_debut',
        'date_fin',
        'commission_pourcentage',
        'commission_fixe',
        'conditions_particulieres',
        'statut'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'commission_pourcentage' => 'decimal:2',
        'commission_fixe' => 'decimal:2'
    ];

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeVente($query)
    {
        return $query->where('type_mandat', 'vente');
    }

    public function scopeGestionLocative($query)
    {
        return $query->where('type_mandat', 'gestion_locative');
    }
}
