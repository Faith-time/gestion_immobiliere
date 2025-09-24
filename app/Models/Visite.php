<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visite extends Model
{
    use HasFactory;

    protected $fillable = [
        'statut',
        'bien_id',
        'client_id',
        'date_visite',
        'message',

    ];

    protected $casts = [
        'date_visite' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function bien()
    {
        return $this->belongsTo(Bien::class,'bien_id');
    }


    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
