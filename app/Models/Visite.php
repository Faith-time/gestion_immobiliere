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
        'appartement_id',
        'client_id',
        'date_visite',
        'message',
        'notes_admin',
        'motif_rejet',
        'motif_annulation',
        'commentaire_visite',
        'confirmee_at',
        'confirmee_par',
        'rejetee_at',
        'rejetee_par',
        'effectuee_at',
        'effectuee_par',
    ];

    protected $casts = [
        'date_visite' => 'datetime',
        'confirmee_at' => 'datetime',
        'rejetee_at' => 'datetime',
        'effectuee_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

    public function bien()
    {
        return $this->belongsTo(Bien::class, 'bien_id');
    }

    public function appartement()
    {
        return $this->belongsTo(Appartement::class, 'appartement_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmee_par');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejetee_par');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'effectuee_par');
    }

    // ==================== MÉTHODES ====================

    public function isEnAttente()
    {
        return $this->statut === 'en_attente';
    }

    public function isConfirmee()
    {
        return $this->statut === 'confirmee';
    }

    public function isEffectuee()
    {
        return $this->statut === 'effectuee';
    }

    public function isAnnulee()
    {
        return $this->statut === 'annulee';
    }

    public function isRejetee()
    {
        return $this->statut === 'rejetee';
    }

    // ==================== SCOPES ====================

    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeConfirmee($query)
    {
        return $query->where('statut', 'confirmee');
    }

    public function scopeEffectuee($query)
    {
        return $query->where('statut', 'effectuee');
    }

    public function scopePourClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopePourBien($query, $bienId)
    {
        return $query->where('bien_id', $bienId);
    }
}
