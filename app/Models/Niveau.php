<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    use BelongsToEtablissement;

    protected $table = 'niveaux';

    protected $fillable = ['etablissement_id', 'nom', 'ordre'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
}