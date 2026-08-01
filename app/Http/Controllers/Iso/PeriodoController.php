<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Accion;
use App\Models\Iso\Periodo;
use App\Models\Iso\PeriodoTransicion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodoController extends Controller
{
    public function index()
    {
        $periodos = Periodo::withCount([
            'contextos',
            'riesgos',
            'contextos as contextos_pendientes_count' => fn ($query) => $query->where('decision', 'pendiente'),
            'riesgos as riesgos_abiertos_count' => fn ($query) => $query->whereNotIn('estado', ['finalizado', 'anulado']),
            'riesgos as verificaciones_pendientes_count' => fn ($query) => $query->where('eficacia', 'pendiente'),
        ])->with(['transiciones.realizadoPor'])->orderByDesc('anio')->get();

        foreach ($periodos as $periodo) {
            $periodo->acciones_abiertas_count = Accion::whereHas('riesgo', fn ($query) => $query->where('periodo_id', $periodo->id))
                ->whereIn('estado', ['pendiente', 'en_proceso'])->count();
        }

        return view('iso.periodos.index', compact('periodos'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['anio' => 'required|integer|min:2000|max:2100|unique:iso_periodos,anio', 'nombre' => 'nullable|string|max:255']);
        Periodo::create(['anio' => $data['anio'], 'nombre' => $data['nombre'] ?: "Planificación {$data['anio']}", 'creado_por' => $request->user()->id]);
        return back()->with('success', 'Período creado.');
    }

    public function activar(Request $request, Periodo $periodo)
    {
        abort_if($periodo->estado === 'cerrado', 422, 'Un período cerrado debe reabrirse antes de activarlo.');
        DB::transaction(function () use ($periodo) {
            Periodo::where('estado', 'vigente')->whereKeyNot($periodo->id)->update(['estado' => 'borrador']);
            $periodo->update(['estado' => 'vigente']);
        });
        return back()->with('success', 'Período activado.');
    }

    public function clima(Request $request, Periodo $periodo)
    {
        $data = $request->validate(['cambio_climatico_relevante' => 'required|boolean', 'fundamento_cambio_climatico' => 'required|string|max:3000']);
        $periodo->update($data);
        return back()->with('success', 'Evaluación de cambio climático actualizada.');
    }

    public function cerrar(Request $request, Periodo $periodo)
    {
        abort_if($periodo->estado === 'cerrado', 422, 'El período ya está cerrado.');
        abort_if($periodo->contextos()->where('decision', 'pendiente')->exists(), 422, 'No se puede cerrar: hay elementos FODA pendientes de decisión.');
        abort_if(is_null($periodo->cambio_climatico_relevante) || blank($periodo->fundamento_cambio_climatico), 422, 'No se puede cerrar: falta completar la evaluación de cambio climático.');

        $data = $request->validate([
            'motivo' => 'required|string|min:10|max:1000',
            'comprende_impacto' => 'accepted',
            'confirmacion' => 'required|in:CERRAR ' . $periodo->anio,
        ], [
            'confirmacion.in' => 'Escribí exactamente CERRAR ' . $periodo->anio . ' para confirmar.',
            'comprende_impacto.accepted' => 'Debés confirmar que comprendés el impacto del cierre.',
        ]);

        $resumen = $this->resumenControl($periodo);
        DB::transaction(function () use ($periodo, $request, $data, $resumen) {
            $estadoAnterior = $periodo->estado;
            $periodo->update(['estado' => 'cerrado', 'cerrado_por' => $request->user()->id, 'cerrado_en' => now()]);
            PeriodoTransicion::create([
                'periodo_id' => $periodo->id, 'accion' => 'cierre', 'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => 'cerrado', 'motivo' => $data['motivo'], 'resumen_control' => $resumen,
                'realizado_por' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Período cerrado. El FODA y sus decisiones quedaron bloqueados para edición.');
    }

    public function reabrir(Request $request, Periodo $periodo)
    {
        abort_unless($periodo->estado === 'cerrado', 422, 'Solo se puede reabrir un período cerrado.');
        $data = $request->validate([
            'motivo' => 'required|string|min:10|max:1000',
            'comprende_impacto' => 'accepted',
            'confirmacion' => 'required|in:REABRIR ' . $periodo->anio,
        ], [
            'confirmacion.in' => 'Escribí exactamente REABRIR ' . $periodo->anio . ' para confirmar.',
            'comprende_impacto.accepted' => 'Debés confirmar que comprendés el impacto de la reapertura.',
        ]);

        DB::transaction(function () use ($periodo, $request, $data) {
            $resumen = $this->resumenControl($periodo);
            $periodo->update(['estado' => 'borrador', 'cerrado_por' => null, 'cerrado_en' => null]);
            PeriodoTransicion::create([
                'periodo_id' => $periodo->id, 'accion' => 'reapertura', 'estado_anterior' => 'cerrado',
                'estado_nuevo' => 'borrador', 'motivo' => $data['motivo'], 'resumen_control' => $resumen,
                'realizado_por' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Período reabierto como borrador. Ya puede editarse; activalo si debe ser el período vigente.');
    }

    private function resumenControl(Periodo $periodo): array
    {
        return [
            'foda_total' => $periodo->contextos()->count(),
            'foda_pendientes' => $periodo->contextos()->where('decision', 'pendiente')->count(),
            'riesgos_total' => $periodo->riesgos()->count(),
            'riesgos_abiertos' => $periodo->riesgos()->whereNotIn('estado', ['finalizado', 'anulado'])->count(),
            'acciones_abiertas' => Accion::whereHas('riesgo', fn ($query) => $query->where('periodo_id', $periodo->id))->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
            'verificaciones_pendientes' => $periodo->riesgos()->where('eficacia', 'pendiente')->count(),
        ];
    }
}
