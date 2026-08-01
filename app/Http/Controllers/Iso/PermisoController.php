<?php

namespace App\Http\Controllers\Iso;

use App\Http\Controllers\Controller;
use App\Models\Iso\UsuarioPermiso;
use App\Models\User;
use Illuminate\Http\Request;

class PermisoController extends Controller
{
    public function index()
    {
        $usuarios = User::habilitados()->with('permisoIso')->orderBy('name')->get();
        return view('iso.permisos.index', compact('usuarios'));
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->isAdmin(), 422, 'Los administradores ya tienen acceso completo.');

        $niveles = ['sin_acceso', 'consulta', 'gestion', 'administracion'];
        $data = $request->validate(['nivel' => 'required|in:' . implode(',', $niveles)]);

        if ($data['nivel'] === 'sin_acceso') {
            $user->permisoIso()->delete();
        } else {
            UsuarioPermiso::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'puede_ver' => $data['nivel'] === 'consulta',
                    'puede_gestionar' => $data['nivel'] === 'gestion',
                    'puede_administrar' => $data['nivel'] === 'administracion',
                ]
            );
        }

        return back()->with('success', 'Permiso de Planificación actualizado.');
    }
}
