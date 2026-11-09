<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class EtablissementController extends Controller
{
    public function edit(Request $request)
    {
        return view('etablissement.edit', ['etablissement' => $request->user()->etablissement]);
    }

    public function update(Request $request)
    {
        $etablissement = $request->user()->etablissement;

        $donnees = $request->validate([
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'signataire_nom' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
            'cachet' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $champs = Arr::except($donnees, ['logo', 'cachet']);

        foreach (['logo', 'cachet'] as $image) {
            if ($request->hasFile($image)) {
                if ($etablissement->{$image}) {
                    Storage::disk('public')->delete($etablissement->{$image});
                }
                $champs[$image] = $request->file($image)->store('etablissements', 'public');
            }
        }

        $etablissement->update($champs);

        return back()->with('succes', "Informations de l'établissement enregistrées.");
    }
}