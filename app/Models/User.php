<?php


namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


use App\Enums\Role;
use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, BelongsToEtablissement;

    protected $fillable = [
        'etablissement_id', 'name', 'email', 'password',
        'role', 'telephone', 'actif', 'consentement_at',
        'doit_changer_mot_de_passe',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'consentement_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'actif' => 'boolean',
            'doit_changer_mot_de_passe' => 'boolean',
        ];
    }

    public function aLeRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function enfants(): BelongsToMany
    {
        return $this->belongsToMany(Eleve::class, 'eleve_parent', 'parent_id', 'eleve_id')
            ->withPivot('lien')
            ->withTimestamps();
    }
}