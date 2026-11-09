<?php

namespace App\Notifications;

use App\Models\Absence;
use Illuminate\Notifications\Notification;

class AbsenceEnregistree extends Notification
{
    public function __construct(public Absence $absence)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $eleve = $this->absence->eleve;
        $date = $this->absence->date_absence->format('d/m/Y');
        $retard = $this->absence->type === 'retard';

        return [
            'titre' => ($retard ? 'Retard' : 'Absence') . ' de ' . $eleve->nomComplet(),
            'message' => $retard
                ? "{$eleve->prenoms} est arrivé(e) en retard le {$date}."
                : "{$eleve->prenoms} était absent(e) le {$date}.",
            'eleve_id' => $eleve->id,
            'absence_id' => $this->absence->id,
        ];
    }
}