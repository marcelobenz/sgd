<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\Accion;
use App\Models\Iso\Riesgo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccionController extends Controller
{
    public function store(Request $request, Riesgo $riesgo)
    {
        $data = $request->validate(['descripcion' => 'required|string|max:5000', 'responsable_id' => 'required|exists:users,id', 'fecha_objetivo' => 'required|date']);
        $riesgo->acciones()->create($data + ['creado_por' => $request->user()->id, 'actualizado_por' => $request->user()->id]);
        if ($riesgo->estado === 'pendiente') $riesgo->update(['estado' => 'en_proceso', 'actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Acción agregada.');
    }

    public function actualizar(Request $request, Accion $accion)
    {
        $data = $request->validate(['estado' => ['required', Rule::in(['pendiente', 'en_proceso', 'completada', 'cancelada'])], 'resultado' => 'nullable|string|max:5000']);
        $accion->update($data + ['actualizado_por' => $request->user()->id, 'completada_en' => $data['estado'] === 'completada' ? now() : null]);
        return back()->with('success', 'Acción actualizada.');
    }

    public function seguimiento(Request $request, Accion $accion)
    {
        $data = $request->validate([
            'fecha' => 'required|date', 'detalle' => 'required|string|max:5000', 'resultado' => 'nullable|string|max:1000',
            'documento_id' => 'nullable|exists:documentos,id', 'enlace_externo' => 'nullable|url|max:2000',
        ]);
        $accion->seguimientos()->create($data + ['registrado_por' => $request->user()->id]);
        if ($accion->estado === 'pendiente') $accion->update(['estado' => 'en_proceso', 'actualizado_por' => $request->user()->id]);
        return back()->with('success', 'Seguimiento y evidencia registrados.');
    }
}
