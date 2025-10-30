<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'loyer_mensuel',
        'statut',
        'reservation_id',
        'client_id',
        'date_debut',
        'date_fin',
        'type_contrat',
        'bailleur_signature_data',
        'bailleur_signed_at',
        'bailleur_signature_ip',
        'locataire_signature_data',
        'locataire_signed_at',
        'locataire_signature_ip',
        'signature_status',
        'pdf_path',
        'pdf_generated_at',
    ];

    protected $casts = [
        'loyer_mensuel' => 'float',
        'date_debut' => 'date',
        'date_fin' => 'date',
        'bailleur_signed_at' => 'datetime',
        'locataire_signed_at' => 'datetime',
        'pdf_generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

    /**
     * Réservation associée à la location
     */
    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id');
    }

    /**
     * Client/Locataire de la location
     */
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Bien via la réservation
     */
    public function bien()
    {
        return $this->hasOneThrough(
            Bien::class,
            Reservation::class,
            'id',              // Clé étrangère sur reservations
            'id',              // Clé étrangère sur biens
            'reservation_id',  // Clé locale sur locations
            'bien_id'          // Clé locale sur reservations
        );
    }

    /**
     * Appartement via la réservation (si applicable)
     */
    public function appartement()
    {
        return $this->hasOneThrough(
            Appartement::class,
            Reservation::class,
            'id',                // Clé étrangère sur reservations
            'id',                // Clé étrangère sur appartements
            'reservation_id',    // Clé locale sur locations
            'appartement_id'     // Clé locale sur reservations
        );
    }

    /**
     * Paiement unique de la location
     */
    public function paiement()
    {
        return $this->hasOne(Paiement::class, 'location_id')
            ->where('type', 'location')
            ->latest();
    }

    /**
     * Tous les paiements (initial + loyers mensuels)
     */
    public function paiements()
    {
        return $this->hasMany(Paiement::class, 'location_id');
    }

    /**
     * Avis de retard
     */
    public function avisRetards()
    {
        return $this->hasMany(AvisRetard::class);
    }

    // ==================== ACCESSEURS ====================

    /**
     * Obtenir le bien (charge automatiquement si nécessaire)
     */
    public function getBienAttribute()
    {
        if ($this->relationLoaded('reservation') && $this->reservation) {
            return $this->reservation->bien;
        }

        // Charger dynamiquement
        $this->load('reservation.bien');
        return $this->reservation ? $this->reservation->bien : null;
    }

    /**
     * Obtenir l'appartement (si applicable)
     */
    public function getAppartementAttribute()
    {
        if ($this->relationLoaded('reservation') && $this->reservation) {
            return $this->reservation->appartement;
        }

        // Charger dynamiquement
        $this->load('reservation.appartement');
        return $this->reservation ? $this->reservation->appartement : null;
    }

    // ==================== TYPES DE CONTRATS ====================

    const TYPE_BAIL_CLASSIQUE = 'bail_classique';
    const TYPE_BAIL_MEUBLE = 'bail_meuble';
    const TYPE_BAIL_COMMERCIAL = 'bail_commercial';

    public static function getTypesContrat()
    {
        return [
            self::TYPE_BAIL_CLASSIQUE => [
                'label' => 'Bail d\'Habitation Classique',
                'duree_min' => 36,
                'icon' => 'fa-home',
                'color' => 'primary',
                'description' => 'Location d\'un logement non meublé. Durée minimale de 3 ans.',
                'caracteristiques' => [
                    'Logement vide (non meublé)',
                    'Durée minimale : 3 ans renouvelable',
                    'Préavis locataire : 3 mois',
                    'Préavis propriétaire : 6 mois',
                    'Dépôt de garantie : 2 mois maximum',
                ]
            ],
            self::TYPE_BAIL_MEUBLE => [
                'label' => 'Bail Meublé',
                'duree_min' => 12,
                'icon' => 'fa-couch',
                'color' => 'success',
                'description' => 'Location d\'un logement meublé. Durée minimale d\'1 an.',
                'caracteristiques' => [
                    'Logement entièrement meublé',
                    'Durée minimale : 1 an (9 mois étudiants)',
                    'Préavis locataire : 1 mois',
                    'Préavis propriétaire : 3 mois',
                    'Dépôt de garantie : 2 mois maximum',
                ]
            ],
            self::TYPE_BAIL_COMMERCIAL => [
                'label' => 'Bail Commercial',
                'duree_min' => 36,
                'icon' => 'fa-store',
                'color' => 'warning',
                'description' => 'Location d\'un local commercial. Bail 3-6-9 ans.',
                'caracteristiques' => [
                    'Local commercial/professionnel',
                    'Bail 3-6-9 ans',
                    'Préavis : 6 mois',
                    'Pas de plafonnement du loyer',
                    'Dépôt de garantie : 3 à 6 mois',
                ]
            ],
        ];
    }

    public function getTypeContratInfo()
    {
        $types = self::getTypesContrat();
        return $types[$this->type_contrat] ?? $types[self::TYPE_BAIL_CLASSIQUE];
    }

    public function isDureeValide($dureeMois)
    {
        $typeInfo = $this->getTypeContratInfo();
        return $dureeMois >= $typeInfo['duree_min'];
    }

    // ==================== MÉTHODES DE SIGNATURE ====================

    public function isSignedByBailleur()
    {
        return !is_null($this->bailleur_signed_at) && !is_null($this->bailleur_signature_data);
    }

    public function isSignedByLocataire()
    {
        return !is_null($this->locataire_signed_at) && !is_null($this->locataire_signature_data);
    }

    public function isFullySigned()
    {
        return $this->signature_status === 'entierement_signe';
    }

    public function canBeSignedByBailleur()
    {
        return $this->statut !== 'terminee' && !$this->isSignedByBailleur();
    }

    public function canBeSignedByLocataire()
    {
        return $this->statut !== 'terminee' && !$this->isSignedByLocataire();
    }

    // ==================== MÉTHODES PDF ====================

    public function hasPdf()
    {
        return $this->pdf_path && Storage::disk('public')->exists($this->pdf_path);
    }

    public function getPdfUrl()
    {
        if ($this->hasPdf()) {
            return asset('storage/' . $this->pdf_path);
        }
        return null;
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('statut', 'active');
    }


    public function scopeFullySigned($query)
    {
        return $query->where('signature_status', 'entierement_signe');
    }

    // ==================== MÉTHODES DE VALIDATION ====================

    public function isValidRental()
    {
        $bien = $this->reservation?->bien;
        return $bien && $this->client_id !== $bien->proprietaire_id;
    }
}
