<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Iso\Accion;
use App\Models\Iso\Riesgo;
use App\Models\Iso\Periodo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Services\Iso\PeriodoAbiertoService;

class AccionController extends Controller
{
    public function __construct(private readonly PeriodoAbiertoService $periodos) {}

    public function index(Request $request)
    {
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : (Periodo::where('estado', 'vigente')->first() ?? $periodos->first());
        $query = Accion::with(['riesgo.periodo', 'responsable', 'seguimientos'])->withCount('seguimientos')
            ->whereHas('riesgo', fn ($riesgo) => $periodo ? $riesgo->where('periodo_id', $periodo->id) : $riesgo->whereRaw('1=0'));
        if ($request->filled('estado')) $query->where('estado', $request->string('estado'));
        if ($request->filled('tipo')) $query->whereHas('riesgo', fn ($riesgo) => $riesgo->where('tipo', $request->string('tipo')));
        if ($request->filled('responsable')) $query->where('responsable_id', $request->integer('responsable'));
        if ($request->boolean('mis_acciones')) $query->where('responsable_id', $request->user()->id);
        if ($request->boolean('vencidas')) $query->whereIn('estado', ['pendiente', 'en_proceso'])->whereDate('fecha_objetivo', '<', today());
        if ($request->boolean('sin_evidencia')) {
            $query->whereDoesntHave('seguimientos', fn ($seguimiento) => $seguimiento->whereNotNull('documento_id')->orWhereNotNull('enlace_externo'));
        }
        if ($request->boolean('pendiente_verificacion')) {
            $query->whereIn('estado', ['completada', 'cancelada'])->whereHas('riesgo', fn ($riesgo) => $riesgo->where('eficacia', 'pendiente'));
        }
        $orden = $request->string('orden')->toString();
        $direccion = $request->string('direccion')->lower()->toString() === 'desc' ? 'desc' : 'asc';
        $ordenesDirectos = ['accion' => 'descripcion', 'fecha' => 'fecha_objetivo', 'estado' => 'estado', 'seguimientos' => 'seguimientos_count'];
        if (isset($ordenesDirectos[$orden])) {
            $query->orderBy($ordenesDirectos[$orden], $direccion);
        } elseif ($orden === 'riesgo') {
            $query->orderBy(Riesgo::select('codigo')->whereColumn('iso_riesgos.id', 'iso_acciones.riesgo_id'), $direccion);
        } elseif ($orden === 'responsable') {
            $query->orderBy(User::select('name')->whereColumn('users.id', 'iso_acciones.responsable_id'), $direccion);
        } elseif ($orden === 'verificacion') {
            $query->orderBy(Riesgo::select('fecha_verificacion_prevista')->whereColumn('iso_riesgos.id', 'iso_acciones.riesgo_id'), $direccion);
        } else {
            $query->orderByRaw("FIELD(estado, 'en_proceso','pendiente','completada','cancelada')")->orderBy('fecha_objetivo');
        }
        $acciones = $query->get();
        $usuarios = User::habilitados()->orderBy('name')->get();
        return view('iso.acciones.index', compact('periodos', 'periodo', 'acciones', 'usuarios'));
    }

