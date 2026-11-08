<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Periode extends Model
{
    use BelongsToEtablissement;

    protected $table = 'periodes';

    protected $fillable = ['etablissement_id', 'annee_scolaire_id', 'nom', 'ordre', 'date_debut', 'date_fin'];

    protected function casts(): array
    {
        return ['date_debut' => 'date', 'date_fin' => 'date'];
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}