<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Iso\Contexto;
use App\Models\Iso\HistorialCambio;
use App\Models\Iso\Objetivo;
use App\Models\Iso\ObjetivoAccion;
use App\Models\Iso\ObjetivoIndicador;
use App\Models\Iso\ParteInteresada;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;
use App\Models\User;
use App\Services\Iso\ResultadoObjetivoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ObjetivoController extends Controller
{
    public function __construct(private readonly ResultadoObjetivoService $resultados) {}

    public function index(Request $request)
    {
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo')
            ? $periodos->firstWhere('id', (int) $request->input('periodo'))
            : ($periodos->firstWhere('estado', 'vigente') ?? $periodos->first());

        $query = Objetivo::with(['responsable', 'indicadorPrincipal.mediciones', 'acciones', 'evaluaciones' => fn ($q) => $q->latest('fecha_evaluacion')]);
        if ($periodo) $query->where('periodo_id', $periodo->id); else $query->whereRaw('1=0');
        if ($request->filled('estado')) $query->where('estado', $request->string('estado'));
        if ($request->filled('proceso')) $query->where('proceso', $request->string('proceso'));

        $objetivos = $query->orderBy('numero')->get();
        $objetivos->each(function (Objetivo $objetivo) {
            $indicador = $objetivo->indicadorPrincipal;
            $resultado = $indicador ? $this->resultados->resultado($indicador) : null;
            $objetivo->setAttribute('resultado_actual', $resultado);
            $objetivo->setAttribute('cumplimiento_actual', $indicador ? $this->resultados->cumplimiento($indicador, $resultado) : 'pendiente');
        });

        $procesos = Objetivo::select('proceso')->distinct()->orderBy('proceso')->pluck('proceso');
        return view('iso.objetivos.index', compact('objetivos', 'periodos', 'periodo', 'procesos'));
    }

    public function create(Request $request)
    {
        $periodos = Periodo::whereIn('estado', ['borrador', 'vigente'])->orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? $periodos->firstWhere('id', (int) $request->input('periodo')) : ($periodos->firstWhere('estado', 'vigente') ?? $periodos->first());
        return view('iso.objetivos.create', $this->datosFormulario($periodos, $periodo));
    }

    public function store(Request $request)
    {
        $this->normalizarCatalogos($request);
        $data = $this->validarObjetivo($request);
        $periodo = Periodo::findOrFail($data['periodo_id']);
        $this->validarPeriodoAbierto($periodo);

        $objetivo = DB::transaction(function () use ($request, $data, $periodo) {
            Periodo::whereKey($periodo->id)->lockForUpdate()->first();
            $numero = ((int) Objetivo::where('periodo_id', $periodo->id)->max('numero')) + 1;
            $objetivo = Objetivo::create([
                ...collect($data)->only(['periodo_id', 'compromiso_politica', 'proceso', 'titulo', 'descripcion', 'area_responsable', 'responsable_id', 'fecha_inicio', 'fecha_objetivo', 'periodicidad_seguimiento', 'observaciones'])->all(),
                'numero' => $numero,
                'codigo' => 'OBJ-' . $periodo->anio . '-' . str_pad((string) $numero, 3, '0', STR_PAD_LEFT),
                'estado' => 'activo',
                'creado_por' => $request->user()->id,
                'actualizado_por' => $request->user()->id,
            ]);
            $objetivo->indicadores()->create($this->datosIndicador($data, $request->user()->id));
            $this->sincronizarRelaciones($objetivo, $data);
            if (filled($data['accion_descripcion'] ?? null)) {
                $objetivo->acciones()->create([
                    'descripcion' => $data['accion_descripcion'],
                    'area_responsable' => $data['accion_area_responsable'],
                    'responsable_id' => $data['accion_responsable_id'] ?? null,
                    'fecha_objetivo' => $data['accion_fecha_objetivo'],
                    'estado' => 'pendiente',
                    'creado_por' => $request->user()->id,
                    'actualizado_por' => $request->user()->id,
                ]);
            }
            return $objetivo;
        });

        return redirect()->route('planificacion.objetivos.show', $objetivo)->with('success', 'Objetivo de calidad creado. Ya podés registrar sus mediciones y evidencias.');
    }

    public function show(Request $request, Objetivo $objetivo)
    {
        $objetivo->load([
            'periodo', 'responsable', 'indicadores.mediciones.documento',
            'acciones' => fn ($q) => $q->with(['responsable', 'documento', 'seguimientos.documento', 'transiciones.usuario'])->orderBy('fecha_objetivo'),
            'evaluaciones' => fn ($q) => $q->with(['evaluadoPor', 'documento'])->latest('fecha_evaluacion')->latest('id'),
            'contextos', 'riesgos', 'partes',
            'revisiones' => fn ($q) => $q->with('realizadaPor')->latest('fecha_vigencia')->latest('id'),
        ]);
        $indicador = $objetivo->indicadorPrincipal;
        $resultado = $indicador ? $this->resultados->resultado($indicador) : null;
        $cumplimiento = $indicador ? $this->resultados->cumplimiento($indicador, $resultado) : 'pendiente';
        $periodos = Periodo::whereIn('estado', ['borrador', 'vigente'])->orderByDesc('anio')->get();
        $datos = $this->datosFormulario($periodos, $objetivo->periodo);
        $datos += [
            'objetivo' => $objetivo, 'indicador' => $indicador, 'resultado' => $resultado,
            'cumplimiento' => $cumplimiento, 'documentos' => $this->documentosAccesibles($request),
            'tieneActividad' => $objetivo->tieneActividadGestion(),
        ];
        return view('iso.objetivos.show', $datos);
    }

    public function update(Request $request, Objetivo $objetivo)
    {
        $this->validarEditable($objetivo);
        if ($objetivo->tieneActividadGestion()) {
            throw ValidationException::withMessages(['motivo_cambio' => 'El objetivo ya tiene actividad. Utilizá la revisión controlada para modificar su planificación.']);
        }
        $this->normalizarCatalogos($request);
        $data = $this->validarObjetivo($request, false);
        $control = $request->validate([
            'motivo_cambio' => 'required|string|min:5|max:2000',
            'confirmacion_cambio' => 'accepted',
        ]);
        abort_if((int) $data['periodo_id'] !== $objetivo->periodo_id, 422, 'El período de un objetivo existente no puede modificarse.');
        DB::transaction(function () use ($request, $objetivo, $data, $control) {
            $anterior = $this->snapshotPlanificacion($objetivo);
            $this->aplicarPlanificacion($objetivo, $data, $request->user()->id);
            $objetivo->revisiones()->create([
                'tipo' => 'correccion', 'fecha_vigencia' => today(), 'motivo' => $control['motivo_cambio'],
                'valores_anteriores' => $anterior, 'valores_nuevos' => $this->snapshotPlanificacion($objetivo->fresh()),
                'realizada_por' => $request->user()->id,
            ]);
        });
        return back()->with('success', 'Corrección registrada. Los valores anteriores quedaron conservados en el historial.');
    }

    public function revisar(Request $request, Objetivo $objetivo)
    {
        $this->validarEditable($objetivo);
        if (!$objetivo->tieneActividadGestion()) {
            throw ValidationException::withMessages(['motivo_cambio' => 'El objetivo todavía no tiene actividad. Corregí la ficha mediante la edición administrativa.']);
        }
        $this->normalizarCatalogos($request);
        $data = $this->validarObjetivo($request, false);
        $control = $request->validate([
            'fecha_vigencia' => 'required|date|before_or_equal:today',
            'motivo_cambio' => 'required|string|min:10|max:2000',
            'confirmacion_cambio' => 'accepted',
        ]);
        abort_if((int) $data['periodo_id'] !== $objetivo->periodo_id, 422, 'El período de un objetivo existente no puede modificarse.');

        DB::transaction(function () use ($request, $objetivo, $data, $control) {
            $anterior = $this->snapshotPlanificacion($objetivo);
            $this->aplicarPlanificacion($objetivo, $data, $request->user()->id);
            $objetivo->revisiones()->create([
                'tipo' => 'revision', 'fecha_vigencia' => $control['fecha_vigencia'], 'motivo' => $control['motivo_cambio'],
                'valores_anteriores' => $anterior, 'valores_nuevos' => $this->snapshotPlanificacion($objetivo->fresh()),
                'realizada_por' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Revisión de la planificación registrada. Las evaluaciones anteriores conservaron sus resultados.');
    }

    public function destroy(Request $request, Objetivo $objetivo)
    {
        $this->validarPeriodoAbierto($objetivo->periodo()->firstOrFail());
        if ($objetivo->tieneActividadGestion()) {
            throw ValidationException::withMessages(['eliminar_objetivo' => 'No puede eliminarse porque ya tiene mediciones, evaluaciones o acciones iniciadas. Conservá el registro y gestioná su cierre o suspensión.']);
        }
        $data = $request->validate([
            'motivo_eliminacion' => 'required|string|min:5|max:2000',
            'comprende_eliminacion' => 'accepted',
            'confirmacion_eliminacion' => ['required', Rule::in(['ELIMINAR OBJETIVO'])],
        ]);
        $periodoId = $objetivo->periodo_id;

        DB::transaction(function () use ($request, $objetivo, $data) {
            HistorialCambio::create([
                'entidad_tipo' => $objetivo->getMorphClass(), 'entidad_id' => $objetivo->id,
                'evento' => 'eliminado_por_error',
                'valores_anteriores' => $this->snapshotPlanificacion($objetivo) + ['motivo_eliminacion' => $data['motivo_eliminacion']],
                'valores_nuevos' => null, 'user_id' => $request->user()->id,
            ]);
            $objetivo->delete();
        });

        return redirect()->route('planificacion.objetivos.index', ['periodo' => $periodoId])->with('success', 'Objetivo creado por error eliminado. La operación quedó registrada en auditoría.');
    }

    public function medir(Request $request, Objetivo $objetivo, ObjetivoIndicador $indicador)
    {
        $this->validarEditable($objetivo);
        abort_unless($indicador->objetivo_id === $objetivo->id, 404);
        $data = $request->validate([
            'fecha_medicion' => 'required|date', 'periodo_referencia' => 'nullable|string|max:100',
            'valor' => 'required|numeric', 'observaciones' => 'nullable|string|max:5000',
            'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000',
        ]);
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        $indicador->mediciones()->create($data + ['registrado_por' => $request->user()->id]);
        return back()->with('success', 'Medición registrada. El resultado del objetivo fue recalculado.');
    }

    public function storeAccion(Request $request, Objetivo $objetivo)
    {
        $this->validarEditable($objetivo);
        $request->merge(['area_responsable' => $request->input('area_responsable') === '__otro__' ? $request->input('area_responsable_otro') : $request->input('area_responsable')]);
        $data = $request->validate([
            'descripcion' => 'required|string|max:5000', 'area_responsable' => 'required|string|max:255',
            'responsable_id' => 'nullable|exists:users,id', 'fecha_objetivo' => 'required|date',
        ]);
        $objetivo->acciones()->create($data + ['estado' => 'pendiente', 'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Acción agregada al objetivo.');
    }

    public function updateAccion(Request $request, ObjetivoAccion $accion)
    {
        $this->validarEditable($accion->objetivo);
        if (in_array($accion->estado, ['completada', 'cancelada'], true)) {
            throw ValidationException::withMessages(['estado' => 'La acción está cerrada. Reabrila antes de modificarla.']);
        }
        $data = $request->validate([
            'estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completada', 'cancelada'])],
            'resultado' => 'nullable|string|max:5000', 'documento_id' => 'nullable|exists:documentos,id',
            'enlace_externo' => 'nullable|url|max:2000',
        ]);
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        if (in_array($data['estado'], ['completada', 'cancelada'], true) && blank($data['resultado'])) {
            throw ValidationException::withMessages(['resultado' => 'Registrá el resultado obtenido o el motivo de cancelación.']);
        }
        $accion->update($data + ['cerrada_en' => in_array($data['estado'], ['completada', 'cancelada'], true) ? now() : null, 'actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Acción actualizada.');
    }

    public function reabrirAccion(Request $request, ObjetivoAccion $accion)
    {
        $this->validarEditable($accion->objetivo);
        abort_unless(in_array($accion->estado, ['completada', 'cancelada'], true), 422, 'La acción no se encuentra cerrada.');
        $data = $request->validate([
            'motivo' => 'required|string|min:5|max:2000', 'comprende_impacto' => 'accepted',
            'confirmacion' => ['required', Rule::in(['REABRIR ACCION'])],
        ]);
        DB::transaction(function () use ($accion, $request, $data) {
            $anterior = $accion->estado;
            $accion->update(['estado' => 'en_proceso', 'resultado' => null, 'cerrada_en' => null, 'actualizado_por' => $request->user()->id]);
            $accion->transiciones()->create(['estado_anterior' => $anterior, 'estado_nuevo' => 'en_proceso', 'motivo' => $data['motivo'], 'user_id' => $request->user()->id]);
        });
        return back()->with('success', 'Acción reabierta. La justificación quedó registrada.');
    }

    public function seguimientoAccion(Request $request, ObjetivoAccion $accion)
    {
        $this->validarEditable($accion->objetivo);
        $data = $request->validate([
            'fecha' => 'required|date', 'detalle' => 'required|string|max:5000',
            'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000',
        ]);
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        if (in_array($accion->estado, ['completada', 'cancelada'], true) && empty($data['documento_id']) && empty($data['enlace_externo'])) {
            throw ValidationException::withMessages(['documento_id' => 'En una acción cerrada solo puede agregarse evidencia complementaria. Vinculá un documento o enlace.']);
        }
        $accion->seguimientos()->create($data + ['registrado_por' => $request->user()->id]);
        return back()->with('success', 'Seguimiento registrado.');
    }

    public function evaluar(Request $request, Objetivo $objetivo)
    {
        $this->validarEditable($objetivo);
        $indicador = $objetivo->indicadorPrincipal()->firstOrFail();
        $resultado = $this->resultados->resultado($indicador);
        if ($resultado === null) throw ValidationException::withMessages(['cumplimiento' => 'Registrá al menos una medición antes de evaluar el objetivo.']);
        $calculado = $this->resultados->cumplimiento($indicador, $resultado);
        $data = $request->validate([
            'fecha_evaluacion' => 'required|date', 'cumplimiento' => ['required', Rule::in(['cumplido', 'aceptable', 'incumplido'])],
            'conclusion' => 'required|string|max:5000', 'justificacion' => 'nullable|string|max:5000',
            'decision' => ['required', Rule::in(['continuar', 'reformular', 'cerrar', 'suspender'])],
            'proxima_evaluacion' => 'nullable|date|after:fecha_evaluacion',
            'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000',
        ]);
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        if (($data['cumplimiento'] === 'aceptable' || $data['cumplimiento'] !== $calculado) && blank($data['justificacion'] ?? null)) {
            throw ValidationException::withMessages(['justificacion' => 'Justificá el resultado aceptable o la excepción respecto del cálculo automático.']);
        }
        if (in_array($data['decision'], ['continuar', 'reformular'], true) && blank($data['proxima_evaluacion'])) {
            throw ValidationException::withMessages(['proxima_evaluacion' => 'Programá la próxima evaluación porque el objetivo continuará abierto.']);
        }
        DB::transaction(function () use ($request, $objetivo, $resultado, $data) {
            $objetivo->evaluaciones()->create($data + ['resultado' => $resultado, 'evaluado_por' => $request->user()->id]);
            $estado = match ($data['decision']) { 'cerrar' => 'cerrado', 'suspender' => 'suspendido', default => 'activo' };
            $objetivo->update(['estado' => $estado, 'actualizado_por' => $request->user()->id]);
        });
        return back()->with('success', 'Evaluación del objetivo registrada.');
    }

    private function validarObjetivo(Request $request, bool $incluyeAccion = true): array
    {
        $reglas = [
            'periodo_id' => 'required|exists:iso_periodos,id', 'compromiso_politica' => 'required|string|max:5000',
            'proceso' => 'required|string|max:255', 'titulo' => 'required|string|max:255', 'descripcion' => 'nullable|string|max:5000',
            'area_responsable' => 'required|string|max:255', 'responsable_id' => 'nullable|exists:users,id',
            'fecha_inicio' => 'required|date', 'fecha_objetivo' => 'required|date|after_or_equal:fecha_inicio',
            'periodicidad_seguimiento' => 'required|string|max:50', 'observaciones' => 'nullable|string|max:5000',
            'indicador_nombre' => 'required|string|max:255', 'indicador_metodo_calculo' => 'required|string|max:5000',
            'indicador_unidad' => 'required|string|max:50', 'indicador_fuente' => 'required|string|max:255',
            'indicador_frecuencia' => 'required|string|max:50',
            'indicador_agregacion' => ['required', Rule::in(['ultimo', 'promedio', 'suma', 'variacion', 'variacion_porcentual'])],
            'indicador_comparador' => ['required', Rule::in(['mayor_igual', 'mayor', 'menor_igual', 'menor', 'igual', 'rango'])],
            'indicador_meta' => 'required|numeric', 'indicador_meta_hasta' => 'nullable|numeric|required_if:indicador_comparador,rango',
            'indicador_tolerancia' => 'nullable|numeric|min:0', 'indicador_linea_base' => 'nullable|numeric',
            'contextos' => 'nullable|array', 'contextos.*' => 'exists:iso_contextos,id',
            'riesgos' => 'nullable|array', 'riesgos.*' => 'exists:iso_riesgos,id',
            'partes' => 'nullable|array', 'partes.*' => 'exists:iso_partes_interesadas,id',
        ];
        if ($incluyeAccion) $reglas += [
            'accion_descripcion' => 'nullable|string|max:5000', 'accion_area_responsable' => 'nullable|required_with:accion_descripcion|string|max:255',
            'accion_responsable_id' => 'nullable|exists:users,id', 'accion_fecha_objetivo' => 'nullable|required_with:accion_descripcion|date',
        ];
        return $request->validate($reglas);
    }

    private function datosIndicador(array $data, int $userId, bool $nuevo = true): array
    {
        $valores = [
            'nombre' => $data['indicador_nombre'], 'metodo_calculo' => $data['indicador_metodo_calculo'],
            'unidad' => $data['indicador_unidad'], 'fuente' => $data['indicador_fuente'], 'frecuencia' => $data['indicador_frecuencia'],
            'agregacion' => $data['indicador_agregacion'], 'comparador' => $data['indicador_comparador'],
            'meta' => $data['indicador_meta'], 'meta_hasta' => $data['indicador_meta_hasta'] ?? null,
            'tolerancia' => $data['indicador_tolerancia'] ?? null, 'linea_base' => $data['indicador_linea_base'] ?? null,
            'actualizado_por' => $userId,
        ];
        if ($nuevo) $valores += ['principal' => true, 'activo' => true, 'creado_por' => $userId];
        return $valores;
    }

    private function sincronizarRelaciones(Objetivo $objetivo, array $data): void
    {
        $objetivo->contextos()->sync($data['contextos'] ?? []);
        $objetivo->riesgos()->sync($data['riesgos'] ?? []);
        $objetivo->partes()->sync($data['partes'] ?? []);
    }

    private function aplicarPlanificacion(Objetivo $objetivo, array $data, int $userId): void
    {
        $objetivo->update([
            ...collect($data)->only(['compromiso_politica', 'proceso', 'titulo', 'descripcion', 'area_responsable', 'responsable_id', 'fecha_inicio', 'fecha_objetivo', 'periodicidad_seguimiento', 'observaciones'])->all(),
            'actualizado_por' => $userId,
        ]);
        $objetivo->indicadorPrincipal()->firstOrFail()->update($this->datosIndicador($data, $userId, false));
        $this->sincronizarRelaciones($objetivo, $data);
    }

    private function snapshotPlanificacion(Objetivo $objetivo): array
    {
        $objetivo->load(['indicadorPrincipal', 'contextos:id', 'riesgos:id', 'partes:id']);
        $indicador = $objetivo->indicadorPrincipal;

        return [
            'objetivo' => collect($objetivo->getAttributes())->only([
                'periodo_id', 'codigo', 'compromiso_politica', 'proceso', 'titulo', 'descripcion',
                'area_responsable', 'responsable_id', 'fecha_inicio', 'fecha_objetivo',
                'periodicidad_seguimiento', 'observaciones', 'estado',
            ])->all(),
            'indicador' => $indicador ? collect($indicador->getAttributes())->only([
                'nombre', 'metodo_calculo', 'unidad', 'fuente', 'frecuencia', 'agregacion',
                'comparador', 'meta', 'meta_hasta', 'tolerancia', 'linea_base',
            ])->all() : null,
            'relaciones' => [
                'contextos' => $objetivo->contextos->pluck('id')->all(),
                'riesgos' => $objetivo->riesgos->pluck('id')->all(),
                'partes' => $objetivo->partes->pluck('id')->all(),
            ],
        ];
    }

    private function normalizarCatalogos(Request $request): void
    {
        $request->merge([
            'proceso' => $request->input('proceso') === '__otro__' ? $request->input('proceso_otro') : $request->input('proceso'),
            'area_responsable' => $request->input('area_responsable') === '__otro__' ? $request->input('area_responsable_otro') : $request->input('area_responsable'),
            'compromiso_politica' => $request->input('compromiso_politica') === '__otro__' ? $request->input('compromiso_politica_otro') : $request->input('compromiso_politica'),
            'indicador_unidad' => $request->input('indicador_unidad') === '__otro__' ? $request->input('indicador_unidad_otro') : $request->input('indicador_unidad'),
            'accion_area_responsable' => $request->input('accion_area_responsable') === '__otro__' ? $request->input('accion_area_responsable_otro') : $request->input('accion_area_responsable'),
        ]);
    }

    private function datosFormulario($periodos, ?Periodo $periodo): array
    {
        return [
            'periodos' => $periodos, 'periodo' => $periodo,
            'usuarios' => User::habilitados()->orderBy('name')->get(),
            'procesos' => config('iso.procesos'), 'areas' => config('iso.areas_responsables'),
            'compromisos' => config('iso.compromisos_politica'), 'frecuencias' => config('iso.frecuencias'),
            'unidades' => config('iso.unidades_indicador'),
            'contextos' => $periodo ? Contexto::where('periodo_id', $periodo->id)->orderBy('codigo')->get() : collect(),
            'riesgos' => $periodo ? Riesgo::where('periodo_id', $periodo->id)->orderBy('codigo')->get() : collect(),
            'partes' => ParteInteresada::where('estado', 'activa')->orderBy('nombre')->get(),
        ];
    }

    private function validarPeriodoAbierto(Periodo $periodo): void
    {
        abort_if($periodo->estado === 'cerrado', 422, 'El período se encuentra cerrado. Reabrilo antes de modificar sus objetivos.');
    }

    private function validarEditable(Objetivo $objetivo): void
    {
        $this->validarPeriodoAbierto($objetivo->periodo()->firstOrFail());
        abort_if(in_array($objetivo->estado, ['cerrado', 'suspendido'], true), 422, 'El objetivo está cerrado o suspendido.');
    }

    private function validarDocumento(Request $request, ?int $documentoId): void
    {
        if (!$documentoId) return;
        $documento = Documento::findOrFail($documentoId);
        abort_unless($request->user()->isAdmin() || $documento->puedeLeer($request->user()), 403, 'No tenés permiso para vincular este documento.');
    }

    private function documentosAccesibles(Request $request)
    {
        return Documento::whereRaw("LOWER(estado) IN ('aprobado','registro')")
            ->when(!$request->user()->isAdmin(), fn ($q) => $q->whereHas('permisos', fn ($p) => $p->where('user_id', $request->user()->id)->where(fn ($a) => $a->where('puede_leer', true)->orWhere('puede_escribir', true)->orWhere('puede_aprobar', true)->orWhere('puede_eliminar', true))))
            ->orderBy('titulo')->get(['id', 'titulo']);
    }
}
