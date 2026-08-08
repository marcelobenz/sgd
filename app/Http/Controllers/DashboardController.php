<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Iso\Accion;
use App\Models\Iso\Objetivo;
use App\Models\Iso\ObjetivoAccion;
use App\Models\Iso\Periodo;
use App\Models\Iso\ProveedorAccion;
use App\Models\Iso\Riesgo;
use App\Services\BandejaPendientesService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, BandejaPendientesService $bandeja)
    {
        $user = $request->user()->loadMissing('permisoIso');
        $preferencias = $user->preferenciasConDefaults();
        $periodo = Periodo::where('estado', 'vigente')->first() ?? Periodo::latest('anio')->first();
        $pendientes = $bandeja->paraUsuario($user, $periodo, (int) $preferencias['horizonte_dias']);

        $documentos = Documento::query()
            ->whereHas('permisos', fn ($query) => $query->where('user_id', $user->id));

        $documentosResumen = [
            'accesibles' => (clone $documentos)->count(),
            'por_aprobar' => $pendientes->where('tipo', 'aprobacion_documento')->count(),
            'revisiones' => $pendientes->where('tipo', 'revision_documento')->count(),
            'revisiones_vencidas' => $pendientes->where('tipo', 'revision_documento')->where('prioridad', 'vencida')->count(),
        ];

        $iso = null;
        if ($user->puedeVerPlanificacion() && $periodo) {
            $riesgos = Riesgo::where('periodo_id', $periodo->id);
            $accionesRiesgo = Accion::whereHas('riesgo', fn ($query) => $query->where('periodo_id', $periodo->id));
            $accionesObjetivo = ObjetivoAccion::whereHas('objetivo', fn ($query) => $query->where('periodo_id', $periodo->id));
            $accionesProveedor = ProveedorAccion::whereHas('evaluacion', fn ($query) => $query->where('periodo_id', $periodo->id));

            $iso = [
                'riesgos_altos' => (clone $riesgos)->where('indice_inicial', '>', 6)->whereNotIn('estado', ['finalizado', 'anulado'])->count(),
                'verificaciones_pendientes' => (clone $riesgos)->whereNotIn('estado', ['finalizado', 'anulado'])->whereDate('fecha_verificacion_prevista', '<=', today())->count(),
                'acciones_abiertas' => (clone $accionesRiesgo)->whereIn('estado', ['pendiente', 'en_proceso'])->count()
                    + (clone $accionesObjetivo)->whereIn('estado', ['pendiente', 'en_proceso'])->count()
                    + (clone $accionesProveedor)->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
                'objetivos_activos' => Objetivo::where('periodo_id', $periodo->id)->where('estado', 'activo')->count(),
                'riesgos_distribucion' => [
                    'Bajo' => (clone $riesgos)->where('indice_inicial', '<=', 3)->count(),
                    'Medio' => (clone $riesgos)->whereBetween('indice_inicial', [4, 6])->count(),
                    'Alto' => (clone $riesgos)->where('indice_inicial', '>', 6)->count(),
                ],
                'acciones_distribucion' => [
                    'Pendientes' => (clone $accionesRiesgo)->where('estado', 'pendiente')->count() + (clone $accionesObjetivo)->where('estado', 'pendiente')->count() + (clone $accionesProveedor)->where('estado', 'pendiente')->count(),
                    'En proceso' => (clone $accionesRiesgo)->where('estado', 'en_proceso')->count() + (clone $accionesObjetivo)->where('estado', 'en_proceso')->count() + (clone $accionesProveedor)->where('estado', 'en_proceso')->count(),
                    'Completadas' => (clone $accionesRiesgo)->where('estado', 'completada')->count() + (clone $accionesObjetivo)->where('estado', 'completada')->count() + (clone $accionesProveedor)->where('estado', 'completada')->count(),
                ],
            ];
        }

        return view('dashboard', [
            'periodo' => $periodo,
            'puedeVerIso' => $user->puedeVerPlanificacion(),
            'puedeGestionarIso' => $user->puedeGestionarPlanificacion(),
            'pendientes' => $pendientes->take(6),
            'resumenPendientes' => $bandeja->resumen($pendientes),
            'documentosResumen' => $documentosResumen,
            'iso' => $iso,
            'preferencias' => $preferencias,
        ]);
    }
}
