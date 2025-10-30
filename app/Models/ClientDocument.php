<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'type_document',
        'fichier_path',
        'statut',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec le client (User)
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Vérifier si le document est validé
     */
    public function isValide()
    {
        return $this->statut === 'valide';
    }

    /**
     * Vérifier si le document est en attente
     */
    public function isEnAttente()
    {
        return $this->statut === 'en_attente';
    }

    /**
     * Vérifier si le document est refusé
     */
    public function isRefuse()
    {
        return $this->statut === 'refuse';
    }

    /**
     * Obtenir l'URL du fichier
     */
    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->fichier_path);
    }
}
