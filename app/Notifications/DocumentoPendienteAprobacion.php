<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentoPendienteAprobacion extends Notification implements ShouldQueue
{
    use Queueable;

    protected $documento;

    public function __construct($documento)
    {
        $this->documento = $documento;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = route('documentos.show', $this->documento->id); // Ruta para ver el documento

        return (new MailMessage)
                    ->subject('Nuevo documento pendiente de aprobación')
                    ->line('Tiene un nuevo documento pendiente de aprobación.')
                    ->action('Ver Documento', $url)
                    ->line('Gracias por usar nuestra aplicación!');
    }
}
