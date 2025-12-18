<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
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
     * Obtenir le bien via la réservation
     */
    public function getBienAttribute()
    {
        return $this->reservation?->bien;
    }

    /**
     * Obtenir l'appartement via la réservation
     */
    public function getAppartementAttribute()
    {
        return $this->reservation?->appartement;
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
                'duree_min' => 1, // ✅ CORRECTION : Entre 3 jours et 3 mois (on utilise 1 mois minimum)
                'duree_max' => 3, // ✅ AJOUT : Maximum 3 mois
                'icon' => 'fa-couch',
                'color' => 'success',
                'description' => 'Location d\'un logement meublé de courte durée (3 jours à 3 mois).',
                'caracteristiques' => [
                    'Logement entièrement meublé',
                    'Durée : de 3 jours à 3 mois maximum',
                    'Idéal pour séjours temporaires',
                    'Préavis locataire : selon contrat',
                    'Dépôt de garantie : 1 mois maximum',
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

    /**
     * Obtenir le statut complet de la transaction pour cette location
     *
     * @return array Statut détaillé incluant paiement initial et loyers mensuels
     */
    public function getTransactionStatus(): array
    {
        // 1. Récupérer le paiement initial (caution + 1er mois)
        $paiementInitial = $this->paiements()
            ->where('type', 'location')
            ->first();

        // 2. Récupérer tous les paiements de loyers mensuels
        $paiementsLoyersMensuels = $this->paiements()
            ->where('type', 'loyer_mensuel')
            ->orderBy('created_at', 'asc')
            ->get();

        // 3. Récupérer le paiement de réservation (via la relation)
        $paiementReservation = $this->reservation?->paiements()
            ->where('type', 'reservation')
            ->where('statut', 'reussi')
            ->first();

        // 4. Calculer les dates et le nombre de mois
        $dateDebut = Carbon::parse($this->date_debut);
        $dateFin = Carbon::parse($this->date_fin);
        $aujourdhui = Carbon::now();

        // Nombre total de mois de location
        $totalMois = $dateDebut->diffInMonths($dateFin) + 1;

        // 5. Déterminer combien de mois ont été payés
        $moisPayes = 0;
        $moisEnRetard = 0;

        // Si paiement initial validé, ça couvre le premier mois
        if ($paiementInitial && $paiementInitial->statut === 'reussi') {
            $moisPayes += 1; // Premier mois couvert
        }

        // Ajouter les loyers mensuels payés
        $moisPayes += $paiementsLoyersMensuels
            ->where('statut', 'reussi')
            ->count();

        // 6. Calculer les montants
        $montantReservation = $paiementReservation ? $paiementReservation->montant_paye : 0;
        $montantPaiementInitial = $paiementInitial ? $paiementInitial->montant_paye : 0;
        $montantLoyersMensuels = $paiementsLoyersMensuels
            ->where('statut', 'reussi')
            ->sum('montant_paye');

        // Montant total attendu
        $montantTotal = ($totalMois * $this->loyer_mensuel) +
            $montantReservation +
            ($paiementInitial ? $paiementInitial->montant_total : 0);

        // Montant total payé
        $montantPaye = $montantReservation +
            $montantPaiementInitial +
            $montantLoyersMensuels;

        $montantRestant = max(0, $montantTotal - $montantPaye);

        // 7. Déterminer les mois en retard
        $currentDate = $dateDebut->copy()->startOfMonth();
        $indexMois = 0;

        while ($currentDate->lte($dateFin) && $currentDate->lte($aujourdhui)) {
            $dateEcheance = $currentDate->copy()->day(10);

            // Vérifier si ce mois a été payé
            $moisPaye = false;

            // Premier mois couvert par paiement initial
            if ($indexMois === 0 && $paiementInitial && $paiementInitial->statut === 'reussi') {
                $moisPaye = true;
            } else {
                // Vérifier dans les loyers mensuels
                $paiementPourCeMois = $paiementsLoyersMensuels->first(function($p) use ($currentDate) {
                    if ($p->mois_concerne) {
                        $moisPaiement = Carbon::parse($p->mois_concerne);
                        return $moisPaiement->year == $currentDate->year &&
                            $moisPaiement->month == $currentDate->month;
                    }

                    if ($p->created_at) {
                        $dateCreation = Carbon::parse($p->created_at);
                        return $dateCreation->year == $currentDate->year &&
                            $dateCreation->month == $currentDate->month;
                    }

                    return false;
                });

                $moisPaye = $paiementPourCeMois && $paiementPourCeMois->statut === 'reussi';
            }

            // Si le mois est échu et non payé, c'est un retard
            if (!$moisPaye && $aujourdhui->gt($dateEcheance)) {
                $moisEnRetard++;
            }

            $currentDate->addMonth();
            $indexMois++;
        }

        // 8. Déterminer le statut global
        $statut = 'en_attente'; // Par défaut

        if ($this->statut === 'terminee') {
            $statut = 'terminee';
        } elseif ($moisEnRetard > 0) {
            $statut = 'en_retard';
        } elseif ($paiementInitial && $paiementInitial->statut === 'reussi') {
            if ($montantRestant <= 0) {
                $statut = 'complet';
            } elseif ($moisPayes >= ($indexMois > 0 ? $indexMois : 1)) {
                $statut = 'a_jour';
            } else {
                $statut = 'partiel';
            }
        } elseif ($paiementInitial && in_array($paiementInitial->statut, ['en_attente', 'en_cours'])) {
            $statut = 'en_attente_paiement';
        }

        // 9. Paiements en attente
        $paiementsEnAttente = $this->paiements()
            ->whereIn('statut', ['en_attente', 'en_cours'])
            ->get();

        // 10. Prochaine échéance
        $prochaineEcheance = null;
        $prochainMoisAPayer = null;

        if ($this->statut === 'active' || $this->statut === 'en_retard') {
            $currentCheckDate = $dateDebut->copy()->startOfMonth();
            $checkIndex = 0;

            while ($currentCheckDate->lte($dateFin)) {
                // Vérifier si ce mois est payé
                $moisPaye = false;

                if ($checkIndex === 0 && $paiementInitial && $paiementInitial->statut === 'reussi') {
                    $moisPaye = true;
                } else {
                    $paiementPourCeMois = $paiementsLoyersMensuels->first(function($p) use ($currentCheckDate) {
                        if ($p->mois_concerne) {
                            $moisPaiement = Carbon::parse($p->mois_concerne);
                            return $moisPaiement->year == $currentCheckDate->year &&
                                $moisPaiement->month == $currentCheckDate->month &&
                                $p->statut === 'reussi';
                        }
                        return false;
                    });

                    $moisPaye = (bool) $paiementPourCeMois;
                }

                if (!$moisPaye) {
                    $prochaineEcheance = $currentCheckDate->copy()->day(10);
                    $prochainMoisAPayer = $currentCheckDate->format('Y-m');
                    break;
                }

                $currentCheckDate->addMonth();
                $checkIndex++;
            }
        }

        // 11. Retourner le statut complet
        return [
            // Statut général
            'statut' => $statut,
            'statut_location' => $this->statut,

            // Informations temporelles
            'date_debut' => $this->date_debut->format('Y-m-d'),
            'date_fin' => $this->date_fin->format('Y-m-d'),
            'total_mois' => $totalMois,
            'mois_ecoules' => min($totalMois, $dateDebut->diffInMonths($aujourdhui) + 1),

            // Statistiques de paiement
            'mois_payes' => $moisPayes,
            'mois_en_retard' => $moisEnRetard,
            'taux_paiement' => $totalMois > 0 ? round(($moisPayes / $totalMois) * 100, 2) : 0,

            // Montants
            'montant_total' => $montantTotal,
            'montant_paye' => $montantPaye,
            'montant_restant' => $montantRestant,
            'loyer_mensuel' => $this->loyer_mensuel,

            // Détail des paiements
            'paiement_reservation' => $montantReservation,
            'paiement_initial' => $montantPaiementInitial,
            'paiements_loyers_mensuels' => $montantLoyersMensuels,
            'paiement_initial_status' => $paiementInitial?->statut ?? 'non_cree',

            // Paiements
            'nombre_paiements_reussis' => $paiementsLoyersMensuels->where('statut', 'reussi')->count() +
                ($paiementInitial && $paiementInitial->statut === 'reussi' ? 1 : 0),
            'nombre_paiements_en_attente' => $paiementsEnAttente->count(),

            // Prochaine action
            'prochaine_echeance' => $prochaineEcheance?->format('Y-m-d'),
            'prochain_mois_a_payer' => $prochainMoisAPayer,
            'prochain_mois_libelle' => $prochainMoisAPayer ?
                Carbon::createFromFormat('Y-m', $prochainMoisAPayer)->translatedFormat('F Y') : null,

            // Actions possibles
            'peut_payer' => $this->statut === 'active' || $this->statut === 'en_retard',
            'peut_terminer' => $this->statut === 'active' && $moisEnRetard === 0,

            // Alertes
            'alertes' => [
                'retard' => $moisEnRetard > 0,
                'paiement_initial_manquant' => !$paiementInitial || $paiementInitial->statut !== 'reussi',
                'proche_fin' => $dateFin->diffInDays($aujourdhui) <= 30 && $aujourdhui->lte($dateFin),
            ],
        ];
    }
}
