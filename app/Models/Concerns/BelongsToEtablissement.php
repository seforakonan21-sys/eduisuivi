<?php

namespace App\Models\Concerns;

use App\Models\Etablissement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToEtablissement
{
    protected static function bootBelongsToEtablissement(): void
    {
        // Un utilisateur rattaché à un établissement ne voit que ses données.
        // L'admin général (etablissement_id null) voit tout.
        // Auth::hasUser() évite une boucle infinie quand Laravel charge l'utilisateur connecté.
        static::addGlobalScope('etablissement', function (Builder $query) {
            if (Auth::hasUser() && Auth::user()->etablissement_id) {
                $query->where(
                    $query->getModel()->getTable() . '.etablissement_id',
                    Auth::user()->etablissement_id
                );
            }
        });

        static::creating(function ($model) {
            if (! $model->etablissement_id && Auth::hasUser() && Auth::user()->etablissement_id) {
                $model->etablissement_id = Auth::user()->etablissement_id;
            }
        });
    }

    public function etablissement(): BelongsTo
    {
        return $this->belongsTo(Etablissement::class);
    }
}