<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Matiere extends Model
{
    use BelongsToEtablissement;

    protected $table = 'matieres';

    protected $fillable = ['etablissement_id', 'nom', 'code'];
}