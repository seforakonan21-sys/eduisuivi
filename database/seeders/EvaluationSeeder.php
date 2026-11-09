<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\Evaluation;
use App\Models\Inscription;
use App\Models\Note;
use App\Models\Periode;
use Illuminate\Database\Seeder;

class EvaluationSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(2026); // notes reproductibles

        foreach (Etablissement::all() as $etab) {
            $periode = Periode::where('etablissement_id', $etab->id)->orderBy('ordre')->first();
            $affectations = Affectation::with('classe.niveau')->where('etablissement_id', $etab->id)->get();

            foreach ($affectations as $affectation) {
                $noteMax = (int) $affectation->classe->niveau->bareme;

                $ids = Inscription::where('classe_id', $affectation->classe_id)
                    ->where('statut', 'inscrit')->pluck('eleve_id');
                $eleves = Eleve::whereIn('id', $ids)->get();

                $evaluations = [
                    ['Devoir 1', 'devoir', '2026-10-12'],
                    ['Composition du 1er trimestre', 'composition', '2026-12-10'],
                ];

                foreach ($evaluations as [$titre, $type, $date]) {
                    $evaluation = Evaluation::create([
                        'etablissement_id' => $etab->id, 'affectation_id' => $affectation->id,
                        'periode_id' => $periode->id, 'titre' => $titre, 'type' => $type,
                        'note_max' => $noteMax, 'date_evaluation' => $date, 'statut' => 'valide',
                    ]);

                    foreach ($eleves as $eleve) {
                        Note::create([
                            'etablissement_id' => $etab->id, 'evaluation_id' => $evaluation->id,
                            'eleve_id' => $eleve->id,
                            'valeur' => round(mt_rand(30, 98) / 100 * $noteMax * 2) / 2,
                        ]);
                    }
                }
            }
        }
    }
}