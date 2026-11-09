<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Appreciation extends Model
{
    use BelongsToEtablissement;

    protected $table = 'appreciations';

    protected $fillable = ['etablissement_id', 'eleve_id', 'affectation_id', 'periode_id', 'texte', 'saisi_par'];
}