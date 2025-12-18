<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'bien_id',
        'appartement_id',
        'montant',
        'type_montant',
        'statut',
        'paiement_id',
        'date_reservation',
        'motif_rejet'
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_reservation' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================== RELATIONS ====================

    public function appartement()
    {
        return $this->belongsTo(Appartement::class);
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function bien()
    {
        return $this->belongsTo(Bien::class);
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class);
    }

    public function paiements()
    {
        return $this->HasMany(Paiement::class);
    }

    public function vente()
    {
        return $this->hasOne(Vente::class, 'reservation_id');
    }

    public function location()
    {
        return $this->hasOne(Location::class, 'reservation_id');
    }

    // ==================== MÉTHODES DE CALCUL ====================

    /**
     * Calculer le montant initial selon le type de transaction
     * Pour VENTE : Acompte (10% du prix)
     * Pour LOCATION : Dépôt de garantie (1 mois de loyer)
     */
    public function calculateMontantInitial(): float
    {
        if (!$this->bien || !$this->bien->mandat) {
            throw new \Exception('Impossible de calculer le montant : le bien n\'a pas de mandat valide.');
        }

        return match($this->bien->mandat->type_mandat) {
            'vente' => $this->bien->price * 0.10,
            'gestion_locative' => $this->bien->price,
            default => throw new \Exception('Type de mandat inconnu : ' . $this->bien->mandat->type_mandat)
        };
    }

    /**
     * Obtenir le texte explicatif du montant initial
     */
    public function getMontantInitialText(): string
    {
        if (!$this->bien || !$this->bien->mandat) {
            return 'Erreur : Ce bien n\'a pas de mandat valide.';
        }

        $montant = number_format($this->calculateMontantInitial(), 0, ',', ' ');

        return match($this->bien->mandat->type_mandat) {
            'vente' => "Acompte de réservation : 10% du prix de vente ({$montant} FCFA). Les 90% restants (" .
                number_format($this->getMontantRestantAchat(), 0, ',', ' ') .
                " FCFA) seront à régler lors de la signature définitive de l'acte de vente.",
            'gestion_locative' => "Dépôt de garantie (caution) : {$montant} FCFA (équivalent à 1 mois de loyer). " .
                "Cette somme vous sera restituée en fin de bail, déduction faite des éventuels dommages.",
            default => 'Erreur : Type de mandat non reconnu.'
        };
    }

    /**
     * Obtenir le type de montant (pour clarté)
     */
    public function getTypeMontant(): string
    {
        if ($this->type_montant) {
            return $this->type_montant;
        }

        if (!$this->bien || !$this->bien->mandat) {
            return 'Indéfini';
        }

        return match($this->bien->mandat->type_mandat) {
            'vente' => 'acompte',
            'gestion_locative' => 'depot_garantie',
            default => 'Indéfini'
        };
    }

    /**
     * Obtenir le label court du type de montant
     */
    public function getTypeMontantLabel(): string
    {
        if (!$this->bien || !$this->bien->mandat) {
            return 'Indéfini';
        }

        return match($this->bien->mandat->type_mandat) {
            'vente' => 'Acompte (10%)',
            'gestion_locative' => 'Caution (1 mois)',
            default => 'Indéfini'
        };
    }

    /**
     * Vérifier si le montant initial a été payé
     */
    public function isMontantInitialPaye(): bool
    {
        return $this->paiement &&
            $this->paiement->statut === 'reussi' &&
            $this->paiement->montant_restant <= 0;
    }

    /**
     * Obtenir le montant restant à payer pour l'achat (90%)
     * Uniquement pour les ventes
     */
    public function getMontantRestantAchat(): ?float
    {
        if (!$this->bien || !$this->bien->mandat) {
            return null;
        }

        if ($this->bien->mandat->type_mandat !== 'vente') {
            return null;
        }

        $prixTotal = $this->bien->price;
        $acompteVerse = $prixTotal * 0.10;

        return $prixTotal - $acompteVerse;
    }

    /**
     * Vérifier si la réservation peut être créée (mandat valide)
     */
    public function canBeCreated(): bool
    {
        if (!$this->bien || !$this->bien->mandat) {
            return false;
        }

        $typeMandat = $this->bien->mandat->type_mandat;
        return in_array($typeMandat, ['vente', 'gestion_locative']);
    }

    /**
     * Obtenir les informations complètes sur le paiement
     */
    public function getInfoPaiement(): array
    {
        $mandat = $this->bien?->mandat;

        if (!$mandat) {
            return [
                'type' => 'invalide',
                'montant_initial' => null,
                'label' => 'Erreur',
                'description' => 'Aucun mandat valide pour ce bien',
                'error' => true
            ];
        }

        if ($mandat->type_mandat === 'vente') {
            return [
                'type' => 'acompte',
                'montant_initial' => $this->bien->price * 0.10,
                'montant_restant' => $this->bien->price * 0.90,
                'prix_total' => $this->bien->price,
                'pourcentage_initial' => 10,
                'pourcentage_restant' => 90,
                'label' => 'Acompte de vente',
                'description' => '10% du prix de vente versé en acompte',
                'error' => false
            ];
        }

        if ($mandat->type_mandat === 'gestion_locative') {
            return [
                'type' => 'depot_garantie',
                'montant_initial' => $this->bien->price,
                'loyer_mensuel' => $this->bien->price,
                'nombre_mois' => 1,
                'label' => 'Dépôt de garantie',
                'description' => 'Caution équivalente à 1 mois de loyer',
                'restituable' => true,
                'error' => false
            ];
        }

        return [
            'type' => 'invalide',
            'montant_initial' => null,
            'label' => 'Erreur',
            'description' => 'Type de mandat non reconnu',
            'error' => true
        ];
    }
}
