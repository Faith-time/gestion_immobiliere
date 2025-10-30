<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bien;
use App\Models\Appartement;
use App\Models\Categorie;
use App\Models\Image;
use Illuminate\Support\Facades\DB;

class AppartementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Génère automatiquement des appartements pour tous les biens
     * ayant la catégorie "Appartement" (categorie_id = 5)
     *
     * Logique:
     * - Pour chaque bien de type appartement
     * - Créer (nombre d'étages + 1) appartements
     * - Du RDC (étage 0) au dernier étage (sans terrasse)
     * - Répartir équitablement la superficie totale
     * - Répartir les images entre les appartements
     */
    public function run(): void
    {
        $this->command->info('🏢 Début de la génération des appartements...');

        DB::beginTransaction();

        try {
            // 1. Récupérer la catégorie "Appartement"
            $categorieAppartement = Categorie::where('id', 5)->first();

            if (!$categorieAppartement) {
                $this->command->error('❌ Catégorie avec ID 5 introuvable!');
                $this->command->info('💡 Recherche par nom "Appartement"...');

                $categorieAppartement = Categorie::whereRaw('LOWER(name) = ?', ['appartement'])->first();

                if (!$categorieAppartement) {
                    $this->command->error('❌ Aucune catégorie "Appartement" trouvée dans la base de données.');
                    return;
                }
            }

            $this->command->info("✅ Catégorie trouvée: {$categorieAppartement->name} (ID: {$categorieAppartement->id})");

            // 2. Récupérer tous les biens de cette catégorie
            $biens = Bien::where('categorie_id', $categorieAppartement->id)
                ->with('images')
                ->get();

            if ($biens->isEmpty()) {
                $this->command->warn('⚠️  Aucun bien de type "Appartement" trouvé dans la base de données.');
                return;
            }

            $this->command->info("📊 {$biens->count()} bien(s) de type Appartement trouvé(s)");

            $totalAppartementsGeneres = 0;

            // 3. Générer les appartements pour chaque bien
            foreach ($biens as $bien) {
                $this->command->info("\n🏠 Traitement du bien: {$bien->title}");

                // Vérifier si le bien a des étages
                if ($bien->floors === null || $bien->floors < 0) {
                    $this->command->warn("  ⚠️  Bien #{$bien->id} sans étages définis - ignoré");
                    continue;
                }

                // Supprimer les appartements existants pour ce bien (si relance du seeder)
                $appartementsExistants = $bien->appartements()->count();
                if ($appartementsExistants > 0) {
                    $this->command->warn("  🗑️  Suppression de {$appartementsExistants} appartement(s) existant(s)");
                    $bien->appartements()->delete();
                }

                // Calculer le nombre d'appartements à créer
                // floors = nombre d'étages au-dessus du RDC
                // Donc: RDC (0) + floors = floors + 1 appartements
                $nombreAppartements = $bien->floors + 1;
                $this->command->info("  📐 Nombre d'étages: {$bien->floors} → {$nombreAppartements} appartements à créer");

                // Répartir équitablement la superficie
                $superficieParAppartement = $bien->superficy / $nombreAppartements;

                // Récupérer les images du bien (sans appartement_id)
                $imagesGenerales = $bien->images()->whereNull('appartement_id')->get();
                $nombreImages = $imagesGenerales->count();

                // 4. Créer chaque appartement
                for ($etage = 0; $etage <= $bien->floors; $etage++) {
                    // Générer le numéro d'appartement
                    $numero = sprintf('APT-%03d', $etage + 1);

                    // Calculer les caractéristiques par défaut
                    $chambresParAppartement = $bien->rooms
                        ? max(1, floor($bien->rooms / max(1, $nombreAppartements)))
                        : 1;

                    $sallesDeBainParAppartement = $bien->bathrooms
                        ? max(1, floor($bien->bathrooms / max(1, $nombreAppartements)))
                        : 1;

                    // Créer l'appartement
                    $appartement = Appartement::create([
                        'bien_id' => $bien->id,
                        'numero' => $numero,
                        'etage' => $etage,
                        'superficie' => round($superficieParAppartement, 2),
                        'pieces' => $bien->rooms ?: 1,
                        'chambres' => $chambresParAppartement,
                        'salles_bain' => $sallesDeBainParAppartement,
                        'statut' => 'disponible',
                        'description' => $this->getEtageLabel($etage),
                    ]);

                    $this->command->info("    ✅ {$numero} - {$this->getEtageLabel($etage)} créé");

                    // 5. Répartir les images entre les appartements
                    if ($nombreImages > 0 && $imagesGenerales->isNotEmpty()) {
                        // Calculer combien d'images attribuer à cet appartement
                        $imagesParAppartement = floor($nombreImages / $nombreAppartements);
                        $reste = $nombreImages % $nombreAppartements;

                        // Les premiers appartements reçoivent une image supplémentaire si reste
                        $nombreImagesPourCetAppartement = $imagesParAppartement;
                        if ($etage < $reste) {
                            $nombreImagesPourCetAppartement++;
                        }

                        // Récupérer les images à attribuer
                        $indexDebut = $etage * $imagesParAppartement + min($etage, $reste);
                        $imagesAAttribuer = $imagesGenerales->slice($indexDebut, $nombreImagesPourCetAppartement);

                        // Attribuer les images à cet appartement
                        foreach ($imagesAAttribuer as $image) {
                            $image->update([
                                'appartement_id' => $appartement->id,
                                'libelle' => $image->libelle ?: "{$numero} - Image"
                            ]);
                        }

                        if ($imagesAAttribuer->count() > 0) {
                            $this->command->info("       📸 {$imagesAAttribuer->count()} image(s) attribuée(s)");
                        }
                    }

                    $totalAppartementsGeneres++;
                }

                $this->command->info("  ✔️  {$nombreAppartements} appartements créés pour '{$bien->title}'");
            }

            DB::commit();

            $this->command->info("\n" . str_repeat('=', 70));
            $this->command->info("✨ GÉNÉRATION TERMINÉE AVEC SUCCÈS!");
            $this->command->info("📊 Statistiques:");
            $this->command->info("   • Biens traités: {$biens->count()}");
            $this->command->info("   • Appartements générés: {$totalAppartementsGeneres}");
            $this->command->info(str_repeat('=', 70));

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error("\n❌ ERREUR lors de la génération: " . $e->getMessage());
            $this->command->error("Trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Retourne le label de l'étage (fonction générique)
     *
     * @param int $etage
     * @return string
     */
    private function getEtageLabel(int $etage): string
    {
        // Cas spécial: Rez-de-chaussée
        if ($etage === 0) {
            return 'Rez-de-chaussée';
        }

        // Cas spécial: 1er étage
        if ($etage === 1) {
            return '1er étage';
        }

        // Pour tous les autres étages: 2ème, 3ème, 4ème, 5ème, etc.
        return $etage . 'ème étage';
    }
}
