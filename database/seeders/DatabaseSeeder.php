<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Etablissement;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrateur EduSuivi',
            'email' => 'admin@edusuivi.test',
            'password' => 'password',
            'role' => Role::AdminGeneral,
        ]);

        $etablissements = [
            ['nom' => 'Collège Moderne de Divo', 'code' => 'CMD', 'ville' => 'Divo', 'slug' => 'cmd'],
            ['nom' => 'Collège Sainte-Marie', 'code' => 'CSM', 'ville' => 'Abidjan', 'slug' => 'csm'],
        ];

        foreach ($etablissements as $e) {
            $etab = Etablissement::create([
                'nom' => $e['nom'], 'code' => $e['code'], 'ville' => $e['ville'],
            ]);

            foreach ([
                ['Directeur ' . $e['code'], 'directeur', Role::Directeur],
                ['Enseignant ' . $e['code'], 'enseignant', Role::Enseignant],
                ['Parent ' . $e['code'], 'parent', Role::ParentEleve],
                ['Secrétariat ' . $e['code'], 'secretariat', Role::Administratif],
            ] as [$nom, $prefixe, $role]) {
                User::create([
                    'etablissement_id' => $etab->id,
                    'name' => $nom,
                    'email' => "{$prefixe}.{$e['slug']}@edusuivi.test",
                    'password' => 'password',
                    'role' => $role,
                ]);
            }
        }
    }
}