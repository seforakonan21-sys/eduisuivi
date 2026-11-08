<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnneeScolaire extends Model
{
    use BelongsToEtablissement;

    protected $table = 'annees_scolaires';

    protected $fillable = ['etablissement_id', 'libelle', 'date_debut', 'date_fin', 'est_active'];

    protected function casts(): array
    {
        return ['date_debut' => 'date', 'date_fin' => 'date', 'est_active' => 'boolean'];
    }

    public function periodes(): HasMany
    {
        return $this->hasMany(Periode::class)->orderBy('ordre');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }
}