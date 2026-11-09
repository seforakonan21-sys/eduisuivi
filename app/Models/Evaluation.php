<?php

namespace App\Models;

use App\Enums\Role;
use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use BelongsToEtablissement;

    public const TYPES = ['devoir' => 'Devoir', 'composition' => 'Composition'];
    public const STATUTS = ['brouillon' => 'Brouillon', 'valide' => 'Validé'];

    protected $fillable = [
        'etablissement_id', 'affectation_id', 'periode_id', 'titre',
        'type', 'note_max', 'date_evaluation', 'statut',
    ];

    protected function casts(): array
    {
        return ['date_evaluation' => 'date'];
    }

    public function affectation(): BelongsTo
    {
        return $this->belongsTo(Affectation::class);
    }

    public function periode(): BelongsTo
    {
        return $this->belongsTo(Periode::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function estValidee(): bool
    {
        return $this->statut === 'valide';
    }

    /** Le directeur accède à tout ; l'enseignant seulement à ses propres évaluations. */
    public function accessiblePar(User $user): bool
    {
        if ($user->aLeRole(Role::Directeur)) {
            return true;
        }

        return $user->aLeRole(Role::Enseignant) && $this->affectation->enseignant_id === $user->id;
    }
}