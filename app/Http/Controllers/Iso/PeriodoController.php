<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Accion;
use App\Models\Iso\Objetivo;
use App\Models\Iso\ObjetivoAccion;
use App\Models\Iso\Periodo;
use App\Models\Iso\PeriodoTransicion;
use App\Models\Iso\Riesgo;
use App\Services\Iso\CodigoIsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodoController extends Controller
{
    public function __construct(private readonly CodigoIsoService $codigos) {}

    public function index()
    {
        $periodos = Periodo::withCount([
            'contextos',
            'riesgos',
            'contextos as contextos_pendientes_count' => fn ($query) => $query->where('decision', 'pendiente'),
            'riesgos as riesgos_abiertos_count' => fn ($query) => $query->whereIn('estado', ['pendiente', 'en_proceso']),
            'riesgos as permanentes_sin_control_count' => fn ($query) => $query->where('estado', 'permanente')
                ->where(function ($control) {
                    $control->whereNull('fecha_verificacion_prevista')
                        ->orWhereDate('fecha_verificacion_prevista', '<', today())
                        ->orWhereDoesntHave('verificaciones');
                }),
            'riesgos as verificaciones_pendientes_count' => fn ($query) => $query->where('eficacia', 'pendiente'),
        ])->with(['transiciones.realizadoPor'])->orderByDesc('anio')->get();

        foreach ($periodos as $periodo) {
            $periodo->acciones_abiertas_count = Accion::whereHas('riesgo', fn ($query) => $query->where('periodo_id', $periodo->id))
                ->whereIn('estado', ['pendiente', 'en_proceso'])->count();
            $periodo->objetivos_sin_cierre_count = Objetivo::where('periodo_id', $periodo->id)
                ->whereDoesntHave('evaluaciones', fn ($query) => $query->where('tipo', 'cierre_periodo'))->count();
            $periodo->objetivo_acciones_abiertas_count = ObjetivoAccion::whereHas('objetivo', fn ($query) => $query->where('periodo_id', $periodo->id))
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
        abort_if($periodo->estado === 'vigente', 422, 'El período ya se encuentra vigente.');
        $data = $request->validate([
            'motivo' => 'required|string|min:10|max:1000',
            'comprende_impacto' => 'accepted',
            'confirmacion' => 'required|in:ACTIVAR ' . $periodo->anio,
            'trasladar_permanentes' => 'nullable|boolean',
            'trasladar_objetivos' => 'nullable|boolean',
        ], [
            'confirmacion.in' => 'Escribí exactamente ACTIVAR ' . $periodo->anio . ' para confirmar.',
            'comprende_impacto.accepted' => 'Debés confirmar que comprendés el impacto de la activación.',
        ]);
        $continuidades = DB::transaction(function () use ($periodo, $request, $data) {
            $vigenteAnterior = Periodo::where('estado', 'vigente')->whereKeyNot($periodo->id)->lockForUpdate()->first();
            $periodoAnterior = $vigenteAnterior ?: Periodo::where('anio', '<', $periodo->anio)->orderByDesc('anio')->lockForUpdate()->first();
            Periodo::where('estado', 'vigente')->whereKeyNot($periodo->id)->update(['estado' => 'borrador']);
            $periodo->update(['estado' => 'vigente']);
            $continuidades = $request->boolean('trasladar_permanentes') && $periodoAnterior && $periodoAnterior->anio < $periodo->anio
                ? $this->trasladarRiesgosPermanentes($periodoAnterior, $periodo, $request->user()->id)
                : 0;
            $objetivosContinuados = $request->boolean('trasladar_objetivos') && $periodoAnterior && $periodoAnterior->anio < $periodo->anio
                ? $this->trasladarObjetivos($periodoAnterior, $periodo, $request->user()->id)
                : 0;
            PeriodoTransicion::create([
                'periodo_id' => $periodo->id, 'accion' => 'activacion', 'estado_anterior' => 'borrador',
                'estado_nuevo' => 'vigente', 'motivo' => $data['motivo'],
                'resumen_control' => ['periodo_vigente_desplazado' => $vigenteAnterior?->anio, 'riesgos_permanentes_trasladados' => $continuidades, 'objetivos_continuados' => $objetivosContinuados],
                'realizado_por' => $request->user()->id,
            ]);
            return ['riesgos' => $continuidades, 'objetivos' => $objetivosContinuados];
        });
        $mensaje = 'Período activado.';
        if ($continuidades['riesgos']) $mensaje .= " Se trasladaron {$continuidades['riesgos']} riesgos permanentes.";
        if ($continuidades['objetivos']) $mensaje .= " Se continuaron {$continuidades['objetivos']} objetivos de calidad.";
        return back()->with('success', $mensaje);
    }

    public function clima(Request $request, Periodo $periodo)
    {
        abort_if($periodo->estado === 'cerrado', 422, 'El período está cerrado. Reabrilo antes de modificar su evaluación climática.');
        $data = $request->validate(['cambio_climatico_relevante' => 'required|boolean', 'fundamento_cambio_climatico' => 'required|string|max:3000']);
        $periodo->update($data);
        return back()->with('success', 'Evaluación de cambio climático actualizada.');
    }

    public function cerrar(Request $request, Periodo $periodo)
    {
        abort_if($periodo->estado === 'cerrado', 422, 'El período ya está cerrado.');
        abort_if($periodo->contextos()->where('decision', 'pendiente')->exists(), 422, 'No se puede cerrar: hay elementos FODA pendientes de decisión.');
        abort_if($periodo->riesgos()->whereIn('estado', ['pendiente', 'en_proceso'])->exists(), 422, 'No se puede cerrar: hay riesgos u oportunidades pendientes o en proceso.');
        abort_if($this->permanentesSinControl($periodo)->exists(), 422, 'No se puede cerrar: hay riesgos permanentes sin evaluación vigente o sin próxima revisión programada.');
        abort_if(Accion::whereHas('riesgo', fn ($query) => $query->where('periodo_id', $periodo->id))->whereIn('estado', ['pendiente', 'en_proceso'])->exists(), 422, 'No se puede cerrar: hay acciones de riesgos u oportunidades abiertas.');
        abort_if(Objetivo::where('periodo_id', $periodo->id)->whereDoesntHave('evaluaciones', fn ($query) => $query->where('tipo', 'cierre_periodo'))->exists(), 422, 'No se puede cerrar: hay objetivos sin evaluación de cierre del período.');
        abort_if(ObjetivoAccion::whereHas('objetivo', fn ($query) => $query->where('periodo_id', $periodo->id))->whereIn('estado', ['pendiente', 'en_proceso'])->exists(), 422, 'No se puede cerrar: hay acciones de objetivos pendientes o en proceso.');
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
            'riesgos_abiertos' => $periodo->riesgos()->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
            'riesgos_permanentes_sin_control' => $this->permanentesSinControl($periodo)->count(),
            'acciones_abiertas' => Accion::whereHas('riesgo', fn ($query) => $query->where('periodo_id', $periodo->id))->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
            'objetivos_sin_evaluacion_cierre' => Objetivo::where('periodo_id', $periodo->id)->whereDoesntHave('evaluaciones', fn ($query) => $query->where('tipo', 'cierre_periodo'))->count(),
            'acciones_objetivos_abiertas' => ObjetivoAccion::whereHas('objetivo', fn ($query) => $query->where('periodo_id', $periodo->id))->whereIn('estado', ['pendiente', 'en_proceso'])->count(),
            'verificaciones_pendientes' => $periodo->riesgos()->where('eficacia', 'pendiente')->count(),
        ];
    }

    private function permanentesSinControl(Periodo $periodo)
    {
        return $periodo->riesgos()->where('estado', 'permanente')
            ->where(function ($control) {
                $control->whereNull('fecha_verificacion_prevista')
                    ->orWhereDate('fecha_verificacion_prevista', '<', today())
                    ->orWhereDoesntHave('verificaciones');
            });
    }

    private function trasladarRiesgosPermanentes(Periodo $origen, Periodo $destino, int $usuarioId): int
    {
        $trasladados = 0;
        $riesgos = $origen->riesgos()->where('estado', 'permanente')
            ->whereNotNull('fecha_verificacion_prevista')->whereHas('verificaciones')
            ->when($origen->estado !== 'cerrado', fn ($query) => $query->whereDate('fecha_verificacion_prevista', '>=', today()))
            ->get();

        foreach ($riesgos as $riesgo) {
            $origenId = $riesgo->id;
            if (Riesgo::where('periodo_id', $destino->id)->where('riesgo_origen_id', $origenId)->exists()) continue;
            [$numero, $codigo] = $this->codigos->siguienteRiesgo($destino);
            Riesgo::create([
                ...$riesgo->only(['tipo', 'proceso', 'identificacion', 'partes_interesadas', 'efecto_potencial', 'criterio_eficacia', 'impacto_inicial', 'probabilidad_inicial', 'indice_inicial', 'responsable_id', 'fecha_verificacion_prevista', 'eficacia', 'conclusion_eficacia', 'impacto_final', 'probabilidad_final', 'indice_final']),
                'periodo_id' => $destino->id, 'riesgo_origen_id' => $origenId, 'contexto_id' => null,
                'numero' => $numero, 'codigo' => $codigo, 'estado' => 'permanente',
                'creado_por' => $usuarioId, 'actualizado_por' => $usuarioId,
            ]);
            $trasladados++;
        }

        return $trasladados;
    }

    private function trasladarObjetivos(Periodo $origen, Periodo $destino, int $usuarioId): int
    {
        $trasladados = 0;
        $objetivos = $origen->objetivos()->with(['indicadorPrincipal', 'partes'])
            ->whereHas('evaluaciones', fn ($query) => $query->where('tipo', 'cierre_periodo')->whereIn('decision', ['continuar', 'reformular']))
            ->get();
        $diferenciaAnios = $destino->anio - $origen->anio;

        foreach ($objetivos as $objetivo) {
            if (Objetivo::where('periodo_id', $destino->id)->where('objetivo_origen_id', $objetivo->id)->exists()) continue;
            $numero = ((int) Objetivo::where('periodo_id', $destino->id)->lockForUpdate()->max('numero')) + 1;
            $continuidad = Objetivo::create([
                ...$objetivo->only(['compromiso_politica', 'proceso', 'titulo', 'descripcion', 'area_responsable', 'responsable_id', 'periodicidad_seguimiento', 'observaciones']),
                'periodo_id' => $destino->id, 'objetivo_origen_id' => $objetivo->id,
                'numero' => $numero, 'codigo' => 'OBJ-' . $destino->anio . '-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'fecha_inicio' => $objetivo->fecha_inicio->copy()->addYears($diferenciaAnios),
                'fecha_objetivo' => $objetivo->fecha_objetivo->copy()->addYears($diferenciaAnios),
                'estado' => 'activo', 'creado_por' => $usuarioId, 'actualizado_por' => $usuarioId,
            ]);
            if ($objetivo->indicadorPrincipal) {
                $continuidad->indicadores()->create([
                    ...$objetivo->indicadorPrincipal->only(['nombre', 'metodo_calculo', 'unidad', 'fuente', 'frecuencia', 'agregacion', 'comparador', 'meta', 'meta_hasta', 'tolerancia', 'linea_base', 'principal', 'activo']),
                    'creado_por' => $usuarioId, 'actualizado_por' => $usuarioId,
                ]);
            }
            $continuidad->partes()->sync($objetivo->partes->pluck('id'));
            $trasladados++;
        }

        return $trasladados;
    }
}
