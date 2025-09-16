<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vente extends Model
{
    use HasFactory;

    protected $fillable = [
        'biens_id',
        'acheteur_id',
        'prix_vente',
        'date_vente',
    ];

    protected $casts = [
        'prix_vente' => 'float',
        'date_vente' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function bien()
    {
        return $this->belongsTo(Bien::class, 'biens_id');
    }

    public function acheteur()
    {
        return $this->belongsTo(User::class, 'acheteur_id');
    }
}
