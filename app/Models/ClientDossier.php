<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientDossier extends Model
{
    use HasFactory;

    protected $table = 'client_dossiers';

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
        'date_entree_souhaitee',
        'carte_identite_path',
        // ✅ PAS DE derniere_quittance_path
    ];

    protected $casts = [
        'revenus_mensuels' => 'string',
        'nombre_personnes' => 'integer',
        'nbchambres' => 'integer',
        'nbsalons' => 'integer',
        'nbcuisines' => 'integer',
        'nbsalledebains' => 'integer',
        'situation_familiale' => 'string',
        'type_logement' => 'array',
        'date_entree_souhaitee' => 'date',
    ];

    /**
     * Relation avec le client (User)
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Accesseur pour formater revenus_mensuels
     */
    public function getRevenusMensuelsFormatteAttribute()
    {
        $revenus = [
            'plus_100000' => '+ 100 000 FCFA',
            'plus_200000' => '+ 200 000 FCFA',
            'plus_300000' => '+ 300 000 FCFA',
            'plus_500000' => '+ 500 000 FCFA',
        ];

        return $revenus[$this->revenus_mensuels] ?? $this->revenus_mensuels;
    }

    /**
     * Accesseur pour formater situation_familiale
     */
    public function getSituationFamilialeFormatteAttribute()
    {
        $situations = [
            'celibataire' => 'Célibataire',
            'marie' => 'Marié(e)',
        ];

        return $situations[$this->situation_familiale] ?? $this->situation_familiale;
    }

    /**
     * Accesseur pour obtenir l'URL complète de la carte d'identité
     */
    public function getCarteIdentiteUrlAttribute()
    {
        return $this->carte_identite_path
            ? asset('storage/' . $this->carte_identite_path)
            : null;
    }

    /**
     * Vérifier si la carte d'identité est fournie
     */
    public function hasCarteIdentite()
    {
        return !empty($this->carte_identite_path);
    }

    /**
     * ✅ Vérifier si le dossier est complet (sans quittance)
     */
    public function isComplete()
    {
        return !empty($this->profession) &&
            !empty($this->numero_cni) &&
            !empty($this->personne_contact) &&
            !empty($this->telephone_contact) &&
            !empty($this->revenus_mensuels) &&
            $this->hasCarteIdentite();
    }

    /**
     * Scope pour les dossiers complets
     */
    public function scopeComplete($query)
    {
        return $query->whereNotNull('profession')
            ->whereNotNull('numero_cni')
            ->whereNotNull('personne_contact')
            ->whereNotNull('telephone_contact')
            ->whereNotNull('revenus_mensuels')
            ->whereNotNull('carte_identite_path');
    }
}
