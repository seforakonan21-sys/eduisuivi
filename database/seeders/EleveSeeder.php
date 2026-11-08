<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Etablissement;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Database\Seeder;

class EleveSeeder extends Seeder
{
    public function run(): void
    {
        $noms = ['Kouassi', 'Konan', 'Yao', 'Koffi', 'Kouamé', "N'Guessan", 'Traoré', 'Coulibaly',
                 'Bamba', 'Ouattara', 'Diabaté', 'Soro'];
        $prenomsF = ['Awa', 'Adjoua', 'Aya', 'Fatou', 'Mariam', 'Affoué', 'Ahou', 'Nadège'];
        $prenomsM = ['Ange', 'Yves', 'Kevin', 'Serge', 'Ibrahim', 'Kouadio', 'Cédric', 'Jean-Marc'];

        $service = app(InscriptionService::class);

        foreach (Etablissement::all() as $etab) {
            $classes = Classe::where('etablissement_id', $etab->id)->orderBy('id')->take(2)->get();
            $parent = User::where('etablissement_id', $etab->id)->where('role', 'parent')->first();

            if ($classes->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < 12; $i++) {
                $feminin = $i % 2 === 0;

                $eleve = Eleve::create([
                    'etablissement_id' => $etab->id,
                    'matricule' => Eleve::genererMatricule($etab),
                    'nom' => $noms[$i % count($noms)],
                    'prenoms' => $feminin ? $prenomsF[$i % count($prenomsF)] : $prenomsM[$i % count($prenomsM)],
                    'date_naissance' => sprintf('%d-%02d-%02d', 2010 + ($i % 5), 1 + ($i % 12), 5 + $i),
                    'sexe' => $feminin ? 'F' : 'M',
                ]);

                $service->inscrire($eleve, $classes[$i % $classes->count()]);

                // Le parent de démo suit deux enfants (frères et sœurs)
                if ($parent && $i < 2) {
                    $eleve->parents()->attach($parent->id, ['lien' => 'mere']);
                }
            }
        }
    }
}