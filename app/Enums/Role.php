<?php

namespace App\Enums;

enum Role: string
{
    case AdminGeneral = 'admin_general';
    case Directeur = 'directeur';
    case Enseignant = 'enseignant';
    case ParentEleve = 'parent';
    case Eleve = 'eleve';
    case Administratif = 'administratif';

    public function label(): string
    {
        return match ($this) {
            self::AdminGeneral => 'Administrateur général',
            self::Directeur => 'Directeur / Chef d\'établissement',
            self::Enseignant => 'Enseignant',
            self::ParentEleve => 'Parent d\'élève',
            self::Eleve => 'Élève',
            self::Administratif => 'Personnel administratif',
        };
    }
}