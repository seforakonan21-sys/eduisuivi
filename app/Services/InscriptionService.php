<?php

namespace App\Services;

use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use Illuminate\Validation\ValidationException;

class InscriptionService
{
    public function inscrire(Eleve $eleve, Classe $classe): Inscription
    {
        $dejaInscrit = Inscription::where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $classe->annee_scolaire_id)
            ->exists();

        if ($dejaInscrit) {
            throw ValidationException::withMessages([
                'classe_id' => 'Cet élève est déjà inscrit pour cette année scolaire.',
            ]);
        }

        if ($classe->effectif_max) {
            $effectif = Inscription::where('classe_id', $classe->id)->where('statut', 'inscrit')->count();

            if ($effectif >= $classe->effectif_max) {
                throw ValidationException::withMessages([
                    'classe_id' => "La classe {$classe->nom} est complète ({$classe->effectif_max} élèves).",
                ]);
            }
        }

        return Inscription::create([
            'etablissement_id' => $eleve->etablissement_id,
            'eleve_id' => $eleve->id,
            'classe_id' => $classe->id,
            'annee_scolaire_id' => $classe->annee_scolaire_id,
            'statut' => 'inscrit',
            'date_inscription' => now()->toDateString(),
        ]);
    }
}