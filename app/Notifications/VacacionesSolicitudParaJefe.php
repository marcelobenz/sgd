<?php

namespace App\Notifications;

use App\Models\VacacionesSolicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacacionesSolicitudParaJefe extends Notification
{
    use Queueable;

    public function __construct(
        public VacacionesSolicitud $solicitud,
        public string $evento,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $empleado = $this->solicitud->usuario;
        $detalle = $this->evento === 'cancelada'
            ? 'canceló una solicitud de vacaciones pendiente.'
            : 'creó una nueva solicitud de vacaciones.';

        return (new MailMessage)
            ->subject('Vacaciones: solicitud '.($this->evento === 'cancelada' ? 'cancelada' : 'pendiente'))
            ->greeting('Hola '.$notifiable->name)
            ->line($empleado->name.' '.$detalle)
            ->line('Período: '.$this->solicitud->fecha_desde->format('d/m/Y').' al '.$this->solicitud->fecha_hasta->format('d/m/Y'))
            ->line('Días: '.$this->solicitud->dias)
            ->line('Estado: '.ucfirst($this->solicitud->estado))
            ->action('Revisar solicitudes', route('vacaciones.admin', ['anio' => $this->solicitud->fecha_desde->year]))
            ->line('SGD');
    }
}
