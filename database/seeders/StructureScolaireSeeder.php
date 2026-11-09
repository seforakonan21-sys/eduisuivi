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
            $primaire = $etab->code === 'EPP';

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

            // Niveaux : CP1 à CM2 pour l'école primaire, 6ème à Terminale pour le secondaire
            $liste = $primaire
                ? [['CP1', 'primaire'], ['CP2', 'primaire'], ['CE1', 'primaire'],
                   ['CE2', 'primaire'], ['CM1', 'primaire'], ['CM2', 'primaire']]
                : [['6ème', 'college'], ['5ème', 'college'], ['4ème', 'college'], ['3ème', 'college'],
                   ['2nde', 'lycee'], ['1ère', 'lycee'], ['Terminale', 'lycee']];

            $niveaux = collect($liste)->map(fn ($n, $i) => Niveau::create([
                'etablissement_id' => $id, 'nom' => $n[0], 'cycle' => $n[1], 'ordre' => $i + 1,
            ]));

            // Classes : A et B pour chaque niveau du primaire, pour les 4 premiers du secondaire
            foreach ($primaire ? $niveaux : $niveaux->take(4) as $niveau) {
                foreach (['A', 'B'] as $lettre) {
                    Classe::create([
                        'etablissement_id' => $id, 'niveau_id' => $niveau->id,
                        'annee_scolaire_id' => $annee->id,
                        'nom' => "{$niveau->nom} {$lettre}", 'effectif_max' => $primaire ? 60 : 50,
                    ]);
                }
            }

            // Matières (à ajuster avec les vrais programmes)
            $listeMatieres = $primaire
                ? [['Français', 'FRA'], ['Mathématiques', 'MATH'], ['Éveil scientifique', 'EVEIL'],
                   ['Éducation civique', 'EDHC'], ['EPS', 'EPS']]
                : [['Mathématiques', 'MATH'], ['Français', 'FRA'], ['Anglais', 'ANG'],
                   ['Histoire-Géographie', 'HG'], ['SVT', 'SVT'], ['Physique-Chimie', 'PC'],
                   ['EPS', 'EPS'], ['Éducation civique', 'EDHC']];

            $matieres = collect($listeMatieres)->map(fn ($m) => Matiere::create([
                'etablissement_id' => $id, 'nom' => $m[0], 'code' => $m[1],
            ]));

            // Affectations de démonstration pour la première classe
            $enseignant = User::where('etablissement_id', $id)->where('role', 'enseignant')->first();
            $classe = Classe::where('etablissement_id', $id)->orderBy('id')->first();

            if ($enseignant && $classe) {
                // Primaire : le maître de classe enseigne toutes les matières
                foreach ($primaire ? $matieres : $matieres->take(2) as $matiere) {
                    Affectation::create([
                        'etablissement_id' => $id, 'classe_id' => $classe->id,
                        'matiere_id' => $matiere->id, 'enseignant_id' => $enseignant->id,
                        'coefficient' => $primaire ? 1 : 4,
                    ]);
                }
            }
        }
    }
}