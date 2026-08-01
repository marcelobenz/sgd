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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RiesgoController extends Controller
{
    public function index(Request $request)
    {
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : (Periodo::where('estado', 'vigente')->first() ?? $periodos->first());
        $query = $periodo?->riesgos()->with(['contexto', 'responsable', 'acciones']) ?? Riesgo::whereRaw('1=0');
        if ($request->filled('tipo')) $query->where('tipo', $request->string('tipo'));
        if ($request->filled('estado')) $query->where('estado', $request->string('estado'));
        $riesgos = $query->orderByDesc('indice_inicial')->orderBy('codigo')->get();
        return view('iso.riesgos.index', compact('periodos', 'periodo', 'riesgos'));
    }

    public function create(Request $request)
    {
        $contexto = $request->filled('contexto') ? Contexto::with('periodo')->findOrFail($request->integer('contexto')) : null;
        $periodo = $contexto?->periodo ?? Periodo::where('estado', 'vigente')->firstOrFail();
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
            'impacto_inicial' => 'required|integer|min:1|max:3', 'probabilidad_inicial' => 'required|integer|min:1|max:3',
            'responsable_id' => 'nullable|exists:users,id', 'fecha_verificacion_prevista' => 'nullable|date',
            'accion_descripcion' => 'nullable|string|max:5000', 'accion_responsable_id' => 'nullable|exists:users,id', 'accion_fecha_objetivo' => 'nullable|date',
        ]);
        $periodo = Periodo::findOrFail($data['periodo_id']);
        abort_if($periodo->estado === 'cerrado', 422, 'El período está cerrado.');
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
        $riesgo->load(['periodo', 'contexto', 'responsable', 'acciones.responsable', 'acciones.seguimientos.documento']);
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
        $data = $request->validate([
            'eficacia' => ['required', Rule::in(['si', 'parcial', 'no'])], 'conclusion_eficacia' => 'required|string|max:5000',
            'impacto_final' => 'required|integer|min:1|max:3', 'probabilidad_final' => 'required|integer|min:1|max:3',
            'estado' => ['required', Rule::in(['en_proceso', 'permanente', 'finalizado'])],
        ]);
        $riesgo->update($data + ['indice_final' => $data['impacto_final'] * $data['probabilidad_final'], 'actualizado_por' => $request->user()->id, 'finalizado_en' => $data['estado'] === 'finalizado' ? now() : null]);
        return back()->with('success', 'Eficacia verificada y valoración final registrada.');
    }
}
