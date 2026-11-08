<?php

namespace App\Models;

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
        ];
    }

    public function aLeRole(Role ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }
}