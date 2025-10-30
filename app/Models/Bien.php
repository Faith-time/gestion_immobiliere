<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Bien extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'rooms',
        'floors',
        'bathrooms',
        'kitchens',
        'living_rooms',
        'city',
        'address',
        'superficy',
        'price',
        'status',
        'categorie_id',
        'proprietaire_id',
        'property_title',
        'motif_rejet',
        'rejected_at',
        'rejected_by',
        'validated_at',
        'validated_by'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'superficy' => 'decimal:2',
        'rooms' => 'integer',
        'floors' => 'integer',
        'bathrooms' => 'integer',
        'kitchens' => 'integer',
        'living_rooms' => 'integer',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

// ==================== RELATIONS ====================

    public function category()
    {
        return $this->belongsTo(Categorie::class, 'categorie_id');
    }

    public function proprietaire()
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function mandat()
    {
        return $this->hasOne(Mandat::class);
    }

    public function images()
    {
        return $this->hasMany(Image::class, 'bien_id')->orderBy('created_at');
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

    public function mandatActuel()
    {
        return $this->hasOne(Mandat::class)->where('statut', 'actif');
    }

    public function appartements()
    {
        return $this->hasMany(Appartement::class);
    }

// ==================== ACCESSEURS ====================

    public function getTypeAttribute()
    {
        return $this->category ? $this->category->name : null;
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

    public function getFirstImageAttribute()
    {
        return $this->images()->first();
    }

    public function getFirstImageUrlAttribute()
    {
        $firstImage = $this->getFirstImageAttribute();
        return $firstImage ? $firstImage->url : null;
    }

    public function getImageCountAttribute()
    {
        return $this->images()->count();
    }

// ==================== SCOPES ====================

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

    public function scopeDisponible($query)
    {
        return $query->where('status', 'disponible');
    }

    public function scopeEnValidation($query)
    {
        return $query->where('status', 'en_validation');
    }

    public function scopeRejete($query)
    {
        return $query->where('status', 'rejete');
    }

    public function scopeProprietaire($query, $proprietaireId)
    {
        return $query->where('proprietaire_id', $proprietaireId);
    }

    public function scopeVille($query, $ville)
    {
        return $query->where('city', $ville);
    }

    public function scopeCategorie($query, $categorieId)
    {
        return $query->where('categorie_id', $categorieId);
    }

    public function scopeRecherche($query, $terme)
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('title', 'like', "%$terme%")
                ->orWhere('description', 'like', "%$terme%")
                ->orWhere('address', 'like', "%$terme%")
                ->orWhere('city', 'like', "%$terme%");
        });
    }

// ==================== MÉTHODES ====================

    public function hasImages()
    {
        return $this->images()->exists();
    }

    public function canBeValidated()
    {
        return $this->status === 'en_validation';
    }

    public function canBeRejected()
    {
        return $this->status === 'en_validation';
    }

    public function isAvailable()
    {
        return $this->status === 'disponible';
    }

    public function isSold()
    {
        return $this->status === 'vendu';
    }

    public function isRented()
    {
        return $this->status === 'loue';
    }

    public function isReserved()
    {
        return $this->status === 'reserve';
    }

    public function isRejected()
    {
        return $this->status === 'rejete';
    }

    public function getPricePerSquareMeter()
    {
        return $this->superficy ? $this->price / $this->superficy : 0;
    }

