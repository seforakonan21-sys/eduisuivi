<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Etablissement;
use App\Models\Inscription;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $annee = null;
        $stats = [];

        if ($user->aLeRole(Role::Directeur, Role::Administratif)) {
            $annee = AnneeScolaire::where('est_active', true)->first();

            $stats = [
                'Élèves inscrits' => $annee
                    ? Inscription::where('annee_scolaire_id', $annee->id)->where('statut', 'inscrit')->count()
                    : 0,
                'Classes' => $annee ? Classe::where('annee_scolaire_id', $annee->id)->count() : 0,
                'Enseignants' => User::where('role', Role::Enseignant->value)->count(),
                'Parents' => User::where('role', Role::ParentEleve->value)->count(),
            ];
        } elseif ($user->aLeRole(Role::AdminGeneral)) {
            $stats = [
                'Établissements' => Etablissement::count(),
                'Utilisateurs' => User::count(),
            ];
        }

        return view('dashboard', compact('user', 'annee', 'stats'));
    }
}