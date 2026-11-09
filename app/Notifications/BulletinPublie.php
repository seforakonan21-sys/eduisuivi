<?php

namespace App\Notifications;

use App\Models\Eleve;
use App\Models\Periode;
use Illuminate\Notifications\Notification;

class BulletinPublie extends Notification
{
    public function __construct(public Eleve $eleve, public Periode $periode)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'titre' => 'Bulletin disponible',
            'message' => "Le bulletin du {$this->periode->nom} de {$this->eleve->prenoms} est disponible.",
            'eleve_id' => $this->eleve->id,
            'periode_id' => $this->periode->id,
        ];
    }
}