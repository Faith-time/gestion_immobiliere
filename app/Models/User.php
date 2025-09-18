<?php
// app/Models/User.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relations
    public function biens()
    {
        return $this->hasMany(Bien::class, 'proprietaire_id');
    }

    public function visites()
    {
        return $this->hasMany(Visite::class, 'agent_id');
    }

    public function clientVisites()
    {
        return $this->hasMany(Visite::class, 'client_id');
    }

    public function ventes()
    {
        return $this->hasMany(Vente::class, 'acheteur_id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'client_id');
    }

    public function clientDocuments()
    {
        return $this->hasMany(ClientDocument::class, 'client_id');
    }
}
