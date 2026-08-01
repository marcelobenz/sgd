<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Accion;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;

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
            'verificaciones_pendientes' => $periodo?->riesgos()->where('eficacia', 'pendiente')->whereDate('fecha_verificacion_prevista', '<=', today())->count() ?? 0,
        ];

        return view('iso.planificacion.index', compact('periodo', 'stats'));
    }
}
