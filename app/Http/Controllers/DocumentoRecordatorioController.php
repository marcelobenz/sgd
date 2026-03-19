<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\DocumentoRecordatorio;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentoRecordatorioController extends Controller
{
    public function store(Request $request, Documento $documento)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'mensaje' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:no_repite,diario,semanal,mensual,anual',
            'usuarios' => 'required|array|min:1',
            'usuarios.*' => 'exists:users,id',
        ], [
            'nombre.required' => 'Debe ingresar un nombre para el recordatorio.',
            'fecha_inicio.required' => 'Debe seleccionar una fecha.',
            'frecuencia.required' => 'Debe seleccionar una repetición.',
            'usuarios.required' => 'Debe seleccionar al menos un usuario destinatario.',
        ]);

        $fechaInicio = Carbon::parse($request->fecha_inicio);

        // Hora fija por defecto del sistema
        $horaEnvio = '09:00:00';

        $fechaConHora = Carbon::parse($fechaInicio->format('Y-m-d') . ' ' . $horaEnvio);

        $diaSemana = (int) $fechaInicio->dayOfWeekIso; // 1=lunes ... 7=domingo
        $diaMes = (int) $fechaInicio->day;
        $mesAnual = (int) $fechaInicio->month;

        $recordatorio = DocumentoRecordatorio::create([
            'documento_id' => $documento->id,
            'nombre' => $request->nombre,
            'mensaje' => $request->mensaje,
            'frecuencia' => $request->frecuencia,
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'hora_envio' => $horaEnvio,
            'dia_semana' => $request->frecuencia === 'semanal' ? $diaSemana : null,
            'dia_mes' => in_array($request->frecuencia, ['mensual', 'anual']) ? $diaMes : null,
            'mes_anual' => $request->frecuencia === 'anual' ? $mesAnual : null,
            'notificar_interno' => $request->has('notificar_interno'),
            'notificar_email' => $request->has('notificar_email'),
            'activo' => $request->has('activo'),
            'proxima_ejecucion' => $this->calcularProximaEjecucionInicial($fechaConHora, $request->frecuencia),
            'created_by' => Auth::id(),
        ]);

        $recordatorio->usuarios()->sync($request->usuarios);

        return redirect()
            ->route('documentos.edit', $documento->id)
            ->with('success', 'Recordatorio creado correctamente.');
    }

    private function calcularProximaEjecucionInicial(Carbon $fechaBase, string $frecuencia): Carbon
    {
        $ahora = now();

        if ($frecuencia === 'no_repite') {
            return $fechaBase;
        }

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
                default:
                    return $fechaBase;
            }
        }

        return $proxima;
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

    public function update(Request $request, DocumentoRecordatorio $recordatorio)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'mensaje' => 'nullable|string',
            'fecha_inicio' => 'required|date',
            'frecuencia' => 'required|in:no_repite,diario,semanal,mensual,anual',
            'usuarios' => 'required|array|min:1',
            'usuarios.*' => 'exists:users,id',
        ], [
            'nombre.required' => 'Debe ingresar un nombre para el recordatorio.',
            'fecha_inicio.required' => 'Debe seleccionar una fecha.',
            'frecuencia.required' => 'Debe seleccionar una repetición.',
            'usuarios.required' => 'Debe seleccionar al menos un usuario destinatario.',
        ]);

        $fechaInicio = \Carbon\Carbon::parse($request->fecha_inicio);
        $horaEnvio = '09:00:00';
        $fechaConHora = \Carbon\Carbon::parse($fechaInicio->format('Y-m-d') . ' ' . $horaEnvio);

        $diaSemana = (int) $fechaInicio->dayOfWeekIso;
        $diaMes = (int) $fechaInicio->day;
        $mesAnual = (int) $fechaInicio->month;

        $recordatorio->update([
            'nombre' => $request->nombre,
            'mensaje' => $request->mensaje,
            'frecuencia' => $request->frecuencia,
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'hora_envio' => $horaEnvio,
            'dia_semana' => $request->frecuencia === 'semanal' ? $diaSemana : null,
            'dia_mes' => in_array($request->frecuencia, ['mensual', 'anual']) ? $diaMes : null,
            'mes_anual' => $request->frecuencia === 'anual' ? $mesAnual : null,
            'notificar_interno' => $request->has('notificar_interno'),
            'notificar_email' => $request->has('notificar_email'),
            'activo' => $request->has('activo'),
            'proxima_ejecucion' => $this->calcularProximaEjecucionInicial($fechaConHora, $request->frecuencia),
        ]);

        $recordatorio->usuarios()->sync($request->usuarios);

        return redirect()
            ->route('documentos.edit', $recordatorio->documento_id)
            ->with('success', 'Recordatorio actualizado correctamente.');
    }

    public function toggleActivo(DocumentoRecordatorio $recordatorio)
    {
        $recordatorio->activo = !$recordatorio->activo;
        $recordatorio->save();

        return redirect()
            ->route('documentos.edit', $recordatorio->documento_id)
            ->with('success', 'Estado del recordatorio actualizado correctamente.');
    }

}