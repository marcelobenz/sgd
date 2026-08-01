<?php

namespace App\Http\Middleware;

use App\Models\Iso\UsuarioPermiso;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsoAccess
{
    public function handle(Request $request, Closure $next, string $nivel = 'view'): Response
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return $next($request);
        }

        $permiso = $user ? UsuarioPermiso::where('user_id', $user->id)->first() : null;
        $allowed = match ($nivel) {
            'admin' => $permiso?->puede_administrar,
            'manage' => $permiso?->puede_gestionar || $permiso?->puede_administrar,
            default => $permiso?->puede_ver || $permiso?->puede_gestionar || $permiso?->puede_administrar,
        };

        abort_unless($allowed, 403, 'No tenés permisos para acceder a Planificación.');

        return $next($request);
    }
}
