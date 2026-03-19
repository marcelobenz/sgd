<?php

namespace App\Notifications;

use App\Models\DocumentoRecordatorio;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecordatorioDocumentoNotification extends Notification
{
    use Queueable;

    protected $recordatorio;

    public function __construct(DocumentoRecordatorio $recordatorio)
    {
        $this->recordatorio = $recordatorio;
    }

    public function via($notifiable)
    {
        $canales = [];

        if ($this->recordatorio->notificar_interno) {
            $canales[] = 'database';
        }

        if ($this->recordatorio->notificar_email) {
            $canales[] = 'mail';
        }

        return $canales;
    }

    public function toMail($notifiable)
    {
        $documento = $this->recordatorio->documento;

        return (new MailMessage)
            ->subject('Recordatorio: ' . $this->recordatorio->nombre)
            ->greeting('Hola ' . ($notifiable->name ?? ''))
            ->line('Tenés un recordatorio asociado a un documento.')
            ->line('Documento: ' . $documento->titulo)
            ->line('Recordatorio: ' . $this->recordatorio->nombre)
            ->when($this->recordatorio->mensaje, function ($mail) {
                return $mail->line('Mensaje: ' . $this->recordatorio->mensaje);
            })
            ->action('Ver documento', route('documentos.show', $documento->id))
            ->line('Este mensaje fue generado automáticamente por el sistema.');
    }

    public function toArray($notifiable)
    {
        $documento = $this->recordatorio->documento;

        return [
            'recordatorio_id' => $this->recordatorio->id,
            'documento_id' => $documento->id,
            'documento_titulo' => $documento->titulo,
            'recordatorio_nombre' => $this->recordatorio->nombre,
            'mensaje' => $this->recordatorio->mensaje,
            'url' => route('documentos.show', $documento->id),
        ];
    }
}