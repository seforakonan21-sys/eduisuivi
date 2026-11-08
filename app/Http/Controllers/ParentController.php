<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParentController extends Controller
{
    public function index()
    {
        return view('parents.index', [
            'parents' => User::where('role', Role::ParentEleve->value)
                ->withCount('enfants')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $donnees = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'telephone' => ['nullable', 'string', 'max:20'],
        ]);

        $motDePasse = Str::password(10, true, true, false, false);

        $parent = User::create([
            ...$donnees,
            'password' => $motDePasse,
            'role' => Role::ParentEleve,
            'doit_changer_mot_de_passe' => true,
        ]);

        return redirect()->route('parents.index')
            ->with('succes', 'Compte parent créé.')
            ->with('parent_cree', $parent->email)
            ->with('mot_de_passe_temporaire', $motDePasse);
    }

    public function destroy(User $parent)
    {
        abort_unless($parent->role === Role::ParentEleve, 404);

        $parent->delete();

        return back()->with('succes', 'Compte parent supprimé.');
    }
}