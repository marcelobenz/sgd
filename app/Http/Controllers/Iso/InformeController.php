<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Periodo;
use App\Models\Iso\ParteInteresada;
use App\Models\Documento;
use App\Models\Iso\Objetivo;
use App\Models\Iso\Proveedor;
use App\Services\Iso\ResultadoObjetivoService;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class InformeController extends Controller
{
    public function index(Request $request)
    {
        $tipo = $request->string('tipo', 'resumido')->toString();
        abort_unless(in_array($tipo, ['resumido', 'detallado'], true), 404);
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : (Periodo::where('estado', 'vigente')->first() ?? $periodos->firstOrFail());
        $inicioPeriodo = now()->setDate($periodo->anio, 1, 1)->startOfDay();
        $finPeriodo = now()->setDate($periodo->anio, 12, 31)->endOfDay();
        $periodo->load([
            'cerradoPor', 'transiciones.realizadoPor',
            'contextos' => fn ($query) => $query->with(['responsable', 'riesgos'])->orderBy('tipo')->orderBy('numero'),
            'riesgos' => fn ($query) => $query->with([
                'contexto', 'responsable', 'origenContinuidad.periodo', 'continuidades.periodo',
                'acciones.responsable', 'acciones.seguimientos.documento', 'acciones.transiciones.realizadoPor',
                'verificaciones' => fn ($verificaciones) => $verificaciones->with(['verificador', 'documento'])->orderBy('fecha')->orderBy('id'),
            ])->orderBy('numero'),
        ]);
        $partes = ParteInteresada::with([
            'responsable',
            'evaluaciones' => fn ($query) => $query->where('periodo_id', $periodo->id)->with(['periodo', 'evaluador', 'documento', 'riesgos'])->orderByDesc('fecha_evaluacion'),
        ])->where(function ($query) use ($periodo) {
            $query->where('estado', 'activa')->orWhereHas('evaluaciones', fn ($evaluaciones) => $evaluaciones->where('periodo_id', $periodo->id));
        })->orderBy('nombre')->get();
        $proveedores = Proveedor::with([
            'documento',
            'selecciones' => fn ($query) => $query->whereDate('fecha', '<=', $finPeriodo)->with(['evaluador', 'documento'])->latest('fecha')->latest('id'),
            'evaluaciones' => fn ($query) => $query->where('periodo_id', $periodo->id)->with(['evaluador', 'documento', 'riesgos', 'acciones.responsable', 'acciones.documento'])->latest('fecha_evaluacion')->latest('id'),
        ])->where(function ($query) use ($periodo, $inicioPeriodo, $finPeriodo) {
            $query->whereHas('evaluaciones', fn ($evaluaciones) => $evaluaciones->where('periodo_id', $periodo->id))
                ->orWhere(function ($vigente) use ($inicioPeriodo, $finPeriodo) {
                    $vigente->whereDate('fecha_alta', '<=', $finPeriodo)
                        ->where(function ($baja) use ($inicioPeriodo) {
                            $baja->whereNull('fecha_baja')->orWhereDate('fecha_baja', '>=', $inicioPeriodo);
                        });
                });
        })->orderBy('nombre')->orderBy('producto_servicio')->get();
        $objetivos = Objetivo::where('periodo_id', $periodo->id)->with([
            'responsable', 'indicadorPrincipal.mediciones.documento',
            'acciones.responsable', 'acciones.documento', 'acciones.seguimientos.documento', 'acciones.transiciones.usuario',
            'evaluaciones' => fn ($query) => $query->with(['evaluadoPor', 'documento'])->latest('fecha_evaluacion')->latest('id'),
            'revisiones' => fn ($query) => $query->with('realizadaPor')->latest('fecha_vigencia')->latest('id'),
            'contextos', 'riesgos', 'partes', 'origenContinuidad.periodo', 'continuidades.periodo',
        ])->orderBy('numero')->get();
        $servicioResultados = app(ResultadoObjetivoService::class);
        $objetivos->each(function (Objetivo $objetivo) use ($servicioResultados) {
            $resultado = $objetivo->indicadorPrincipal ? $servicioResultados->resultado($objetivo->indicadorPrincipal) : null;
            $objetivo->setAttribute('resultado_actual', $resultado);
            $objetivo->setAttribute('cumplimiento_actual', $objetivo->indicadorPrincipal ? $servicioResultados->cumplimiento($objetivo->indicadorPrincipal, $resultado) : 'pendiente');
        });
        $documentoIds = $partes->flatMap->evaluaciones->pluck('documento_id')
            ->merge($periodo->riesgos->flatMap->verificaciones->pluck('documento_id'))
            ->merge($periodo->riesgos->flatMap->acciones->flatMap(fn ($accion) => $accion->seguimientos)->pluck('documento_id'))
            ->merge($proveedores->pluck('documento_id'))
            ->merge($proveedores->flatMap->selecciones->pluck('documento_id'))
            ->merge($proveedores->flatMap(fn ($proveedor) => $proveedor->evaluaciones)->pluck('documento_id'))
            ->merge($proveedores->flatMap(fn ($proveedor) => $proveedor->evaluaciones)->flatMap(fn ($evaluacion) => $evaluacion->acciones)->pluck('documento_id'))
            ->merge($objetivos->flatMap(fn ($objetivo) => $objetivo->indicadorPrincipal?->mediciones ?? collect())->pluck('documento_id'))
            ->merge($objetivos->flatMap->evaluaciones->pluck('documento_id'))
            ->merge($objetivos->flatMap->acciones->pluck('documento_id'))
            ->merge($objetivos->flatMap->acciones->flatMap(fn ($accion) => $accion->seguimientos)->pluck('documento_id'))
            ->filter()->unique();
        $documentosAccesibles = $request->user()->isAdmin()
            ? $documentoIds
            : Documento::whereIn('id', $documentoIds)->whereHas('permisos', function ($permisos) use ($request) {
                $permisos->where('user_id', $request->user()->id)->where(function ($acceso) {
                    $acceso->where('puede_leer', true)->orWhere('puede_escribir', true)->orWhere('puede_aprobar', true)->orWhere('puede_eliminar', true);
                });
            })->pluck('id');

        $resumen = $this->resumen($periodo, $partes, $proveedores, $objetivos);
        return view('iso.informes.index', compact('tipo', 'periodos', 'periodo', 'partes', 'proveedores', 'objetivos', 'documentosAccesibles', 'resumen'));
    }

    private function resumen(Periodo $periodo, Collection $partes, Collection $proveedores, Collection $objetivos): array
    {
        $accionesRiesgo = $periodo->riesgos->flatMap->acciones;
        $accionesObjetivo = $objetivos->flatMap->acciones;
        $evaluacionesProveedor = $proveedores->flatMap->evaluaciones;

        $pendientes = collect();
        if ($periodo->contextos->where('decision', 'pendiente')->count()) $pendientes->push($periodo->contextos->where('decision', 'pendiente')->count().' elementos FODA pendientes de decisi&oacute;n');
        if ($periodo->riesgos->whereIn('estado', ['pendiente', 'en_proceso'])->count()) $pendientes->push($periodo->riesgos->whereIn('estado', ['pendiente', 'en_proceso'])->count().' riesgos u oportunidades abiertos');
        if ($accionesRiesgo->whereIn('estado', ['pendiente', 'en_proceso'])->count()) $pendientes->push($accionesRiesgo->whereIn('estado', ['pendiente', 'en_proceso'])->count().' acciones de riesgos abiertas');
        if ($objetivos->filter(fn ($objetivo) => !$objetivo->evaluaciones->contains('tipo', 'cierre_periodo'))->count()) $pendientes->push($objetivos->filter(fn ($objetivo) => !$objetivo->evaluaciones->contains('tipo', 'cierre_periodo'))->count().' objetivos sin evaluaci&oacute;n de cierre');
        if ($accionesObjetivo->whereIn('estado', ['pendiente', 'en_proceso'])->count()) $pendientes->push($accionesObjetivo->whereIn('estado', ['pendiente', 'en_proceso'])->count().' acciones de objetivos abiertas');
        if ($partes->filter(fn ($parte) => $parte->evaluaciones->isEmpty())->count()) $pendientes->push($partes->filter(fn ($parte) => $parte->evaluaciones->isEmpty())->count().' partes interesadas sin evaluaci&oacute;n del per&iacute;odo');
        if ($proveedores->filter(fn ($proveedor) => $proveedor->evaluaciones->isEmpty())->count()) $pendientes->push($proveedores->filter(fn ($proveedor) => $proveedor->evaluaciones->isEmpty())->count().' proveedores sin evaluaci&oacute;n del per&iacute;odo');
        if (is_null($periodo->cambio_climatico_relevante) || blank($periodo->fundamento_cambio_climatico)) $pendientes->push('Evaluaci&oacute;n de cambio clim&aacute;tico incompleta');

        return [
            'foda' => $periodo->contextos->count(),
            'riesgos' => $periodo->riesgos->where('tipo', 'riesgo')->count(),
            'oportunidades' => $periodo->riesgos->where('tipo', 'oportunidad')->count(),
            'acciones_abiertas' => $accionesRiesgo->merge($accionesObjetivo)->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
            'objetivos' => $objetivos->count(),
            'objetivos_cumplidos' => $objetivos->where('cumplimiento_actual', 'cumplido')->count(),
            'objetivos_aceptables' => $objetivos->where('cumplimiento_actual', 'aceptable')->count(),
            'objetivos_incumplidos' => $objetivos->where('cumplimiento_actual', 'incumplido')->count(),
            'partes' => $partes->count(),
            'proveedores' => $proveedores->count(),
            'proveedores_no_aprobados' => $evaluacionesProveedor->where('resultado', 'no_aprobado')->count(),
            'pendientes' => $pendientes,
        ];
    }
}
