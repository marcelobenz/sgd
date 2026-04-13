<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentoRechazado extends Notification
{
    use Queueable;

    protected $documento;
    protected $comentarios;

    public function __construct($documento, $comentarios)
    {
        $this->documento = $documento;
        $this->comentarios = $comentarios;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Documento rechazado: ' . $this->documento->titulo)
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu documento "' . $this->documento->titulo . '" ha sido rechazado.')
            ->line('Comentarios del aprobador:')
            ->line($this->comentarios)
            ->action('Ver documento', route('documentos.show', $this->documento->id))
            ->line('Por favor revisa y realiza las correcciones necesarias.');
    }
}
