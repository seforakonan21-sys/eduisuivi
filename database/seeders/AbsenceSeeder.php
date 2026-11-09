<?php

namespace Database\Seeders;

use App\Models\Absence;
use App\Models\Appel;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Inscription;
use Illuminate\Database\Seeder;

class AbsenceSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(11); // résultats reproductibles

        foreach (Etablissement::all() as $etab) {
            $classeIds = Inscription::where('etablissement_id', $etab->id)->distinct()->pluck('classe_id');

            foreach (Classe::whereIn('id', $classeIds)->get() as $classe) {
                $eleveIds = Inscription::where('classe_id', $classe->id)
                    ->where('statut', 'inscrit')->pluck('eleve_id');

                // Appel des 14 derniers jours (hors week-ends)
                for ($j = 14; $j >= 1; $j--) {
                    $date = now()->subDays($j);

                    if ($date->isWeekend()) {
                        continue;
                    }

                    Appel::create([
                        'etablissement_id' => $etab->id, 'classe_id' => $classe->id,
                        'date_appel' => $date->toDateString(),
                    ]);

                    foreach ($eleveIds as $eleveId) {
                        $tirage = mt_rand(1, 100);

                        if ($tirage > 13) {
                            continue; // présent
                        }

                        $retard = $tirage > 8;
                        $justifiee = ! $retard && mt_rand(1, 100) <= 40;

                        Absence::create([
                            'etablissement_id' => $etab->id, 'eleve_id' => $eleveId,
                            'classe_id' => $classe->id, 'date_absence' => $date->toDateString(),
                            'type' => $retard ? 'retard' : 'absence',
                            'duree_minutes' => $retard ? mt_rand(5, 40) : null,
                            'justifiee' => $justifiee,
                            'motif' => $justifiee ? 'Maladie (certificat médical)' : null,
                        ]);
                    }
                }
            }
        }
    }
}