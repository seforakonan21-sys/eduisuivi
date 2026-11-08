<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classe extends Model
{
    use BelongsToEtablissement;

    protected $table = 'classes';

    protected $fillable = ['etablissement_id', 'niveau_id', 'annee_scolaire_id', 'nom', 'effectif_max'];

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }
}