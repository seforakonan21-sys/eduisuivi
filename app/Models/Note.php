<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use BelongsToEtablissement;

    protected $fillable = ['etablissement_id', 'evaluation_id', 'eleve_id', 'valeur'];

    protected function casts(): array
    {
        return ['valeur' => 'decimal:2'];
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}