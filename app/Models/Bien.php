<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image',
        'rooms',
        'floors',
        'bathrooms',
        'city',
        'address',
        'superficy',
        'price',
        'status',
        'categorie_id',
        'proprietaire_id',
        'property_title'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'superficy' => 'decimal:2',
        'rooms' => 'integer',
        'floors' => 'integer',
        'bathrooms' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function getTypeAttribute()
    {
        return $this->category ? $this->category->name : null;
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function mandat()
    {
        return $this->hasOne(Mandat::class);
    }

    public function mandatActuel()
    {
        return $this->hasOne(Mandat::class)->where('statut', 'actif');
    }

    public function scopeAvecMandat($query)
    {
        return $query->whereHas('mandats', function ($q) {
            $q->where('statut', 'actif');
        });
    }

    public function scopeVente($query)
    {
        return $query->whereHas('mandats', function ($q) {
            $q->where('type_mandat', 'vente')->where('statut', 'actif');
        });
    }

    public function scopeLocation($query)
    {
        return $query->whereHas('mandats', function ($q) {
            $q->where('type_mandat', 'gestion_locative')->where('statut', 'actif');
        });
    }

    public function getTypeMandatAttribute()
    {
        $mandatActuel = $this->mandatActuel;
        return $mandatActuel ? $mandatActuel->type_mandat : null;
    }

    public function getIsPourVenteAttribute()
    {
        return $this->type_mandat === 'vente';
    }

    public function getIsPourLocationAttribute()
    {
        return $this->type_mandat === 'gestion_locative';
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'bien_id');
    }


    public function visites()
    {
        return $this->hasMany(Visite::class, 'bien_id');
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class, 'biens_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'bien_id');
    }
}
