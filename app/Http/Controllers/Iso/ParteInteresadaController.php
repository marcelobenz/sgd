<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Documento;
use App\Models\Iso\ParteInteresada;
use App\Models\Iso\Periodo;
use App\Models\Iso\Riesgo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Services\Iso\PeriodoAbiertoService;

class ParteInteresadaController extends Controller
{
    public function __construct(private readonly PeriodoAbiertoService $periodosAbiertos) {}

    public function index(Request $request)
    {
        $query = ParteInteresada::with(['ultimaEvaluacion.evaluador'])->orderBy('nombre');
        if ($request->input('estado', 'activa') !== 'todas') $query->where('estado', $request->input('estado', 'activa'));
        $partes = $query->get();
        $partesCatalogo = config('iso.partes_interesadas');
        $areas = config('iso.areas_responsables');
        return view('iso.partes.index', compact('partes', 'partesCatalogo', 'areas'));
    }

    public function store(Request $request)
    {
        $request->merge([
            'nombre' => $request->input('nombre') === '__otro__' ? $request->input('nombre_otro') : $request->input('nombre'),
            'area_responsable' => $request->input('area_responsable') === '__otro__' ? $request->input('area_responsable_otro') : $request->input('area_responsable'),
        ]);
        $data = $this->validarFicha($request);
        $clave = sha1(mb_strtolower(trim($data['nombre'])));
        if (ParteInteresada::where('clave', $clave)->exists()) {
            return back()->withErrors(['nombre' => 'Esta parte interesada ya se encuentra registrada.'])->withInput();
        }
        $parte = ParteInteresada::create($data + ['clave' => $clave, 'estado' => 'activa', 'creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
        return redirect()->route('planificacion.partes.show', $parte)->with('success', 'Parte interesada creada. Ahora podés registrar su primera evaluación.');
    }

    public function show(Request $request, ParteInteresada $parte)
    {
        $parte->load(['evaluaciones' => fn ($query) => $query->with(['evaluador', 'periodo', 'documento', 'riesgos'])->orderByDesc('fecha_evaluacion')]);
        $periodos = Periodo::whereIn('estado', ['borrador', 'vigente'])->orderByDesc('anio')->get();
        $riesgos = Riesgo::whereHas('periodo', fn ($query) => $query->whereIn('estado', ['borrador', 'vigente']))->orderByDesc('periodo_id')->orderBy('codigo')->get();
        $areas = collect(config('iso.areas_responsables'));
        $documentosQuery = Documento::whereRaw("LOWER(estado) IN ('aprobado','registro')")
            ->when(!$request->user()->isAdmin(), function ($query) use ($request) {
                $query->whereHas('permisos', function ($permisos) use ($request) {
                    $permisos->where('user_id', $request->user()->id)->where(function ($acceso) {
                        $acceso->where('puede_leer', true)->orWhere('puede_escribir', true)->orWhere('puede_aprobar', true)->orWhere('puede_eliminar', true);
                    });
                });
            });
        $documentos = (clone $documentosQuery)->orderBy('titulo')->get(['id', 'titulo']);
        $referenciados = $parte->evaluaciones->pluck('documento_id')->filter()->unique();
        $documentosHistoricosAccesibles = $request->user()->isAdmin()
            ? $referenciados
            : Documento::whereIn('id', $referenciados)->whereHas('permisos', function ($permisos) use ($request) {
                $permisos->where('user_id', $request->user()->id)->where(function ($acceso) {
                    $acceso->where('puede_leer', true)->orWhere('puede_escribir', true)->orWhere('puede_aprobar', true)->orWhere('puede_eliminar', true);
                });
            })->pluck('id');
        $documentosAccesibles = $documentos->pluck('id')->merge($documentosHistoricosAccesibles)->unique();
        return view('iso.partes.show', compact('parte', 'periodos', 'riesgos', 'areas', 'documentos', 'documentosAccesibles'));
    }

    public function update(Request $request, ParteInteresada $parte)
    {
        $request->merge(['area_responsable' => $request->input('area_responsable') === '__otro__' ? $request->input('area_responsable_otro') : $request->input('area_responsable')]);
        $data = $this->validarFicha($request);
        $clave = sha1(mb_strtolower(trim($data['nombre'])));
        if (ParteInteresada::where('clave', $clave)->whereKeyNot($parte->id)->exists()) {
            return back()->withErrors(['nombre' => 'Ya existe otra parte interesada con este nombre.'])->withInput();
        }
        $data['estado'] = $request->validate(['estado' => ['required', Rule::in(['activa', 'archivada'])]])['estado'];
        $parte->update($data + ['clave' => $clave, 'actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Ficha de la parte interesada actualizada.');
    }

    public function evaluar(Request $request, ParteInteresada $parte)
    {
        abort_if($parte->estado === 'archivada', 422, 'No se pueden agregar evaluaciones a una parte interesada archivada.');
        $data = $request->validate([
            'periodo_id' => 'required|exists:iso_periodos,id',
            'fecha_evaluacion' => 'required|date',
            'resultado' => ['required', Rule::in(['cumplido', 'parcial', 'no_cumplido', 'no_evaluado'])],
            'observaciones' => 'required|string|max:5000',
            'proxima_revision' => 'nullable|date|after_or_equal:fecha_evaluacion',
            'documento_id' => 'nullable|exists:documentos,id',
            'enlace_externo' => 'nullable|url|max:2000',
            'riesgos' => 'nullable|array', 'riesgos.*' => 'exists:iso_riesgos,id',
        ]);
        $this->periodosAbiertos->validar(Periodo::findOrFail($data['periodo_id']), 'registrar evaluaciones de partes interesadas');
        if (Riesgo::whereIn('id', $data['riesgos'] ?? [])->where('periodo_id', '!=', $data['periodo_id'])->exists()) {
            throw ValidationException::withMessages(['riesgos' => 'Los riesgos vinculados deben pertenecer al mismo período que la evaluación.']);
        }
        if (!empty($data['documento_id'])) {
            $documento = Documento::findOrFail($data['documento_id']);
            abort_unless($request->user()->isAdmin() || $documento->puedeLeer($request->user()), 403, 'No tenés permiso para vincular este documento.');
        }
        DB::transaction(function () use ($data, $parte, $request) {
            $evaluacion = $parte->evaluaciones()->create(collect($data)->except('riesgos')->all() + ['evaluado_por' => $request->user()->id]);
            $evaluacion->riesgos()->sync($data['riesgos'] ?? []);
        });
        return back()->with('success', 'Evaluación periódica registrada.');
    }

    private function validarFicha(Request $request, bool $incluyeNombre = true): array
    {
        $reglas = [
            'pertinente_sgc' => 'required|boolean', 'necesidades_requisitos' => 'required|string|max:5000',
            'area_responsable' => 'required|string|max:255', 'metodo_medicion' => 'required|string|max:3000',
            'requisitos_climaticos' => 'required|boolean', 'detalle_climatico' => 'nullable|string|max:3000',
        ];
        if ($incluyeNombre) $reglas = ['nombre' => 'required|string|max:255'] + $reglas;
        $data = $request->validate($reglas);
        if ($data['requisitos_climaticos'] && blank($data['detalle_climatico'])) {
            throw ValidationException::withMessages(['detalle_climatico' => 'Describí el requisito relacionado con cambio climático.']);
        }
        return $data;
    }
}
