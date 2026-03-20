<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\DocumentoRecordatorio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\RecordatorioEjecucion;
use Illuminate\Support\Facades\DB;

class DocumentoRecordatorioController extends Controller
{
    public function store(Request $request, Documento $documento)
    {
        $data = $this->validarRecordatorio($request);

        $recordatorio = DocumentoRecordatorio::create(
            $this->armarPayload($data, $documento->id)
        );

        $recordatorio->usuarios()->sync($data['usuarios']);

        return redirect()
            ->route('documentos.edit', $documento->id)
            ->with('success', 'Recordatorio creado correctamente.');
    }

    public function update(Request $request, DocumentoRecordatorio $recordatorio)
    {
        $data = $this->validarRecordatorio($request);

        $recordatorio->update(
            $this->armarPayload($data, $recordatorio->documento_id)
        );

        $recordatorio->usuarios()->sync($data['usuarios']);

        return redirect()
            ->route('documentos.edit', $recordatorio->documento_id)
            ->with('success', 'Recordatorio actualizado correctamente.');
    }

    public function destroy(DocumentoRecordatorio $recordatorio)
    {
        $documentoId = $recordatorio->documento_id;

        $recordatorio->usuarios()->detach();
        $recordatorio->delete();

        return redirect()
            ->route('documentos.edit', $documentoId)
            ->with('success', 'Recordatorio eliminado correctamente.');
    }

    public function toggleActivo(DocumentoRecordatorio $recordatorio)
    {
        $recordatorio->activo = !$recordatorio->activo;
        $recordatorio->save();

        return redirect()
            ->route('documentos.edit', $recordatorio->documento_id)
            ->with('success', 'Estado del recordatorio actualizado correctamente.');
    }

