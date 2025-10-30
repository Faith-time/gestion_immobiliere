<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDossier extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'profession',
        'numero_cni',
        'personne_contact',
        'telephone_contact',
        'revenus_mensuels',
        'nombre_personnes',
        'nbchambres',
        'nbsalons',
        'nbcuisines',
        'nbsalledebains',
        'situation_familiale',
        'type_logement',
        'type_logement_autres',
        'quartier_souhaite',
        'budget_mensuel',
        'date_entree_souhaitee',
        'carte_identite',
        'dernier_recu_loyer',
    ];

    protected $casts = [
        'type_logement' => 'array',
        'budget_mensuel' => 'decimal:2',
        'date_entree_souhaitee' => 'date',
        'carte_identite' => 'boolean',
        'dernier_recu_loyer' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