    public function store(Request $request, Riesgo $riesgo)
    {
        $this->periodos->validar($riesgo->periodo()->firstOrFail(), 'agregar acciones');
        $data = $request->validate(['descripcion' => 'required|string|max:5000', 'responsable_id' => 'required|exists:users,id', 'fecha_objetivo' => 'required|date']);
        $reapertura = [];
        if ($riesgo->estado === 'finalizado') {
            $reapertura = $request->validate([
                'motivo_reapertura' => 'required|string|min:10|max:3000',
                'comprende_impacto' => 'accepted',
                'confirmacion_reapertura' => ['required', Rule::in(["REABRIR {$riesgo->codigo}"])],
            ], [
                'confirmacion_reapertura.in' => "Escribí exactamente REABRIR {$riesgo->codigo} para confirmar.",
                'comprende_impacto.accepted' => 'Debés confirmar que comprendés el impacto de reabrir el registro.',
            ]);
        }
        DB::transaction(function () use ($riesgo, $data, $reapertura, $request) {
            if ($riesgo->estado === 'finalizado') {
                $riesgo->transiciones()->create([
                    'accion' => 'reapertura_por_accion', 'estado_anterior' => 'finalizado', 'estado_nuevo' => 'en_proceso',
                    'motivo' => $reapertura['motivo_reapertura'], 'realizado_por' => $request->user()->id,
                ]);
            }
            $riesgo->acciones()->create($data + ['creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
            $riesgo->update(['estado' => 'en_proceso', 'eficacia' => 'pendiente', 'conclusion_eficacia' => null, 'impacto_final' => null, 'probabilidad_final' => null, 'indice_final' => null, 'finalizado_en' => null, 'actualizado_por' => $request->user()->id]);
        });
        return back()->with('success', $reapertura ? 'Riesgo u oportunidad reabierto y nueva acción agregada.' : 'Acción agregada.');
    }

    public function actualizar(Request $request, Accion $accion)
    {
        $this->periodos->validar($accion->riesgo->periodo, 'modificar acciones');
        if (in_array($accion->estado, ['completada', 'cancelada'])) {
            return back()->withErrors(['estado' => 'La acción está cerrada. Para modificarla, utilizá Reabrir acción e indicá el motivo.']);
        }
        $data = $request->validate(['estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completada', 'cancelada'])], 'resultado' => 'nullable|string|max:5000']);
        if (in_array($data['estado'], ['completada', 'cancelada']) && blank($data['resultado'])) {
            return back()->withErrors(['resultado' => $data['estado'] === 'completada' ? 'Registrá el resultado obtenido para completar la acción.' : 'Indicá el motivo por el que se cancela la acción.'])->withInput();
        }
        $accion->update($data + ['actualizado_por' => $request->user()->id, 'completada_en' => $data['estado'] === 'completada' ? now() : null]);
        return back()->with('success', 'Acción actualizada.');
    }

    public function reabrir(Request $request, Accion $accion)
    {
        $this->periodos->validar($accion->riesgo->periodo, 'reabrir acciones');
        abort_unless(in_array($accion->estado, ['completada', 'cancelada']), 422, 'Sólo pueden reabrirse acciones completadas o canceladas.');
        $data = $request->validate([
            'motivo' => 'required|string|min:10|max:3000',
            'comprende_impacto' => 'accepted',
            'confirmacion' => ['required', Rule::in(['REABRIR ACCION'])],
        ], [
            'confirmacion.in' => 'Escribí exactamente REABRIR ACCION para confirmar.',
            'comprende_impacto.accepted' => 'Debés confirmar que comprendés el impacto de la reapertura.',
        ]);

        DB::transaction(function () use ($accion, $data, $request) {
            $estadoAnterior = $accion->estado;
            $accion->transiciones()->create([
                'accion' => 'reapertura', 'estado_anterior' => $estadoAnterior, 'estado_nuevo' => 'en_proceso',
                'motivo' => $data['motivo'], 'realizado_por' => $request->user()->id,
            ]);
            $accion->update([
                'estado' => 'en_proceso', 'resultado' => null, 'completada_en' => null,
                'actualizado_por' => $request->user()->id,
            ]);
            $accion->riesgo->update([
                'estado' => 'en_proceso', 'eficacia' => 'pendiente', 'conclusion_eficacia' => null,
                'impacto_final' => null, 'probabilidad_final' => null, 'indice_final' => null,
                'finalizado_en' => null, 'actualizado_por' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Acción reabierta. La evaluación de eficacia anterior permanece en el historial y la eficacia actual volvió a pendiente.');
    }

    public function seguimiento(Request $request, Accion $accion)
    {
        $this->periodos->validar($accion->riesgo->periodo, 'registrar seguimientos o evidencias');
        $data = $request->validate([
            'fecha' => 'required|date', 'detalle' => 'required|string|max:5000', 'resultado' => 'nullable|string|max:1000',
            'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000',
        ]);
        $cerrada = in_array($accion->estado, ['completada', 'cancelada']);
        if (!empty($data['documento_id'])) {
            $documento = Documento::findOrFail($data['documento_id']);
            abort_unless($request->user()->isAdmin() || $documento->puedeLeer($request->user()), 403, 'No tenés permiso para vincular este documento.');
        }
        if ($cerrada && empty($data['documento_id']) && empty($data['enlace_externo'])) {
            return back()->withErrors(['documento_id' => 'Para una acción cerrada, vinculá un documento del SGD o un enlace de evidencia externa.'])->withInput();
        }
        $accion->seguimientos()->create($data + [
            'tipo' => $cerrada ? 'evidencia_complementaria' : 'seguimiento',
            'registrado_por' => $request->user()->id,
        ]);
        if ($accion->estado === 'pendiente') $accion->update(['estado' => 'en_proceso', 'actualizado_por' => $request->user()->id]);
        return back()->with('success', $cerrada ? 'Evidencia complementaria incorporada sin reabrir la acción.' : 'Seguimiento y evidencia registrados.');
    }
}
