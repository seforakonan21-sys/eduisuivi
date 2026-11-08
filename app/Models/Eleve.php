<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Eleve extends Model
{
    use BelongsToEtablissement;

    public const LIENS = ['pere' => 'Père', 'mere' => 'Mère', 'tuteur' => 'Tuteur / Tutrice'];

    protected $fillable = [
        'etablissement_id', 'matricule', 'nom', 'prenoms', 'date_naissance',
        'sexe', 'photo', 'telephone', 'adresse',
    ];

    protected function casts(): array
    {
        return ['date_naissance' => 'date'];
    }

    public function nomComplet(): string
    {
        return trim($this->nom . ' ' . $this->prenoms);
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class)
            ->orderByDesc('date_inscription')
            ->orderByDesc('id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'eleve_parent', 'eleve_id', 'parent_id')
            ->withPivot('lien')
            ->withTimestamps();
    }

    /** Matricule du type CMD26-0001 : code de l'établissement + année + numéro. */
    public static function genererMatricule(Etablissement $etab): string
    {
        $prefixe = $etab->code . now()->format('y');

        $dernier = static::withoutGlobalScopes()
            ->where('etablissement_id', $etab->id)
            ->where('matricule', 'like', $prefixe . '-%')
            ->max('matricule');

        $numero = $dernier ? ((int) substr($dernier, -4)) + 1 : 1;

        return sprintf('%s-%04d', $prefixe, $numero);
    }
}