<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Accion;
use App\Models\Iso\Objetivo;
use App\Models\Iso\ObjetivoAccion;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;
use App\Models\Iso\Proveedor;
use App\Models\Iso\ProveedorAccion;

class PlanificacionController extends Controller
{
    public function index()
    {
        $periodo = Periodo::where('estado', 'vigente')->first() ?? Periodo::latest('anio')->first();
        $stats = [
            'foda_pendientes' => $periodo?->contextos()->where('decision', 'pendiente')->count() ?? 0,
            'riesgos_altos' => $periodo?->riesgos()->where('indice_inicial', '>', 6)->whereNotIn('estado', ['finalizado', 'anulado'])->count() ?? 0,
            'acciones_vencidas' => Accion::whereHas('riesgo', fn ($q) => $periodo ? $q->where('periodo_id', $periodo->id) : $q->whereRaw('1=0'))
                ->whereNotIn('estado', ['completada', 'cancelada'])->whereDate('fecha_objetivo', '<', today())->count(),
            'verificaciones_pendientes' => $periodo?->riesgos()->whereNotIn('estado', ['finalizado', 'anulado'])->whereDate('fecha_verificacion_prevista', '<=', today())->count() ?? 0,
            'proveedores_vencidos' => Proveedor::where('estado', 'activo')->whereHas('ultimaEvaluacion', fn ($q) => $q->whereDate('proxima_evaluacion', '<', today()))->count(),
            'proveedor_acciones_vencidas' => ProveedorAccion::whereNotIn('estado', ['completada', 'cancelada'])->whereDate('fecha_objetivo', '<', today())->count(),
            'objetivos_activos' => Objetivo::when($periodo, fn ($q) => $q->where('periodo_id', $periodo->id), fn ($q) => $q->whereRaw('1=0'))->where('estado', 'activo')->count(),
            'objetivo_acciones_vencidas' => ObjetivoAccion::whereHas('objetivo', fn ($q) => $periodo ? $q->where('periodo_id', $periodo->id) : $q->whereRaw('1=0'))->whereNotIn('estado', ['completada', 'cancelada'])->whereDate('fecha_objetivo', '<', today())->count(),
        ];

        return view('iso.planificacion.index', compact('periodo', 'stats'));
    }
}
