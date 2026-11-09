<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Niveau extends Model
{
    use BelongsToEtablissement;

    public const CYCLES = [
        'primaire' => 'Primaire',
        'college'  => 'Collège',
        'lycee'    => 'Lycée',
    ];

    protected $table = 'niveaux';

    protected $fillable = ['etablissement_id', 'nom', 'cycle', 'bareme', 'ordre'];

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
}