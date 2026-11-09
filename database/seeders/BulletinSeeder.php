<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\Appreciation;
use App\Models\Bulletin;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\Periode;
use Illuminate\Database\Seeder;

class BulletinSeeder extends Seeder
{
    public function run(): void
    {
        mt_srand(5);

        $phrases = [
            'Très bon travail, continue ainsi.', 'Des efforts appréciables.',
            'Peut mieux faire, doit se concentrer.', 'Résultats satisfaisants.',
            'Participe bien en classe.', 'Doit revoir les leçons régulièrement.',
        ];
        $generales = [
            'Élève sérieux(se) et appliqué(e).', "Bon trimestre, il faut maintenir l'effort.",
            'Des résultats fragiles : plus de travail personnel est attendu.',
        ];
        $decisions = ['Félicitations', 'Encouragements', 'Satisfaisant', 'Doit faire des efforts'];

        foreach (Etablissement::all() as $etab) {
            $etab->update([
                'adresse' => 'BP 123 ' . $etab->ville,
                'telephone' => '+225 27 00 00 00 00',
                'signataire_nom' => 'Le Directeur de ' . $etab->nom,
            ]);

            $periode = Periode::where('etablissement_id', $etab->id)->orderBy('ordre')->first();

            foreach (Affectation::where('etablissement_id', $etab->id)->get() as $affectation) {
                $eleveIds = Inscription::where('classe_id', $affectation->classe_id)
                    ->where('statut', 'inscrit')->pluck('eleve_id');

                foreach ($eleveIds as $eleveId) {
                    Appreciation::create([
                        'etablissement_id' => $etab->id, 'eleve_id' => $eleveId,
                        'affectation_id' => $affectation->id, 'periode_id' => $periode->id,
                        'texte' => $phrases[mt_rand(0, count($phrases) - 1)],
                    ]);
                }
            }

            $classe = Classe::where('etablissement_id', $etab->id)->orderBy('id')->first();
            $eleveIds = Inscription::where('classe_id', $classe->id)->where('statut', 'inscrit')->pluck('eleve_id');

            foreach ($eleveIds as $eleveId) {
                Bulletin::create([
                    'etablissement_id' => $etab->id, 'eleve_id' => $eleveId,
                    'classe_id' => $classe->id, 'periode_id' => $periode->id,
                    'appreciation_generale' => $generales[mt_rand(0, count($generales) - 1)],
                    'decision' => $decisions[mt_rand(0, count($decisions) - 1)],
                    'publie' => true, 'publie_le' => now(),
                ]);
            }
        }
    }
}