// ==================== BOOT ====================

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bien) {
            if (!$bien->status) {
                $bien->status = 'en_validation';
            }
        });
    }

    public function appartementsDisponibles()
    {
        return $this->hasMany(Appartement::class)
            ->where('statut', 'disponible')
            ->orderBy('etage');
    }

    public function appartementsLoues()
    {
        return $this->hasMany(Appartement::class)
            ->where('statut', 'loue')
            ->orderBy('etage');
    }

    /**
     * Vérifie si le bien est un immeuble avec plusieurs appartements
     */
    public function isImmeuble()
    {
        return $this->appartements()->count() > 1;
    }

    /**
     * Génère automatiquement les appartements selon le nombre d'étages
     */
    public function genererAppartements()
    {
        // Supprimer les appartements existants
        $this->appartements()->delete();

        // Créer les appartements (du RDC au dernier étage, sans terrasse)
        $nombreEtages = $this->floors; // floors contient le nombre d'étages

        for ($etage = 0; $etage <= $nombreEtages; $etage++) {
            $numero = $this->genererNumeroAppartement($etage);

            Appartement::create([
                'bien_id' => $this->id,
                'numero' => $numero,
                'etage' => $etage,
                'superficie' => $this->superficy / ($nombreEtages + 1), // Distribution égale
                'pieces' => $this->living_rooms ?? $this->rooms, // Utiliser living_rooms si disponible
                'chambres' => max(1, floor($this->rooms / 2)),
                'salles_bain' => $this->bathrooms,
                'statut' => 'disponible',
                'description' => "Appartement {$numero} - {$this->getEtageLabelFor($etage)}",
            ]);
        }
    }

    /**
     * Génère un numéro d'appartement selon l'étage
     */
    private function genererNumeroAppartement($etage)
    {
        if ($etage === 0) {
            return 'RDC';
        }
        return "APP-{$etage}";
    }

    /**
     * Retourne le label de l'étage
     */
    private function getEtageLabelFor($etage)
    {
        $labels = [
            0 => 'Rez-de-chaussée',
            1 => '1er étage',
            2 => '2ème étage',
            3 => '3ème étage',
        ];

        return $labels[$etage] ?? $etage . 'ème étage';
    }

    /**
     * Obtenir les statistiques d'occupation pour un bien de type appartement
     */
    public function getOccupationStats()
    {
        // Vérifier si c'est un bien de catégorie "Appartement"
        if (!$this->category || $this->categorie_id !== 4) {
            return null;
        }

        $total = $this->appartements()->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'disponibles' => 0,
                'loues' => 0,
                'reserves' => 0,
                'maintenance' => 0,
                'taux_occupation' => 0
            ];
        }

        $disponibles = $this->appartements()->where('statut', 'disponible')->count();
        $loues = $this->appartements()->where('statut', 'loue')->count();
        $reserves = $this->appartements()->where('statut', 'reserve')->count();
        $maintenance = $this->appartements()->where('statut', 'maintenance')->count();

        $taux_occupation = $total > 0 ? round(($loues / $total) * 100, 1) : 0;

        return [
            'total' => $total,
            'disponibles' => $disponibles,
            'loues' => $loues,
            'reserves' => $reserves,
            'maintenance' => $maintenance,
            'taux_occupation' => $taux_occupation
        ];
    }

    /**
     * Mettre à jour le statut global du bien en fonction des appartements
     */
    public function updateStatutGlobal()
    {
        // ✅ Vérifier dynamiquement si c'est un immeuble
        $isImmeuble = $this->category &&
            strtolower($this->category->name) === 'appartement' &&
            $this->appartements()->count() > 0;

        if (!$isImmeuble) {
            Log::info('⚠️ updateStatutGlobal ignoré : pas un immeuble', [
                'bien_id' => $this->id,
                'categorie_id' => $this->categorie_id,
                'category_name' => $this->category ? $this->category->name : null,
                'nb_appartements' => $this->appartements()->count()
            ]);
            return;
        }

        $stats = $this->getOccupationStats();

        if (!$stats || $stats['total'] === 0) {
            Log::info('⚠️ Pas d\'appartements dans ce bien', [
                'bien_id' => $this->id
            ]);
            return;
        }

        $ancienStatut = $this->status;

        // ✅ Si au moins UN appartement est disponible → statut = 'disponible'
        if ($stats['disponibles'] > 0) {
            $this->update(['status' => 'disponible']);
            Log::info('🟢 Bien maintenu/remis à disponible', [
                'bien_id' => $this->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => 'disponible',
                'appartements_disponibles' => $stats['disponibles'],
                'stats' => $stats
            ]);
        } else {
            // Tous occupés
            $nouveauStatut = $stats['loues'] > 0 ? 'loue' : 'reserve';
            $this->update(['status' => $nouveauStatut]);
            Log::info('🔴 Tous appartements occupés - Bien non disponible', [
                'bien_id' => $this->id,
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'stats' => $stats
            ]);
        }
    }

    /**
     * Vérifier si un appartement spécifique est disponible
     */
    public function isAppartementDisponible($appartementId)
    {
        if ($this->categorie_id !== 4) {
            return false;
        }

        return $this->appartements()
            ->where('id', $appartementId)
            ->where('statut', 'disponible')
            ->exists();
    }

    /**
     * Obtenir tous les appartements disponibles
     */
    public function getAppartementsDisponibles()
    {
        return $this->appartements()
            ->where('statut', 'disponible')
            ->orderBy('etage')
            ->orderBy('numero')
            ->get();
    }
}
