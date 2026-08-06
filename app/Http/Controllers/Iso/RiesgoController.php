<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Iso\Contexto;
use App\Models\Iso\Periodo;
use App\Models\Iso\ParteInteresada;
use App\Models\Iso\Riesgo;
use App\Models\User;
use App\Services\Iso\CodigoIsoService;
use App\Services\Iso\PeriodoAbiertoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiesgoController extends Controller
{
    public function __construct(private readonly PeriodoAbiertoService $periodos) {}

    public function index(Request $request)
    {
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : (Periodo::where('estado', 'vigente')->first() ?? $periodos->first());
        $query = Riesgo::query()->with(['contexto', 'responsable', 'acciones'])
            ->withCount(['acciones as acciones_abiertas_count' => fn ($acciones) => $acciones->whereNotIn('estado', ['completada', 'cancelada'])]);
        $periodo ? $query->where('periodo_id', $periodo->id) : $query->whereRaw('1=0');
        if ($request->filled('tipo')) $query->where('tipo', $request->string('tipo'));
        if ($request->filled('estado')) $query->where('estado', $request->string('estado'));
        if ($request->boolean('verificacion_vencida')) {
            $query->whereNotIn('estado', ['finalizado', 'anulado'])->whereDate('fecha_verificacion_prevista', '<=', today());
        }
        $ordenes = [
            'codigo' => 'codigo', 'tipo' => 'tipo', 'identificacion' => 'identificacion', 'proceso' => 'proceso',
            'indice' => 'indice_inicial', 'acciones' => 'acciones_abiertas_count', 'estado' => 'estado',
            'verificacion' => 'fecha_verificacion_prevista',
        ];
        $orden = $request->string('orden')->toString();
        $direccion = $request->string('direccion')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        if (isset($ordenes[$orden])) {
            $query->orderBy($ordenes[$orden], $direccion)->orderBy('codigo');
        } else {
            $query->orderByDesc('indice_inicial')->orderBy('codigo');
        }
        $riesgos = $query->get();
        return view('iso.riesgos.index', compact('periodos', 'periodo', 'riesgos'));
    }

    public function create(Request $request)
    {
        $contexto = $request->filled('contexto') ? Contexto::with('periodo')->findOrFail($request->integer('contexto')) : null;
        $periodo = $contexto?->periodo
            ?? ($request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : Periodo::where('estado', 'vigente')->firstOrFail());
        $this->periodos->validar($periodo, 'crear riesgos u oportunidades');
        $usuarios = User::habilitados()->orderBy('name')->get();
        $procesos = collect(config('iso.procesos'));
        $partesCatalogo = collect(config('iso.partes_interesadas'))
            ->merge(ParteInteresada::where('estado', 'activa')->pluck('nombre'))->unique()->values();
        return view('iso.riesgos.create', compact('contexto', 'periodo', 'usuarios', 'procesos', 'partesCatalogo'));
    }

    public function store(Request $request, CodigoIsoService $codigos)
    {
        $proceso = $request->input('proceso') === '__otro__' ? $request->input('proceso_otro') : $request->input('proceso');
        $partes = collect($request->input('partes_interesadas_seleccion', []))->reject(fn ($parte) => $parte === '__otro__');
        if ($request->input('partes_interesadas_otro')) $partes->push($request->input('partes_interesadas_otro'));
        $request->merge(['proceso' => $proceso, 'partes_interesadas' => $partes->filter()->unique()->implode('; ')]);
        $data = $request->validate([
            'periodo_id' => 'required|exists:iso_periodos,id', 'contexto_id' => 'nullable|exists:iso_contextos,id',
            'tipo' => ['required', Rule::in(['riesgo', 'oportunidad'])], 'proceso' => 'required|string|max:255',
            'identificacion' => 'required|string|max:5000', 'partes_interesadas' => 'nullable|string|max:3000', 'efecto_potencial' => 'required|string|max:5000',
            'criterio_eficacia' => 'required|string|max:3000',
            'impacto_inicial' => 'required|integer|min:1|max:3', 'probabilidad_inicial' => 'required|integer|min:1|max:3',
            'responsable_id' => 'nullable|exists:users,id', 'fecha_verificacion_prevista' => 'nullable|date',
            'accion_descripcion' => 'nullable|string|max:5000', 'accion_responsable_id' => 'nullable|exists:users,id', 'accion_fecha_objetivo' => 'nullable|date',
        ]);
        $periodo = Periodo::findOrFail($data['periodo_id']);
        $this->periodos->validar($periodo, 'crear riesgos u oportunidades');
        if (!empty($data['contexto_id'])) {
            $contexto = Contexto::findOrFail($data['contexto_id']);
            abort_unless($contexto->periodo_id === $periodo->id, 422, 'El contexto no pertenece al período.');
        }
        if (filled($data['accion_descripcion'] ?? null) && (blank($data['accion_responsable_id'] ?? null) || blank($data['accion_fecha_objetivo'] ?? null))) {
            return back()->withErrors(['accion_descripcion' => 'Para crear la acción se requieren responsable y fecha objetivo.'])->withInput();
        }
        $riesgo = DB::transaction(function () use ($data, $periodo, $request, $codigos) {
            [$numero, $codigo] = $codigos->siguienteRiesgo($periodo);
            $riesgo = Riesgo::create([
                ...collect($data)->except(['accion_descripcion', 'accion_responsable_id', 'accion_fecha_objetivo'])->all(),
                'numero' => $numero, 'codigo' => $codigo,
                'indice_inicial' => $data['impacto_inicial'] * $data['probabilidad_inicial'],
                'estado' => filled($data['accion_descripcion'] ?? null) ? 'en_proceso' : 'pendiente',
                'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id,
            ]);
            if (filled($data['accion_descripcion'] ?? null)) {
                $riesgo->acciones()->create(['descripcion' => $data['accion_descripcion'], 'responsable_id' => $data['accion_responsable_id'], 'fecha_objetivo' => $data['accion_fecha_objetivo'], 'estado' => 'pendiente', 'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
            }
            return $riesgo;
        });
        return redirect()->route('planificacion.riesgos.show', $riesgo)->with('success', 'Riesgo u oportunidad registrado.');
    }

    public function show(Request $request, Riesgo $riesgo)
    {
        $riesgo->load(['periodo', 'contexto', 'origenContinuidad.periodo', 'continuidades.periodo', 'responsable', 'acciones.responsable', 'acciones.seguimientos.documento', 'acciones.transiciones.realizadoPor', 'verificaciones.verificador', 'verificaciones.documento', 'transiciones.realizadoPor']);
        $usuarios = User::habilitados()->orderBy('name')->get();
        $documentos = Documento::whereRaw("LOWER(estado) IN ('aprobado','registro')")
            ->when(!$request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereHas('permisos', function ($permisos) use ($request) {
                    $permisos->where('user_id', $request->user()->id)
                        ->where(function ($acceso) {
                            $acceso->where('puede_leer', true)
                                ->orWhere('puede_escribir', true)
                                ->orWhere('puede_aprobar', true)
                                ->orWhere('puede_eliminar', true);
                        });
                });
            })
            ->orderBy('titulo')->get(['id', 'titulo']);
        return view('iso.riesgos.show', compact('riesgo', 'usuarios', 'documentos'));
    }

    public function verificar(Request $request, Riesgo $riesgo)
    {
        $this->periodos->validar($riesgo->periodo()->firstOrFail(), 'evaluar la eficacia');
        abort_if($riesgo->estado === 'finalizado', 422, 'El registro está finalizado. Agregá una nueva acción para reabrirlo antes de evaluarlo nuevamente.');
        $data = $request->validate([
            'decision' => ['required', Rule::in(['continuar', 'finalizar'])],
            'fecha' => 'required|date', 'eficacia' => ['required', Rule::in(['si', 'parcial', 'no'])],
            'conclusion' => 'required|string|max:5000', 'documento_id' => 'nullable|exists:documentos,id',
            'enlace_externo' => 'nullable|url|max:2000',
            'criterio_eficacia' => 'required|string|max:3000',
            'impacto_final' => 'required|integer|min:1|max:3', 'probabilidad_final' => 'required|integer|min:1|max:3',
            'proxima_evaluacion' => 'exclude_unless:decision,continuar|nullable|date|after_or_equal:fecha',
            'justificacion_excepcion' => 'nullable|string|max:3000',
        ]);
        if (!empty($data['documento_id'])) {
            $documento = Documento::findOrFail($data['documento_id']);
            abort_unless($request->user()->isAdmin() || $documento->puedeLeer($request->user()), 403, 'No tenés permiso para vincular este documento.');
        }

        if ($data['decision'] === 'continuar' && blank($data['proxima_evaluacion'] ?? null)) {
            return back()->withErrors(['proxima_evaluacion' => 'Indicá la próxima fecha de evaluación para continuar el tratamiento.'])->withInput();
        }
        if ($data['decision'] === 'continuar' && $data['eficacia'] === 'si' && blank($data['justificacion_excepcion'] ?? null)) {
            return back()->withErrors(['justificacion_excepcion' => 'Explicá por qué el tratamiento continuará aunque el resultado haya sido eficaz.'])->withInput();
        }
        if ($data['decision'] === 'finalizar' && in_array($data['eficacia'], ['parcial', 'no']) && blank($data['justificacion_excepcion'] ?? null)) {
            return back()->withErrors(['justificacion_excepcion' => 'Para cerrar con eficacia parcial o no eficaz, justificá expresamente la aceptación del riesgo residual.'])->withInput();
        }
        $indiceFinal = $data['impacto_final'] * $data['probabilidad_final'];
        if ($riesgo->tipo === 'riesgo' && $data['eficacia'] === 'si' && $indiceFinal >= $riesgo->indice_inicial && blank($data['justificacion_excepcion'] ?? null)) {
            return back()->withErrors(['justificacion_excepcion' => 'Para declarar eficaz un riesgo sin reducción del índice, explicá la excepción y la evidencia que la sustenta.'])->withInput();
        }
        if ($riesgo->tipo === 'oportunidad' && $data['eficacia'] === 'si' && $indiceFinal <= $riesgo->indice_inicial && blank($data['justificacion_excepcion'] ?? null)) {
            return back()->withErrors(['justificacion_excepcion' => 'Para declarar eficaz una oportunidad sin mejora del índice, explicá la excepción y la evidencia que la sustenta.'])->withInput();
        }

        $finaliza = $data['decision'] === 'finalizar';
        DB::transaction(function () use ($riesgo, $request, $data, $indiceFinal, $finaliza) {
            $riesgo->verificaciones()->create([
                'tipo' => $finaliza ? 'final' : 'intermedia', 'fecha' => $data['fecha'], 'eficacia' => $data['eficacia'], 'conclusion' => $data['conclusion'],
                'impacto' => $data['impacto_final'], 'probabilidad' => $data['probabilidad_final'], 'indice' => $indiceFinal,
                'estado_resultante' => $finaliza ? 'finalizado' : 'en_proceso', 'justificacion_excepcion' => $data['justificacion_excepcion'] ?? null,
                'documento_id' => $data['documento_id'] ?? null, 'enlace_externo' => $data['enlace_externo'] ?? null,
                'verificado_por' => $request->user()->id,
            ]);
            $riesgo->update([
                'criterio_eficacia' => $data['criterio_eficacia'], 'eficacia' => $data['eficacia'], 'conclusion_eficacia' => $data['conclusion'],
                'impacto_final' => $data['impacto_final'], 'probabilidad_final' => $data['probabilidad_final'], 'indice_final' => $indiceFinal,
                'fecha_verificacion_prevista' => $finaliza ? null : $data['proxima_evaluacion'],
                'estado' => $finaliza ? 'finalizado' : 'en_proceso', 'actualizado_por' => $request->user()->id,
                'finalizado_en' => $finaliza ? now() : null,
            ]);
        });
        return back()->with('success', $finaliza ? 'Evaluación registrada y riesgo finalizado.' : 'Evaluación registrada. El tratamiento continúa con una próxima fecha programada.');
    }

    public function actualizarFechaVerificacion(Request $request, Riesgo $riesgo)
    {
        $this->periodos->validar($riesgo->periodo()->firstOrFail(), 'reprogramar la evaluación de eficacia');
        abort_if($riesgo->estado === 'finalizado', 422, 'No se puede reprogramar una evaluación sobre un registro finalizado.');
        $data = $request->validate([
            'fecha_verificacion_prevista' => 'nullable|date',
        ]);
        $riesgo->update([
            'fecha_verificacion_prevista' => $data['fecha_verificacion_prevista'] ?? null,
            'actualizado_por' => $request->user()->id,
        ]);

        return back()->with('success', 'Fecha prevista para evaluar la eficacia actualizada.');
    }
}
