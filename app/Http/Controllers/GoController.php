<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GoController extends Controller
{
    public function __invoke(Request $request)
    {
        $next = $request->query('next', '/dashboard');

        // Sanitizar: solo paths locales (evita open redirect)
        $ok = is_string($next)
            && str_starts_with($next, '/')
            && !preg_match('#^//|https?://#i', $next);

        if (!$ok) {
            $next = '/dashboard';
        }

        // Siempre redirigimos al destino.
        // Si no hay sesión, el middleware 'auth' en /documentos/... te lleva al login
        // y Laravel recuerda la intended automáticamente.
        return redirect($next);
    }

}
