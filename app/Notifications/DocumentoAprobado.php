<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentoAprobado extends Notification implements ShouldQueue
{
    use Queueable;

    protected $documento;
    protected $aprobador;

    public function __construct($documento, $aprobador)
    {
        $this->documento = $documento;
        $this->aprobador = $aprobador;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('documentos.show', $this->documento->id);
        $fecha = optional($this->documento->fecha_aprobacion)->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject('Tu documento fue aprobado')
            ->line('El siguiente documento fue aprobado:')
            ->line('Título: ' . $this->documento->titulo)
            ->line('Aprobado por: ' . ($this->aprobador->name ?? 'Usuario'))
            //->line('Fecha de aprobación: ' . $fecha)
            ->action('Ver Documento', $url)
            ->line('Gracias por usar nuestra aplicación.');
    }
}
