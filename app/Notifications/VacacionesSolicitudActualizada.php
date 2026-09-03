<?php

namespace App\Notifications;

use App\Models\VacacionesSolicitud;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VacacionesSolicitudActualizada extends Notification
{
    use Queueable;

    public function __construct(
        public VacacionesSolicitud $solicitud,
        public string $resultado,
        public ?string $motivo = null,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $detalle = match ($this->resultado) {
            'aprobada' => 'tu solicitud de vacaciones fue aprobada.',
            'rechazada' => 'tu solicitud de vacaciones fue rechazada.',
            'desaprobada' => 'la aprobación de tu solicitud de vacaciones fue revertida y volvió a quedar pendiente.',
            default => 'tu solicitud de vacaciones fue actualizada.',
        };

        $mail = (new MailMessage)
            ->subject('Vacaciones: solicitud '.ucfirst($this->resultado))
            ->greeting('Hola '.$notifiable->name)
            ->line('Te informamos que '.$detalle)
            ->line('Período: '.$this->solicitud->fecha_desde->format('d/m/Y').' al '.$this->solicitud->fecha_hasta->format('d/m/Y'))
            ->line('Días: '.$this->solicitud->dias)
            ->action('Ver mis solicitudes', route('vacaciones.mis', ['anio' => $this->solicitud->fecha_desde->year]));

        if ($this->motivo) {
            $mail->line('Motivo: '.$this->motivo);
        }

        return $mail->line('SGD');
    }
}
