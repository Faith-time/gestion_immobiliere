<?php

namespace Database\Seeders;

use App\Models\Bien;
use App\Models\Mandat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BiensWithMandatsSeeder extends Seeder
{
    // Commission fixe comme dans le BienController
    private const COMMISSION_PERCENTAGE = 10;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // Données de base pour les biens
        $villes = ['Dakar', 'Pikine', 'Guédiawaye', 'Rufisque', 'Thiès', 'Kaolack'];
        $typesMandat = ['vente', 'gestion_locative'];
        $typesMandatVente = ['exclusif', 'simple', 'semi_exclusif'];
        $statuts = ['disponible', 'en_validation'];

        // Titres et descriptions variés
        $titres = [
            'Villa moderne avec piscine',
            'Appartement standing centre-ville',
            'Maison familiale avec jardin',
            'Duplex résidentiel',
            'Villa avec vue mer',
            'Appartement neuf 3 pièces',
            'Maison traditionnelle rénovée',
            'Penthouse avec terrasse',
            'Villa contemporaine',
            'Appartement lumineux'
        ];

        $descriptions = [
            'Magnifique propriété avec toutes les commodités modernes, idéale pour une famille.',
            'Bien situé dans un quartier calme et résidentiel, proche des écoles et commerces.',
            'Propriété exceptionnelle offrant confort et élégance dans un cadre privilégié.',
            'Bien immobilier de qualité avec finitions haut de gamme et équipements modernes.',
            'Emplacement de choix dans un environnement paisible et sécurisé.',
            'Bien rénové avec goût, alliant charme traditionnel et confort moderne.',
            'Propriété spacieuse avec de belles prestations dans un quartier recherché.',
            'Bien d\'exception offrant un cadre de vie exceptionnel et des prestations de qualité.'
        ];

        $adresses = [
            'Parcelles Assainies, Unité 15',
            'HLM Grand Yoff',
            'Mermoz Pyrotechnie',
            'Point E',
            'Fann Résidence',
            'Almadies',
            'Ngor',
            'Yoff Layène',
            'Sicap Liberté',
            'Plateau'
        ];

        // Créer 20 biens avec leurs mandats
        for ($i = 1; $i <= 20; $i++) {
            // Données aléatoires pour le bien
            $proprietaireId = rand(10, 15);
            $categorieId = rand(4, 5);
            $typeMandat = $typesMandat[array_rand($typesMandat)];
            $price = rand(15000000, 100000000); // Entre 15M et 100M FCFA
            $superficy = rand(50, 500);
            $rooms = rand(2, 8);
            $floors = rand(1, 3);
            $bathrooms = rand(1, 4);

            // Créer le bien
            $bien = Bien::create([
                'title' => $titres[($i - 1) % count($titres)] . ' #' . $i,
                'description' => $descriptions[array_rand($descriptions)],
                'image' => 'biens/demo_bien_' . $i . '.jpg', // Image fictive
                'rooms' => $rooms,
                'floors' => $floors,
                'bathrooms' => $bathrooms,
                'city' => $villes[array_rand($villes)],
                'address' => $adresses[array_rand($adresses)] . ', Villa ' . $i,
                'superficy' => $superficy,
                'price' => $price,
                'status' => $statuts[array_rand($statuts)],
                'categorie_id' => $categorieId,
                'proprietaire_id' => $proprietaireId,
                'property_title' => 'documents/titre_propriete_' . $i . '.pdf', // Document fictif
            ]);

            // Calculer la commission
            $commissionFixe = ($price * self::COMMISSION_PERCENTAGE) / 100;

            // Dates du mandat : entre aujourd'hui et dans 1 an
            $dateDebut = Carbon::now()->subDays(rand(0, 60))->format('Y-m-d');
            $dateFin = Carbon::parse($dateDebut)->addYear()->format('Y-m-d');

            // Déterminer le statut du mandat selon le statut du bien
            $statutMandat = $bien->status === 'disponible' ? 'actif' : 'en_attente';

            // Données du mandat
            $mandatData = [
                'bien_id' => $bien->id,
                'type_mandat' => $typeMandat,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'commission_pourcentage' => self::COMMISSION_PERCENTAGE,
                'commission_fixe' => $commissionFixe,
                'statut' => $statutMandat,
                'signature_status' => 'non_signe',
            ];

            // Ajouter le type de mandat de vente si c'est une vente
            if ($typeMandat === 'vente') {
                $mandatData['type_mandat_vente'] = $typesMandatVente[array_rand($typesMandatVente)];
            }

            // Note: conditions_particulieres supprimé car la colonne n'existe pas dans la DB

            // Si le mandat est actif, générer parfois un PDF fictif
            if ($statutMandat === 'actif' && rand(1, 2) === 1) {
                $mandatData['pdf_path'] = 'mandats/mandat_' . $typeMandat . '_' . $bien->id . '.pdf';
                $mandatData['pdf_generated_at'] = Carbon::parse($dateDebut)->addDays(1);
            }

            // Créer le mandat
            $mandat = Mandat::create($mandatData);

            // Ajouter parfois des signatures (seulement pour les mandats actifs)
            if ($statutMandat === 'actif' && rand(1, 4) === 1) {
                $signatureChance = rand(1, 3);

                if ($signatureChance === 1) {
                    // Signature propriétaire seulement
                    $mandat->update([
                        'proprietaire_signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                        'proprietaire_signed_at' => Carbon::parse($dateDebut)->addDays(2),
                        'proprietaire_signature_ip' => '192.168.1.' . rand(1, 254),
                        'signature_status' => 'partiellement_signe',
                    ]);
                } elseif ($signatureChance === 2) {
                    // Signatures complètes
                    $mandat->update([
                        'proprietaire_signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                        'proprietaire_signed_at' => Carbon::parse($dateDebut)->addDays(2),
                        'proprietaire_signature_ip' => '192.168.1.' . rand(1, 254),
                        'agence_signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
                        'agence_signed_at' => Carbon::parse($dateDebut)->addDays(3),
                        'agence_signature_ip' => '10.0.0.' . rand(1, 254),
                        'signature_status' => 'entierement_signe',
                        'signed_pdf_path' => 'mandats/mandat_' . $typeMandat . '_' . $bien->id . '_signe.pdf',
                        'final_pdf_generated_at' => Carbon::parse($dateDebut)->addDays(3),
                    ]);
                }
            }

            echo "Bien #{$i} créé avec mandat {$typeMandat}" .
                ($typeMandat === 'vente' ? " {$mandatData['type_mandat_vente']}" : '') .
                " - Propriétaire: {$proprietaireId}, Catégorie: {$categorieId}, Prix: " .
                number_format($price, 0, ',', ' ') . " FCFA\n";
        }

        echo "\n✅ 20 biens avec mandats créés avec succès !\n";
        echo "📊 Répartition :\n";
        echo "   - Propriétaires : ID 10 à 15\n";
        echo "   - Catégories : ID 4 à 5\n";
        echo "   - Types de mandat : vente et gestion locative\n";
        echo "   - Statuts variés : disponible et en_validation\n";
        echo "   - Quelques mandats avec signatures simulées\n";
    }
}
