<?php

namespace App\Models;

use App\Models\Concerns\BelongsToEtablissement;
use Illuminate\Database\Eloquent\Model;

class Appel extends Model
{
    use BelongsToEtablissement;

    protected $table = 'appels';

    protected $fillable = ['etablissement_id', 'classe_id', 'date_appel', 'saisi_par'];

    protected function casts(): array
    {
        return ['date_appel' => 'date'];
    }
}