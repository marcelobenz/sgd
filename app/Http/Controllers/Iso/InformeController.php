<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Periodo;
use App\Models\Iso\ParteInteresada;
use App\Models\Documento;
use App\Models\Iso\Objetivo;
use App\Models\Iso\Proveedor;
use App\Services\Iso\ResultadoObjetivoService;
use Illuminate\Http\Request;

class InformeController extends Controller
{
    public function index(Request $request)
    {
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : (Periodo::where('estado', 'vigente')->first() ?? $periodos->firstOrFail());
        $periodo->load(['contextos.responsable', 'riesgos.contexto', 'riesgos.responsable', 'riesgos.acciones.seguimientos.documento']);
        $partes = ParteInteresada::where('estado', 'activa')->with([
            'evaluaciones' => fn ($query) => $query->with(['periodo', 'evaluador', 'documento', 'riesgos'])->orderByDesc('fecha_evaluacion'),
        ])->orderBy('nombre')->get();
        $proveedores = Proveedor::where('estado', 'activo')->with([
            'documento', 'selecciones' => fn ($query) => $query->with(['evaluador', 'documento'])->latest('fecha'),
            'evaluaciones' => fn ($query) => $query->with(['evaluador', 'documento', 'riesgos', 'acciones.responsable', 'acciones.documento'])->latest('fecha_evaluacion'),
        ])->orderBy('nombre')->orderBy('producto_servicio')->get();
        $objetivos = Objetivo::where('periodo_id', $periodo->id)->with([
            'responsable', 'indicadorPrincipal.mediciones.documento',
            'acciones.responsable', 'acciones.documento', 'acciones.seguimientos.documento',
            'evaluaciones' => fn ($query) => $query->with(['evaluadoPor', 'documento'])->latest('fecha_evaluacion')->latest('id'),
            'revisiones' => fn ($query) => $query->with('realizadaPor')->latest('fecha_vigencia')->latest('id'),
        ])->orderBy('numero')->get();
        $servicioResultados = app(ResultadoObjetivoService::class);
        $objetivos->each(function (Objetivo $objetivo) use ($servicioResultados) {
            $resultado = $objetivo->indicadorPrincipal ? $servicioResultados->resultado($objetivo->indicadorPrincipal) : null;
            $objetivo->setAttribute('resultado_actual', $resultado);
            $objetivo->setAttribute('cumplimiento_actual', $objetivo->indicadorPrincipal ? $servicioResultados->cumplimiento($objetivo->indicadorPrincipal, $resultado) : 'pendiente');
        });
        $documentoIds = $partes->flatMap->evaluaciones->pluck('documento_id')
            ->merge($proveedores->pluck('documento_id'))
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
        return view('iso.informes.index', compact('periodos', 'periodo', 'partes', 'proveedores', 'objetivos', 'documentosAccesibles'));
    }
}
