<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Eleve;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EleveParentController extends Controller
{
    public function store(Request $request, Eleve $eleve)
    {
        $donnees = $request->validate([
            'parent_id' => ['required', Rule::exists('users', 'id')
                ->where('etablissement_id', $request->user()->etablissement_id)
                ->where('role', Role::ParentEleve->value)],
            'lien' => ['required', Rule::in(array_keys(Eleve::LIENS))],
        ]);

        $eleve->parents()->syncWithoutDetaching([
            $donnees['parent_id'] => ['lien' => $donnees['lien']],
        ]);

        return back()->with('succes', 'Parent lié à cet élève.');
    }

    public function destroy(Eleve $eleve, User $parent)
    {
        $eleve->parents()->detach($parent->id);

        return back()->with('succes', 'Lien parent retiré.');
    }
}