<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Contexto;
use App\Models\Iso\Periodo;
use App\Models\User;
use App\Services\Iso\CodigoIsoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ContextoController extends Controller
{
    public function index(Request $request)
    {
        $periodos = Periodo::orderByDesc('anio')->get();
        $periodo = $request->filled('periodo') ? Periodo::findOrFail($request->integer('periodo')) : (Periodo::where('estado', 'vigente')->first() ?? $periodos->first());
        $contextos = $periodo?->contextos()->with(['responsable', 'riesgos'])->orderByRaw("FIELD(tipo, 'fortaleza','debilidad','oportunidad','amenaza')")->orderBy('numero')->get() ?? collect();
        $usuarios = User::habilitados()->orderBy('name')->get();
        $procesos = config('iso.procesos');
        $fuentes = config('iso.fuentes');
        return view('iso.foda.index', compact('periodos', 'periodo', 'contextos', 'usuarios', 'procesos', 'fuentes'));
    }

    public function store(Request $request, CodigoIsoService $codigos)
    {
        $request->merge(['proceso' => $this->valorCatalogo($request, 'proceso')]);
        $data = $request->validate([
            'periodo_id' => 'required|exists:iso_periodos,id', 'tipo' => ['required', Rule::in(['fortaleza', 'debilidad', 'oportunidad', 'amenaza'])],
            'titulo' => 'required|string|max:255', 'descripcion' => 'required|string|max:5000', 'proceso' => 'nullable|string|max:255',
            'fuente_tipo' => 'nullable|string|max:255', 'fuente' => 'nullable|string|max:3000', 'fecha_identificacion' => 'required|date', 'responsable_id' => 'nullable|exists:users,id',
        ]);
        $periodo = Periodo::findOrFail($data['periodo_id']);
        abort_if($periodo->estado === 'cerrado', 422, 'El período está cerrado.');
        DB::transaction(function () use ($data, $periodo, $request, $codigos) {
            [$numero, $codigo] = $codigos->siguienteContexto($periodo, $data['tipo']);
            Contexto::create($data + ['numero' => $numero, 'codigo' => $codigo, 'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
        });
        return back()->with('success', 'Elemento FODA registrado y pendiente de evaluación.');
    }

    public function show(Contexto $contexto)
    {
        $contexto->load(['periodo', 'responsable', 'riesgos.acciones']);
        $usuarios = User::habilitados()->orderBy('name')->get();
        $referencias = config('iso.referencias');
        return view('iso.foda.show', compact('contexto', 'usuarios', 'referencias'));
    }

    public function evaluar(Request $request, Contexto $contexto)
    {
        abort_if($contexto->periodo->estado === 'cerrado', 422, 'El período está cerrado.');
        $data = $request->validate([
            'relevante_sgc' => 'required|boolean',
            'decision' => ['required', Rule::in(['pendiente', 'tratar_riesgo', 'aprovechar_oportunidad', 'vincular_existente', 'aceptar_sin_accion', 'no_aplicable'])],
            'justificacion' => 'nullable|string|max:5000', 'referencia_tipo' => 'nullable|string|max:255', 'referencia_existente' => 'nullable|string|max:1000',
        ]);
        if (in_array($data['decision'], ['vincular_existente', 'aceptar_sin_accion', 'no_aplicable']) && blank($data['justificacion'])) {
            return back()->withErrors(['justificacion' => 'La justificación es obligatoria para esta decisión.'])->withInput();
        }
        $contexto->update($data + ['actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Evaluación registrada.');
    }

    private function valorCatalogo(Request $request, string $campo): ?string
    {
        return $request->input($campo) === '__otro__'
            ? $request->input($campo . '_otro')
            : $request->input($campo);
    }
}
