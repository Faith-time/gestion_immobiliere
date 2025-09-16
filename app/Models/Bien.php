<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bien extends Model
{
    use HasFactory;

    protected $table = 'biens';

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
        'property_title',
    ];

    protected $casts = [
        'rooms' => 'integer',
        'floors' => 'integer',
        'bathrooms' => 'integer',
        'superficy' => 'float',
        'price' => 'float',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function category()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'bien_id');
    }

    public function mandats()
    {
        return $this->hasMany(Mandat::class, 'biens_id');
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
