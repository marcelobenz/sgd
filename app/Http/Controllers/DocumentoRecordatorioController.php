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
}