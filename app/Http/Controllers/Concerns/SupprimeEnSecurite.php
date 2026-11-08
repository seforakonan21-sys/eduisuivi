<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

trait SupprimeEnSecurite
{
    protected function supprimer(Model $modele, string $messageEchec)
    {
        try {
            $modele->delete();
        } catch (QueryException) {
            return back()->withErrors(['suppression' => $messageEchec]);
        }

        return back()->with('succes', 'Élément supprimé.');
    }
}