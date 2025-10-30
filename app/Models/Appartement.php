<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appartement extends Model
{
    use HasFactory;

    protected $fillable = [
        'bien_id',
        'numero',
        'etage',
        'superficie',
        'salons',
        'chambres',
        'salles_bain',
        'cuisines',
        'statut',
        'description',
    ];

    protected $casts = [
        'superficie' => 'decimal:2',
        'etage' => 'integer',
        'salons' => 'integer',         // ✅ CHANGÉ
        'chambres' => 'integer',
        'salles_bain' => 'integer',
        'cuisines' => 'integer',       // ✅ AJOUTÉ
    ];

    // ==================== RELATIONS ====================

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    public function locationActive()
    {
        return $this->hasOneThrough(
            Location::class,
            Reservation::class,
            'appartement_id',
            'reservation_id',
            'id',
            'id'
        )
            ->whereIn('locations.statut', ['active', 'en_attente_paiement'])
            ->latest('locations.created_at');
    }

    public function locations()
    {
        return $this->hasManyThrough(
            Location::class,
            Reservation::class,
            'appartement_id',
            'reservation_id',
            'id',
            'id'
        );
    }

    // ==================== ACCESSEURS ====================

    public function getNumeroCompletAttribute()
    {
        return $this->getEtageLabel() . ' - ' . $this->numero;
    }

    public function getEtageLabel()
    {
        $labels = [
            0 => 'Rez-de-chaussée',
            1 => '1er étage',
            2 => '2ème étage',
            3 => '3ème étage',
        ];

        return $labels[$this->etage] ?? $this->etage . 'ème étage';
    }

    // ==================== MÉTHODES ====================

    public function isDisponible()
    {
        return $this->statut === 'disponible';  // ✅ CORRIGÉ
    }

    public function isLoue()
    {
        return $this->statut === 'loue';
    }

    public function isReserve()
    {
        return $this->statut === 'reserve';
    }

    public function marquerCommeLoue()
    {
        $this->update(['statut' => 'loue']);
        $this->bien->updateStatutGlobal();
    }

    public function marquerCommeDisponible()
    {
        $this->update(['statut' => 'disponible']);
        $this->bien->updateStatutGlobal();
    }

    // ==================== SCOPES ====================

    public function scopeDisponibles($query)
    {
        return $query->where('statut', 'disponible');
    }

    public function scopeLoues($query)
    {
        return $query->where('statut', 'loue');
    }

    public function scopeDuBien($query, $bienId)
    {
        return $query->where('bien_id', $bienId);
    }
}
