<?php

namespace App\Http\Controllers;

use App\Models\Iso\Periodo;
use App\Services\BandejaPendientesService;
use Illuminate\Http\Request;

class PendienteController extends Controller
{
    public function index(Request $request, BandejaPendientesService $bandeja)
    {
        $periodo = Periodo::where('estado', 'vigente')->first() ?? Periodo::latest('anio')->first();
        $preferencias = $request->user()->preferenciasConDefaults();
        $todosLosPendientes = $bandeja->paraUsuario($request->user(), $periodo, (int) $preferencias['horizonte_dias']);
        $pendientes = $todosLosPendientes;

        if (! $request->hasAny(['grupo', 'prioridad', 'estado'])) {
            $pendientes = match ($preferencias['pendientes_filtro']) {
                'urgentes' => $pendientes->whereIn('prioridad', ['vencida', 'hoy', 'proxima'])->values(),
                'iso' => $pendientes->where('grupo', 'iso')->values(),
                'documentos' => $pendientes->where('grupo', 'documentos')->values(),
                default => $pendientes,
            };
        }

        if ($request->filled('grupo')) {
            $pendientes = $pendientes->where('grupo', $request->string('grupo')->toString())->values();
        }

        if ($request->filled('prioridad')) {
            $pendientes = $pendientes->where('prioridad', $request->string('prioridad')->toString())->values();
        }

        if ($request->filled('estado')) {
            $pendientes = $pendientes->filter(fn (array $item) => str($item['estado'])->slug()->toString() === $request->string('estado')->toString())->values();
        }

        return view('pendientes.index', [
            'periodo' => $periodo,
            'pendientes' => $pendientes,
            'resumen' => $bandeja->resumen($todosLosPendientes),
            'preferencias' => $preferencias,
        ]);
    }
}
