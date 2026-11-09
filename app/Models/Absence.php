<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use BelongsToEtablissement;

    public const TYPES = ['absence' => 'Absence', 'retard' => 'Retard'];

    protected $fillable = [
        'etablissement_id', 'eleve_id', 'classe_id', 'date_absence', 'type',
        'duree_minutes', 'justifiee', 'motif', 'saisi_par',
    ];

    protected function casts(): array
    {
        return ['date_absence' => 'date', 'justifiee' => 'boolean'];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }
}