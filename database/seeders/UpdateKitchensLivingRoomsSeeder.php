<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bien;

class UpdateKitchensLivingRoomsSeeder extends Seeder
{
    public function run(): void
    {
        $biens = Bien::where('categorie_id', 4)->get();

        if ($biens->isEmpty()) {
            $this->command->info('❌ Aucun bien trouvé avec categorie_id = 4');
            return;
        }

        $this->command->info("✅ {$biens->count()} bien(s) trouvé(s)");

        $compteur = 0;

        foreach ($biens as $bien) {
            $valeur = ($compteur % 2 === 0) ? 1 : 2;

            $bien->update([
                'kitchens' => $valeur,
                'living_rooms' => $valeur,
            ]);

            $this->command->info("✓ Bien #{$bien->id} : kitchens={$valeur}, living_rooms={$valeur}");
            $compteur++;
        }

        $this->command->info("🎉 Mise à jour terminée !");
    }
}
