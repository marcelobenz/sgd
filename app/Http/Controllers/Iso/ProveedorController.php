<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Iso\Periodo;
use App\Models\Iso\Proveedor;
use App\Models\Iso\ProveedorAccion;
use App\Models\Iso\ProveedorEvaluacion;
use App\Models\Iso\Riesgo;
use App\Models\Iso\HistorialCambio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\Iso\PeriodoAbiertoService;

class ProveedorController extends Controller
{
    public function __construct(private readonly PeriodoAbiertoService $periodosAbiertos) {}

    public function index(Request $request)
    {
        $query = Proveedor::with(['ultimaEvaluacion', 'evaluaciones.acciones']);
        if ($request->input('estado', 'activo') !== 'todos') $query->where('estado', $request->input('estado', 'activo'));
        if ($request->filled('resultado')) $query->whereHas('ultimaEvaluacion', fn ($q) => $q->where('resultado', $request->string('resultado')));
        if ($request->boolean('vencidos')) $query->whereHas('ultimaEvaluacion', fn ($q) => $q->whereDate('proxima_evaluacion', '<', today()));
        $proveedores = $query->orderBy('nombre')->orderBy('producto_servicio')->get();
        $historialCicloVida = $this->historialCicloVida();
        $proveedoresAuditoria = Proveedor::whereIn('id', $historialCicloVida->pluck('entidad_id')->unique())->get()->keyBy('id');
        $areas = config('iso.areas_responsables');
        return view('iso.proveedores.index', compact('proveedores', 'areas', 'historialCicloVida', 'proveedoresAuditoria'));
    }

