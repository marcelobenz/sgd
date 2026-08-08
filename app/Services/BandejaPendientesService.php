<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Iso\Accion;
use App\Models\Iso\ObjetivoAccion;
use App\Models\Iso\Periodo;
use App\Models\Iso\ProveedorAccion;
use App\Models\Iso\Riesgo;
use App\Models\RecordatorioEjecucion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BandejaPendientesService
{
    public function paraUsuario(User $user, ?Periodo $periodo = null, int $horizonteDias = 7): Collection
    {
        $pendientes = collect()
            ->concat($this->aprobacionesDocumentales($user))
            ->concat($this->revisionesDocumentales($user));

        if ($user->puedeGestionarPlanificacion()) {
            $pendientes = $pendientes
                ->concat($this->accionesDeRiesgo($user, $periodo))
                ->concat($this->verificacionesDeRiesgo($user, $periodo))
                ->concat($this->accionesDeObjetivo($user, $periodo))
                ->concat($this->accionesDeProveedor($user, $periodo));
        }

        return $pendientes
            ->map(fn (array $pendiente) => $pendiente + [
                'prioridad' => $this->prioridad($pendiente['fecha'], $horizonteDias),
            ])
            ->sortBy([
                fn (array $a, array $b) => $this->ordenPrioridad($a['prioridad']) <=> $this->ordenPrioridad($b['prioridad']),
                fn (array $a, array $b) => ($a['fecha']?->timestamp ?? PHP_INT_MAX) <=> ($b['fecha']?->timestamp ?? PHP_INT_MAX),
            ])
            ->values();
    }

    public function resumen(Collection $pendientes): array
    {
        return [
            'total' => $pendientes->count(),
            'vencidas' => $pendientes->where('prioridad', 'vencida')->count(),
            'hoy' => $pendientes->where('prioridad', 'hoy')->count(),
            'proximas' => $pendientes->where('prioridad', 'proxima')->count(),
            'sin_fecha' => $pendientes->where('prioridad', 'sin_fecha')->count(),
            'iso' => $pendientes->where('grupo', 'iso')->count(),
            'documentos' => $pendientes->where('grupo', 'documentos')->count(),
        ];
    }

    private function aprobacionesDocumentales(User $user): Collection
    {
        return Documento::query()
            ->whereRaw('LOWER(estado) = ?', ['pendiente de aprobación'])
            ->whereHas('permisos', fn ($query) => $query
                ->where('user_id', $user->id)
                ->where('puede_aprobar', true))
            ->latest('updated_at')
            ->get()
            ->map(fn (Documento $documento) => [
                'clave' => "documento-aprobar-{$documento->id}",
                'grupo' => 'documentos',
                'tipo' => 'aprobacion_documento',
                'origen' => 'Aprobación documental',
                'titulo' => $documento->titulo,
                'descripcion' => 'Revisar y aprobar o rechazar la versión pendiente.',
                'fecha' => null,
                'estado' => 'Pendiente',
                'url' => route('documentos.validaPermiso', [
                    'id' => $documento->id,
                    'ruta' => 'documentos.show',
                    'permiso' => 'puedeAprobar',
                ]),
                'icono' => 'fa-file-signature',
            ]);
    }

    private function revisionesDocumentales(User $user): Collection
    {
        return RecordatorioEjecucion::query()
            ->with(['documento', 'recordatorio'])
            ->where('user_id', $user->id)
            ->whereIn('estado', ['pendiente', 'postergado'])
            ->get()
            ->map(function (RecordatorioEjecucion $ejecucion) {
                $fecha = $ejecucion->postergado_hasta ?? $ejecucion->fecha_programada;

                return [
                    'clave' => "revision-documento-{$ejecucion->id}",
                    'grupo' => 'documentos',
                    'tipo' => 'revision_documento',
                    'origen' => 'Revisión documental',
                    'titulo' => $ejecucion->recordatorio?->nombre ?? 'Revisión programada',
                    'descripcion' => $ejecucion->documento?->titulo ?? 'Documento no disponible',
                    'fecha' => $fecha,
                    'estado' => $ejecucion->estado === 'postergado' ? 'Postergada' : 'Pendiente',
                    'url' => route('recordatorios.mis'),
                    'icono' => 'fa-clipboard-check',
                ];
            });
    }

    private function accionesDeRiesgo(User $user, ?Periodo $periodo): Collection
    {
        return Accion::query()
            ->with('riesgo')
            ->where('responsable_id', $user->id)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->when($periodo, fn ($query) => $query->whereHas('riesgo', fn ($riesgo) => $riesgo->where('periodo_id', $periodo->id)))
            ->get()
            ->map(fn (Accion $accion) => [
                'clave' => "accion-riesgo-{$accion->id}",
                'grupo' => 'iso',
                'tipo' => 'accion_riesgo',
                'origen' => 'Riesgos y oportunidades',
                'titulo' => $accion->descripcion,
                'descripcion' => trim(($accion->riesgo?->codigo ? $accion->riesgo->codigo.' · ' : '').($accion->riesgo?->identificacion ?? '')),
                'fecha' => $accion->fecha_objetivo,
                'estado' => $accion->estado === 'en_proceso' ? 'En proceso' : 'Pendiente',
                'url' => route('planificacion.riesgos.show', $accion->riesgo_id),
                'icono' => 'fa-shield-halved',
            ]);
    }

    private function verificacionesDeRiesgo(User $user, ?Periodo $periodo): Collection
    {
        return Riesgo::query()
            ->where('responsable_id', $user->id)
            ->whereNotIn('estado', ['finalizado', 'anulado'])
            ->whereNotNull('fecha_verificacion_prevista')
            ->when($periodo, fn ($query) => $query->where('periodo_id', $periodo->id))
            ->get()
            ->map(fn (Riesgo $riesgo) => [
                'clave' => "verificacion-riesgo-{$riesgo->id}",
                'grupo' => 'iso',
                'tipo' => 'verificacion_riesgo',
                'origen' => 'Verificación de eficacia',
                'titulo' => "Verificar {$riesgo->codigo}",
                'descripcion' => $riesgo->identificacion,
                'fecha' => $riesgo->fecha_verificacion_prevista,
                'estado' => 'Pendiente',
                'url' => route('planificacion.riesgos.show', $riesgo),
                'icono' => 'fa-magnifying-glass-chart',
            ]);
    }

    private function accionesDeObjetivo(User $user, ?Periodo $periodo): Collection
    {
        return ObjetivoAccion::query()
            ->with('objetivo')
            ->where('responsable_id', $user->id)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->when($periodo, fn ($query) => $query->whereHas('objetivo', fn ($objetivo) => $objetivo->where('periodo_id', $periodo->id)))
            ->get()
            ->map(fn (ObjetivoAccion $accion) => [
                'clave' => "accion-objetivo-{$accion->id}",
                'grupo' => 'iso',
                'tipo' => 'accion_objetivo',
                'origen' => 'Objetivos de calidad',
                'titulo' => $accion->descripcion,
                'descripcion' => trim(($accion->objetivo?->codigo ? $accion->objetivo->codigo.' · ' : '').($accion->objetivo?->titulo ?? '')),
                'fecha' => $accion->fecha_objetivo,
                'estado' => $accion->estado === 'en_proceso' ? 'En proceso' : 'Pendiente',
                'url' => route('planificacion.objetivos.show', $accion->objetivo_id),
                'icono' => 'fa-bullseye',
            ]);
    }

    private function accionesDeProveedor(User $user, ?Periodo $periodo): Collection
    {
        return ProveedorAccion::query()
            ->with('evaluacion.proveedor')
            ->where('responsable_id', $user->id)
            ->whereIn('estado', ['pendiente', 'en_proceso'])
            ->when($periodo, fn ($query) => $query->whereHas('evaluacion', fn ($evaluacion) => $evaluacion->where('periodo_id', $periodo->id)))
            ->get()
            ->map(fn (ProveedorAccion $accion) => [
                'clave' => "accion-proveedor-{$accion->id}",
                'grupo' => 'iso',
                'tipo' => 'accion_proveedor',
                'origen' => 'Evaluación de proveedores',
                'titulo' => $accion->descripcion,
                'descripcion' => $accion->evaluacion?->proveedor?->nombre ?? 'Proveedor',
                'fecha' => $accion->fecha_objetivo,
                'estado' => $accion->estado === 'en_proceso' ? 'En proceso' : 'Pendiente',
                'url' => route('planificacion.proveedores.show', $accion->evaluacion?->proveedor_id),
                'icono' => 'fa-truck-field',
            ]);
    }

    private function prioridad(?CarbonInterface $fecha, int $horizonteDias): string
    {
        if (! $fecha) {
            return 'sin_fecha';
        }

        if ($fecha->isToday()) {
            return 'hoy';
        }

        if ($fecha->isPast()) {
            return 'vencida';
        }

        return $fecha->lte(now()->addDays($horizonteDias)) ? 'proxima' : 'futura';
    }

    private function ordenPrioridad(string $prioridad): int
    {
        return match ($prioridad) {
            'vencida' => 0,
            'hoy' => 1,
            'proxima' => 2,
            'futura' => 3,
            default => 4,
        };
    }
}
