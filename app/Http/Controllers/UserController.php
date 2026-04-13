<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'No autorizado.');
        }

        $query = User::query();

        if ($request->estado === 'habilitados') {
            $query->where('habilitado', true);
        }

        if ($request->estado === 'deshabilitados') {
            $query->where('habilitado', false);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;

            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $usuarios = $query->orderBy('name', 'asc')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    public function toggleHabilitado($id)
    {
        if (!auth()->check() || !auth()->user()->isAdmin()) {
            abort(403, 'No autorizado.');
        }

        $user = User::findOrFail($id);

        if (auth()->id() == $user->id) {
            return back()->with('error', 'No podés deshabilitar tu propio usuario.');
        }

        $user->habilitado = !$user->habilitado;
        $user->save();

        return back()->with('success', 'Estado del usuario actualizado correctamente.');
    }
}