    private function validarRecordatorio(Request $request): array
    {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'mensaje' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:no_repite,diario,semanal,mensual,anual',
            'usuarios' => 'required|array|min:1',
            'usuarios.*' => 'exists:users,id',
            'notificar_interno' => 'nullable|boolean',
            'notificar_email' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
        ], [
            'nombre.required' => 'Debe ingresar un nombre para el recordatorio.',
            'fecha_inicio.required' => 'Debe seleccionar una fecha.',
            'fecha_inicio.date' => 'La fecha seleccionada no es válida.',
            'frecuencia.required' => 'Debe seleccionar una repetición.',
            'frecuencia.in' => 'La repetición seleccionada no es válida.',
            'usuarios.required' => 'Debe seleccionar al menos un usuario destinatario.',
            'usuarios.array' => 'La lista de usuarios no es válida.',
            'usuarios.min' => 'Debe seleccionar al menos un usuario destinatario.',
            'usuarios.*.exists' => 'Uno de los usuarios seleccionados no existe.',
        ]);
    }

    private function armarPayload(array $data, int $documentoId): array
    {
        $fechaInicio = Carbon::parse($data['fecha_inicio']);
        $horaEnvio = '09:00:00';

        $fechaConHora = Carbon::parse(
            $fechaInicio->format('Y-m-d') . ' ' . $horaEnvio
        );

        $frecuencia = $data['frecuencia'];

        $diaSemana = (int) $fechaInicio->dayOfWeekIso; // 1=lunes ... 7=domingo
        $diaMes = (int) $fechaInicio->day;
        $mesAnual = (int) $fechaInicio->month;

        return [
            'documento_id' => $documentoId,
            'nombre' => $data['nombre'],
            'mensaje' => $data['mensaje'] ?? null,
            'frecuencia' => $frecuencia,
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'hora_envio' => $horaEnvio,
            'dia_semana' => $frecuencia === 'semanal' ? $diaSemana : null,
            'dia_mes' => in_array($frecuencia, ['mensual', 'anual']) ? $diaMes : null,
            'mes_anual' => $frecuencia === 'anual' ? $mesAnual : null,
            'notificar_interno' => !empty($data['notificar_interno']),
            'notificar_email' => !empty($data['notificar_email']),
            'activo' => !empty($data['activo']),
            'proxima_ejecucion' => $this->calcularProximaEjecucionInicial($fechaConHora, $frecuencia),
            'created_by' => Auth::id(),
        ];
    }

    private function calcularProximaEjecucionInicial(Carbon $fechaBase, string $frecuencia): ?Carbon
    {
        if (!in_array($frecuencia, ['no_repite', 'diario', 'semanal', 'mensual', 'anual'])) {
            return null;
        }

        if ($frecuencia === 'no_repite') {
            return $fechaBase;
        }

        $ahora = now();
        $proxima = $fechaBase->copy();

        while ($proxima->lessThanOrEqualTo($ahora)) {
            switch ($frecuencia) {
                case 'diario':
                    $proxima->addDay();
                    break;

                case 'semanal':
                    $proxima->addWeek();
                    break;

                case 'mensual':
                    $proxima->addMonthNoOverflow();
                    break;

                case 'anual':
                    $proxima->addYear();
                    break;
            }
        }

        return $proxima;
    }

    public function misRecordatorios()
    {
        $userId = auth()->id();
        session(['recordatorios_view' => 'lista']);


        $ejecuciones = RecordatorioEjecucion::with(['documento', 'recordatorio'])
            ->where('user_id', $userId)
            ->whereIn('estado', ['pendiente', 'postergado'])
            ->orderByRaw("
                CASE
                    WHEN COALESCE(postergado_hasta, fecha_programada) < NOW() THEN 0
                    WHEN DATE(COALESCE(postergado_hasta, fecha_programada)) = CURDATE() THEN 1
                    ELSE 2
                END
            ")
            ->orderByRaw("COALESCE(postergado_hasta, fecha_programada) ASC")
            ->get();

        $cantidadVencidos = RecordatorioEjecucion::where('user_id', $userId)
            ->whereIn('estado', ['pendiente', 'postergado'])
            ->whereRaw('COALESCE(postergado_hasta, fecha_programada) < NOW()')
            ->count();

        $cantidadHoy = RecordatorioEjecucion::where('user_id', $userId)
            ->whereIn('estado', ['pendiente', 'postergado'])
            ->whereRaw('DATE(COALESCE(postergado_hasta, fecha_programada)) = CURDATE()')
            ->count();

        $cantidadProximos7 = RecordatorioEjecucion::where('user_id', $userId)
            ->whereIn('estado', ['pendiente', 'postergado'])
            ->whereRaw('DATE(COALESCE(postergado_hasta, fecha_programada)) > CURDATE()')
            ->whereRaw('DATE(COALESCE(postergado_hasta, fecha_programada)) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)')
            ->count();

        return view('recordatorios.mis', compact(
            'ejecuciones',
            'cantidadVencidos',
            'cantidadHoy',
            'cantidadProximos7'
        ));
    }

    public function resolverEjecucion(Request $request, RecordatorioEjecucion $ejecucion)
    {
        if ((int) $ejecucion->user_id !== (int) auth()->id()) {
            abort(403, 'No tenés permiso para resolver este recordatorio.');
        }

        $data = $request->validate([
            'observacion' => 'nullable|string|max:1000',
        ]);

        $ejecucion->update([
            'estado' => 'resuelto',
            'fecha_resolucion' => now(),
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()
            ->route('recordatorios.mis')
            ->with('success', 'Recordatorio marcado como resuelto.');
    }

    public function postergarEjecucion(Request $request, RecordatorioEjecucion $ejecucion)
    {
        if ((int) $ejecucion->user_id !== (int) auth()->id()) {
            abort(403, 'No tenés permiso para postergar este recordatorio.');
        }

        $data = $request->validate([
            'postergado_hasta' => 'required|date|after_or_equal:today',
            'observacion' => 'nullable|string|max:1000',
        ], [
            'postergado_hasta.required' => 'Debés indicar una nueva fecha.',
            'postergado_hasta.date' => 'La fecha ingresada no es válida.',
            'postergado_hasta.after_or_equal' => 'La nueva fecha no puede ser anterior a hoy.',
        ]);

        $ejecucion->update([
            'estado' => 'postergado',
            'postergado_hasta' => $data['postergado_hasta'],
            'observacion' => $data['observacion'] ?? null,
        ]);

        return redirect()
            ->route('recordatorios.mis')
            ->with('success', 'Recordatorio postergado correctamente.');
    }

    public function calendario()
    {
        session(['recordatorios_view' => 'calendario']);
        return view('recordatorios.calendario');
    }

    public function eventosCalendario()
    {
        $userId = auth()->id();

        $ejecuciones = RecordatorioEjecucion::with(['documento', 'recordatorio'])
            ->where('user_id', $userId)
            ->whereIn('estado', ['pendiente', 'postergado', 'resuelto'])
            ->get();

        $eventos = $ejecuciones->map(function ($ejecucion) {
            $fecha = $ejecucion->postergado_hasta ?? $ejecucion->fecha_programada;

            return [
                'id' => $ejecucion->id,
                'title' => ($ejecucion->documento->titulo ?? 'Documento') . ' - ' . ($ejecucion->recordatorio->nombre ?? 'Recordatorio'),
                'start' => $fecha ? $fecha->format('Y-m-d\TH:i:s') : null,
                'allDay' => false,
                'backgroundColor' => $this->colorEventoRecordatorio($ejecucion, $fecha),
                'borderColor' => $this->colorEventoRecordatorio($ejecucion, $fecha),
                'extendedProps' => [
                    'documento_id' => $ejecucion->documento?->id,
                    'documento_titulo' => $ejecucion->documento->titulo ?? 'Sin documento',
                    'recordatorio_nombre' => $ejecucion->recordatorio->nombre ?? '-',
                    'mensaje' => $ejecucion->recordatorio->mensaje ?? '',
                    'estado' => $ejecucion->estado,
                    'observacion' => $ejecucion->observacion,
                    'fecha' => $fecha ? $fecha->format('d/m/Y H:i') : '-',
                    'url_documento' => $ejecucion->documento ? route('documentos.validaPermiso', [
                        'id' => $ejecucion->documento->id,
                        'ruta' => 'documentos.show',
                        'permiso' => 'puedeLeer'
                    ]) : null,
                    'resolver_url' => route('recordatorios.ejecuciones.resolver', $ejecucion->id),
                    'postergar_url' => route('recordatorios.ejecuciones.postergar', $ejecucion->id),
                ],
            ];
        })->values();

        return response()->json($eventos);
    }

    private function colorEventoRecordatorio($ejecucion, $fecha)
    {
        if ($ejecucion->estado === 'resuelto') {
            return '#28a745';
        }

        if ($ejecucion->estado === 'postergado') {
            return '#17a2b8';
        }

        if ($fecha && $fecha->lt(now())) {
            return '#dc3545';
        }

        if ($fecha && $fecha->isSameDay(now())) {
            return '#ffc107';
        }

        return '#007bff';
    }
}