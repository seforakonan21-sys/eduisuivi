<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inscription extends Model
{
    use BelongsToEtablissement;

    public const STATUTS = ['inscrit' => 'Inscrit', 'abandon' => 'Abandon', 'transfere' => 'Transféré'];

    protected $fillable = [
        'etablissement_id', 'eleve_id', 'classe_id', 'annee_scolaire_id', 'statut', 'date_inscription',
    ];

    protected function casts(): array
    {
        return ['date_inscription' => 'date'];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}