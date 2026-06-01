<?php

namespace App\Console\Commands;

use App\Models\DocumentoRecordatorio;
use App\Models\RecordatorioEjecucion;
use App\Notifications\RecordatorioDocumentoNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcesarRecordatoriosDocumentos extends Command
{
    protected $signature = 'recordatorios:procesar';
    protected $description = 'Procesa y envía recordatorios de documentos vencidos';

    public function handle()
    {
        $ahora = now();

        $recordatorios = DocumentoRecordatorio::with(['usuarios', 'documento'])
            ->where('activo', true)
            ->whereNotNull('proxima_ejecucion')
            ->where('proxima_ejecucion', '<=', $ahora)
            ->get();

        foreach ($recordatorios as $recordatorio) {
            // Guardamos la fecha que se está ejecutando ahora
            $fechaEjecucionActual = $recordatorio->proxima_ejecucion;

            if ($recordatorio->usuarios->isEmpty()) {
                \Log::warning('Recordatorio vencido sin usuarios asociados. No se avanza.', [
                    'recordatorio_id' => $recordatorio->id,
                    'documento_id' => $recordatorio->documento_id,
                    'proxima_ejecucion' => $recordatorio->proxima_ejecucion,
                ]);

                continue;
            }

            foreach ($recordatorio->usuarios as $usuario) {
                // Crear ejecución pendiente para el usuario si todavía no existe
                RecordatorioEjecucion::firstOrCreate(
                    [
                        'documento_recordatorio_id' => $recordatorio->id,
                        'documento_id' => $recordatorio->documento_id,
                        'user_id' => $usuario->id,
                        'fecha_programada' => $fechaEjecucionActual,
                    ],
                    [
                        'estado' => 'pendiente',
                    ]
                );

                // Mantener notificación actual
                $usuario->notify(new RecordatorioDocumentoNotification($recordatorio));
            }

            $siguiente = $this->calcularSiguienteEjecucion($recordatorio);

            if ($recordatorio->frecuencia === 'no_repite') {
                $recordatorio->activo = false;
            }

            $recordatorio->proxima_ejecucion = $siguiente;
            $recordatorio->save();
        }

        $this->info('Recordatorios procesados: ' . $recordatorios->count());

        return 0;
    }

    private function calcularSiguienteEjecucion(DocumentoRecordatorio $recordatorio)
    {
        if (!$recordatorio->proxima_ejecucion) {
            return null;
        }

        $proxima = Carbon::parse($recordatorio->proxima_ejecucion);

        switch ($recordatorio->frecuencia) {
            case 'diario':
                return $proxima->copy()->addDay();

            case 'semanal':
                return $proxima->copy()->addWeek();

            case 'mensual':
                return $proxima->copy()->addMonthNoOverflow();

            case 'anual':
                return $proxima->copy()->addYear();

            case 'no_repite':
            default:
                return null;
        }
    }
}