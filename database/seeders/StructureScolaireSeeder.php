<?php

namespace Database\Seeders;

use App\Models\Affectation;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Database\Seeder;

class StructureScolaireSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Etablissement::all() as $etab) {
            $id = $etab->id;

            $annee = AnneeScolaire::create([
                'etablissement_id' => $id, 'libelle' => '2026-2027',
                'date_debut' => '2026-09-14', 'date_fin' => '2027-06-30', 'est_active' => true,
            ]);

            $trimestres = [
                ['Trimestre 1', '2026-09-14', '2026-12-18'],
                ['Trimestre 2', '2027-01-04', '2027-03-26'],
                ['Trimestre 3', '2027-04-12', '2027-06-30'],
            ];
            foreach ($trimestres as $i => [$nom, $debut, $fin]) {
                Periode::create([
                    'etablissement_id' => $id, 'annee_scolaire_id' => $annee->id,
                    'nom' => $nom, 'ordre' => $i + 1, 'date_debut' => $debut, 'date_fin' => $fin,
                ]);
            }

            $niveaux = collect(['6ème', '5ème', '4ème', '3ème', '2nde', '1ère', 'Terminale'])
                ->map(fn ($nom, $i) => Niveau::create([
                    'etablissement_id' => $id, 'nom' => $nom, 'ordre' => $i + 1,
                ]));

            foreach ($niveaux->take(4) as $niveau) {
                foreach (['A', 'B'] as $lettre) {
                    Classe::create([
                        'etablissement_id' => $id, 'niveau_id' => $niveau->id,
                        'annee_scolaire_id' => $annee->id,
                        'nom' => "{$niveau->nom} {$lettre}", 'effectif_max' => 50,
                    ]);
                }
            }

            $matieres = collect([
                ['Mathématiques', 'MATH'], ['Français', 'FRA'], ['Anglais', 'ANG'],
                ['Histoire-Géographie', 'HG'], ['SVT', 'SVT'], ['Physique-Chimie', 'PC'],
                ['EPS', 'EPS'], ['Éducation civique', 'EDHC'],
            ])->map(fn ($m) => Matiere::create([
                'etablissement_id' => $id, 'nom' => $m[0], 'code' => $m[1],
            ]));

            // Affectations de démonstration pour la première classe
            $enseignant = User::where('etablissement_id', $id)->where('role', 'enseignant')->first();
            $classe = Classe::where('etablissement_id', $id)->orderBy('id')->first();

            if ($enseignant && $classe) {
                foreach ($matieres->take(2) as $matiere) {
                    Affectation::create([
                        'etablissement_id' => $id, 'classe_id' => $classe->id,
                        'matiere_id' => $matiere->id, 'enseignant_id' => $enseignant->id,
                        'coefficient' => 4,
                    ]);
                }
            }
        }
    }
}