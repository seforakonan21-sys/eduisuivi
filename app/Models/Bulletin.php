<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bulletin extends Model
{
    use BelongsToEtablissement;

    public const DECISIONS = [
        "Tableau d'honneur", 'Félicitations', 'Encouragements', 'Satisfaisant',
        'Doit faire des efforts', 'Avertissement de travail', 'Avertissement de conduite', 'Blâme',
    ];

    protected $fillable = [
        'etablissement_id', 'eleve_id', 'classe_id', 'periode_id',
        'appreciation_generale', 'decision', 'publie', 'publie_le', 'saisi_par',
    ];

    protected function casts(): array
    {
        return ['publie' => 'boolean', 'publie_le' => 'datetime'];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }
}