    public function store(Request $request)
    {
        $request->merge(['area_responsable' => $request->input('area_responsable') === '__otro__' ? $request->input('area_responsable_otro') : $request->input('area_responsable')]);
        $data = $this->validarProveedor($request);
        $duplicado = Proveedor::whereRaw('LOWER(nombre) = ?', [mb_strtolower(trim($data['nombre']))])->whereRaw('LOWER(producto_servicio) = ?', [mb_strtolower(trim($data['producto_servicio']))])->exists();
        if ($duplicado) return back()->withErrors(['nombre' => 'Ya existe este proveedor para el mismo producto o servicio.'])->withInput();
        $numero = (int) Proveedor::max('id') + 1;
        $proveedor = Proveedor::create($data + ['codigo' => 'PR-' . str_pad((string) $numero, 4, '0', STR_PAD_LEFT), 'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
        return redirect()->route('planificacion.proveedores.show', $proveedor)->with('success', 'Proveedor creado. Registrá ahora su selección inicial o primera evaluación.');
    }

    public function show(Request $request, Proveedor $proveedor)
    {
        $proveedor->load([
            'documento', 'selecciones' => fn ($q) => $q->with(['evaluador', 'documento'])->latest('fecha'),
            'evaluaciones' => fn ($q) => $q->with(['evaluador', 'periodo', 'documento', 'riesgos', 'acciones.responsable', 'acciones.documento', 'evaluacionAnterior'])->orderByDesc('fecha_evaluacion')->orderByDesc('id'),
            'cicloActivo.acciones',
        ]);
        $periodos = Periodo::whereIn('estado', ['borrador', 'vigente'])->orderByDesc('anio')->get();
        $riesgos = Riesgo::whereHas('periodo', fn ($query) => $query->whereIn('estado', ['borrador', 'vigente']))->orderByDesc('periodo_id')->orderBy('codigo')->get();
        $usuarios = User::habilitados()->orderBy('name')->get();
        $areas = config('iso.areas_responsables');
        $documentos = $this->documentosAccesibles($request);
        $historialCicloVida = $this->historialCicloVida($proveedor);
        return view('iso.proveedores.show', compact('proveedor', 'periodos', 'riesgos', 'usuarios', 'areas', 'documentos', 'historialCicloVida'));
    }

    public function update(Request $request, Proveedor $proveedor)
    {
        $request->merge(['area_responsable' => $request->input('area_responsable') === '__otro__' ? $request->input('area_responsable_otro') : $request->input('area_responsable')]);
        $data = $this->validarProveedor($request);
        $data['estado'] = $proveedor->estado;
        $proveedor->update($data + ['actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Ficha del proveedor actualizada.');
    }

    public function darDeBaja(Request $request, Proveedor $proveedor)
    {
        abort_if($proveedor->estado === 'inactivo', 422, 'El proveedor ya se encuentra inactivo.');
        $data = $request->validate([
            'motivo' => 'required|string|min:5|max:2000',
            'confirmacion' => 'accepted',
        ], ['confirmacion.accepted' => 'Confirmá que comprendés el impacto de dar de baja al proveedor.']);
        $proveedor->update([
            'estado' => 'inactivo', 'fecha_baja' => now(), 'motivo_baja' => $data['motivo'],
            'actualizado_por' => $request->user()->id,
        ]);
        return back()->with('success', 'Proveedor dado de baja. Su historial permanece disponible para consulta y auditoría.');
    }

    public function reactivar(Request $request, Proveedor $proveedor)
    {
        abort_if($proveedor->estado === 'activo', 422, 'El proveedor ya se encuentra activo.');
        $data = $request->validate([
            'motivo' => 'required|string|min:5|max:2000',
            'confirmacion' => 'accepted',
        ], ['confirmacion.accepted' => 'Confirmá que comprendés que el proveedor volverá a estar disponible para su gestión.']);
        $proveedor->update([
            'estado' => 'activo', 'fecha_reactivacion' => now(), 'motivo_reactivacion' => $data['motivo'],
            'actualizado_por' => $request->user()->id,
        ]);
        return back()->with('success', 'Proveedor reactivado. Ya puede volver a seleccionarse y evaluarse.');
    }

    public function destroy(Request $request, Proveedor $proveedor)
    {
        $data = $request->validate([
            'motivo' => 'required|string|min:5|max:2000',
            'confirmacion' => 'accepted',
        ], ['confirmacion.accepted' => 'Confirmá la eliminación definitiva de la ficha.']);
        if ($proveedor->tieneActividad()) {
            throw ValidationException::withMessages(['confirmacion' => 'No puede eliminarse porque posee selección, evaluaciones, acciones o evidencias. Utilizá la baja para conservar su historial.']);
        }
        HistorialCambio::create([
            'entidad_tipo' => $proveedor->getMorphClass(), 'entidad_id' => $proveedor->id,
            'evento' => 'eliminado', 'valores_anteriores' => $proveedor->getAttributes(),
            'valores_nuevos' => ['motivo' => $data['motivo']], 'user_id' => $request->user()->id,
        ]);
        $proveedor->delete();
        return redirect()->route('planificacion.proveedores.index')->with('success', 'Ficha creada por error eliminada definitivamente.');
    }

    public function seleccionar(Request $request, Proveedor $proveedor)
    {
        abort_if($proveedor->estado === 'inactivo', 422, 'No se puede seleccionar un proveedor inactivo.');
        if ($proveedor->selecciones()->exists()) {
            throw ValidationException::withMessages(['fecha' => 'Este proveedor ya tiene registrada su selección inicial.']);
        }
        $data = $request->validate([
            'fecha' => 'required|date', 'caracteristicas' => 'required|integer|min:2|max:10',
            'recomendaciones' => 'nullable|integer|min:2|max:10', 'precio_condiciones' => 'nullable|integer|min:2|max:10',
        ]);
        $calificaciones = collect(['caracteristicas' => $data['caracteristicas'], 'recomendaciones' => $data['recomendaciones'] ?? null, 'precio_condiciones' => $data['precio_condiciones'] ?? null]);
        $puntaje = round($calificaciones->filter(fn ($v) => $v !== null)->avg(), 2);
        $proveedor->selecciones()->create(['fecha' => $data['fecha'], 'calificaciones' => $calificaciones->all(), 'puntaje' => $puntaje, 'resultado' => $this->clasificacion($puntaje), 'conclusion' => 'Selección inicial registrada.', 'evaluado_por' => $request->user()->id]);
        return back()->with('success', 'Selección inicial registrada.');
    }

    public function evaluar(Request $request, Proveedor $proveedor)
    {
        abort_if($proveedor->estado === 'inactivo', 422, 'No se puede evaluar un proveedor inactivo.');
        $data = $request->validate([
            'periodo_id' => 'required|exists:iso_periodos,id', 'fecha_evaluacion' => 'required|date',
            'evaluacion_anterior_id' => 'nullable|exists:iso_proveedor_evaluaciones,id',
            'precio_calidad' => 'required|integer|min:2|max:10', 'resolucion_imprevistos' => 'required|integer|min:2|max:10',
            'calidad_producto' => 'required|integer|min:2|max:10', 'calidad_atencion' => 'required|integer|min:2|max:10',
            'decision' => ['required', Rule::in(['continuar', 'reemplazar', 'suspender'])],
            'conclusion' => 'required|string|max:5000', 'justificacion' => 'nullable|string|max:5000',
            'proxima_evaluacion' => 'nullable|date|after:fecha_evaluacion', 'requiere_analisis_riesgo' => 'required|boolean',
            'requiere_accion' => 'required|boolean',
            'riesgos' => 'nullable|array', 'riesgos.*' => 'exists:iso_riesgos,id',
            'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000',
            'accion_descripcion' => 'nullable|string|max:5000', 'accion_area_responsable' => 'nullable|string|max:255',
            'accion_responsable_id' => 'nullable|exists:users,id', 'accion_fecha_objetivo' => 'nullable|date|after_or_equal:fecha_evaluacion',
        ]);
        $this->periodosAbiertos->validar(Periodo::findOrFail($data['periodo_id']), 'registrar evaluaciones de proveedores');
        if (Riesgo::whereIn('id', $data['riesgos'] ?? [])->where('periodo_id', '!=', $data['periodo_id'])->exists()) {
            throw ValidationException::withMessages(['riesgos' => 'Los riesgos vinculados deben pertenecer al mismo período que la evaluación.']);
        }
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        $calificaciones = collect(['precio_calidad' => $data['precio_calidad'], 'resolucion_imprevistos' => $data['resolucion_imprevistos'], 'calidad_producto' => $data['calidad_producto'], 'calidad_atencion' => $data['calidad_atencion']]);
        $puntaje = round($calificaciones->avg(), 2);
        $resultado = $this->clasificacion($puntaje);
        $accionObligatoria = $data['decision'] === 'continuar' && $resultado !== 'aprobado';
        if ($accionObligatoria && !$data['requiere_accion']) throw ValidationException::withMessages(['requiere_accion' => 'Para continuar con un resultado condicional o no aprobado se requiere una acción.']);
        if (($resultado !== 'aprobado' || in_array($data['decision'], ['reemplazar', 'suspender'])) && blank($data['justificacion'])) throw ValidationException::withMessages(['justificacion' => 'Justificá la decisión adoptada para este resultado.']);
        if ($data['decision'] === 'continuar' && blank($data['proxima_evaluacion'])) throw ValidationException::withMessages(['proxima_evaluacion' => 'Programá la próxima evaluación si el proveedor continuará activo.']);
        if ($data['requiere_accion'] && (blank($data['accion_descripcion']) || blank($data['accion_area_responsable']) || blank($data['accion_fecha_objetivo']))) throw ValidationException::withMessages(['accion_descripcion' => 'Indicá descripción, área responsable y fecha objetivo de la acción.']);
        DB::transaction(function () use ($data, $calificaciones, $puntaje, $resultado, $proveedor, $request, $accionObligatoria) {
            $cicloActivo = $proveedor->evaluaciones()->where('estado_ciclo', '!=', 'cerrada')->lockForUpdate()->orderByDesc('fecha_evaluacion')->orderByDesc('id')->first();
            $evaluacionAnterior = filled($data['evaluacion_anterior_id'] ?? null) ? $proveedor->evaluaciones()->find($data['evaluacion_anterior_id']) : null;
            if (!empty($data['evaluacion_anterior_id']) && !$evaluacionAnterior) throw ValidationException::withMessages(['evaluacion_anterior_id' => 'La evaluación anterior no pertenece a este proveedor.']);
            if ($cicloActivo && (!$evaluacionAnterior || $cicloActivo->id !== $evaluacionAnterior->id)) throw ValidationException::withMessages(['fecha_evaluacion' => 'Ya existe una evaluación en tratamiento para esta prestación.']);
            if ($evaluacionAnterior && $evaluacionAnterior->estado_ciclo !== 'pendiente_reevaluacion') throw ValidationException::withMessages(['fecha_evaluacion' => 'La reevaluación se habilita cuando finalizan las acciones obligatorias.']);
            $tendraAccionObligatoria = $data['requiere_accion'] && ($accionObligatoria || in_array($data['decision'], ['reemplazar', 'suspender']));
            $estadoCiclo = $tendraAccionObligatoria ? 'en_tratamiento' : 'cerrada';
            $evaluacion = $proveedor->evaluaciones()->create([
                ...collect($data)->except(['riesgos', 'requiere_accion', 'precio_calidad', 'resolucion_imprevistos', 'calidad_producto', 'calidad_atencion', 'accion_descripcion', 'accion_area_responsable', 'accion_responsable_id', 'accion_fecha_objetivo'])->all(),
                'tipo' => $evaluacionAnterior ? 'reevaluacion' : 'periodica', 'calificaciones' => $calificaciones->all(),
                'puntaje' => $puntaje, 'resultado' => $resultado, 'estado_ciclo' => $estadoCiclo, 'evaluado_por' => $request->user()->id,
            ]);
            $evaluacion->riesgos()->sync($data['riesgos'] ?? []);
            if (filled($data['accion_descripcion'] ?? null)) $evaluacion->acciones()->create(['obligatoria' => $tendraAccionObligatoria, 'descripcion' => $data['accion_descripcion'], 'area_responsable' => $data['accion_area_responsable'], 'responsable_id' => $data['accion_responsable_id'] ?? null, 'fecha_objetivo' => $data['accion_fecha_objetivo'], 'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
            if ($evaluacionAnterior) $evaluacionAnterior->update(['estado_ciclo' => 'cerrada']);
        });
        return back()->with('success', 'Evaluación periódica registrada.');
    }

    public function storeAccion(Request $request, ProveedorEvaluacion $evaluacion)
    {
        if ($evaluacion->periodo_id) $this->periodosAbiertos->validar($evaluacion->periodo()->firstOrFail(), 'agregar acciones de proveedores');
        abort_if($evaluacion->proveedor->estado === 'inactivo', 422, 'No se pueden agregar acciones nuevas a un proveedor inactivo.');
        $data = $request->validate(['obligatoria' => 'required|boolean', 'descripcion' => 'required|string|max:5000', 'area_responsable' => 'required|string|max:255', 'responsable_id' => 'nullable|exists:users,id', 'fecha_objetivo' => 'required|date', 'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000']);
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        if ($evaluacion->estado_ciclo === 'cerrada' && $data['obligatoria']) throw ValidationException::withMessages(['obligatoria' => 'Una evaluación cerrada solo admite acciones opcionales de mejora.']);
        $evaluacion->acciones()->create($data + ['creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
        $this->recalcularEstadoCiclo($evaluacion);
        return back()->with('success', 'Acción agregada a la evaluación.');
    }

    public function updateAccion(Request $request, ProveedorAccion $accion)
    {
        if ($accion->evaluacion->periodo_id) $this->periodosAbiertos->validar($accion->evaluacion->periodo()->firstOrFail(), 'modificar acciones de proveedores');
        abort_if(in_array($accion->estado, ['completada', 'cancelada']), 422, 'La acción está cerrada y no puede modificarse.');
        $data = $request->validate(['estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completada', 'cancelada'])], 'resultado' => 'nullable|string|max:5000']);
        if (in_array($data['estado'], ['completada', 'cancelada']) && blank($data['resultado'])) throw ValidationException::withMessages(['resultado' => 'Indicá el resultado o motivo antes de cerrar la acción.']);
        $accion->update($data + ['actualizado_por' => $request->user()->id, 'cerrada_en' => in_array($data['estado'], ['completada', 'cancelada']) ? now() : null]);
        $this->recalcularEstadoCiclo($accion->evaluacion);
        return back()->with('success', 'Acción actualizada.');
    }

    private function validarProveedor(Request $request): array
    {
        $data = $request->validate(['nombre' => 'required|string|max:255', 'producto_servicio' => 'required|string|max:255', 'area_responsable' => 'required|string|max:255', 'fecha_alta' => 'nullable|date', 'criticidad' => ['required', Rule::in(['critico', 'no_critico'])], 'periodicidad_meses' => 'required|integer|min:1|max:60', 'estado' => ['nullable', Rule::in(['activo', 'inactivo'])], 'observaciones' => 'nullable|string|max:5000', 'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000']);
        $this->validarDocumento($request, $data['documento_id'] ?? null);
        $data['estado'] ??= 'activo';
        return $data;
    }

    private function clasificacion(float $puntaje): string { return $puntaje >= 7 ? 'aprobado' : ($puntaje >= 5 ? 'condicional' : 'no_aprobado'); }

    private function recalcularEstadoCiclo(ProveedorEvaluacion $evaluacion): void
    {
        if ($evaluacion->resultado === 'aprobado') {
            $evaluacion->update(['estado_ciclo' => 'cerrada']);
            return;
        }
        $accionesObligatorias = $evaluacion->acciones()->where('obligatoria', true);
        if (!$accionesObligatorias->exists()) return;
        $abiertas = (clone $accionesObligatorias)->whereNotIn('estado', ['completada', 'cancelada'])->exists();
        $estado = $abiertas ? 'en_tratamiento' : ($evaluacion->decision === 'continuar' || $evaluacion->decision === 'continuar_con_acciones' ? 'pendiente_reevaluacion' : 'cerrada');
        $evaluacion->update(['estado_ciclo' => $estado]);
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

    private function historialCicloVida(?Proveedor $proveedor = null)
    {
        return HistorialCambio::with('usuario')
            ->where('entidad_tipo', (new Proveedor())->getMorphClass())
            ->when($proveedor, fn ($query) => $query->where('entidad_id', $proveedor->id))
            ->whereIn('evento', ['actualizado', 'eliminado'])
            ->latest()
            ->get()
            ->filter(function (HistorialCambio $cambio) {
                if ($cambio->evento === 'eliminado') return true;
                return array_key_exists('estado', $cambio->valores_nuevos ?? [])
                    && in_array($cambio->valores_nuevos['estado'], ['activo', 'inactivo'], true);
            })
            ->values();
    }
}
