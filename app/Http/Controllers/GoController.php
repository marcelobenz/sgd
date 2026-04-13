<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoController extends Controller
{
    public function __invoke(Request $request)
    {
        $next = $request->query('next', '/dashboard');

        // Agregamos un log para ver qué valor de 'next' llega
        Log::info('Valor de next recibido:', ['next' => $next]);

        // Solo paths locales (evita open redirect)
        $ok = is_string($next)
            && str_starts_with($next, '/')
            && !preg_match('#^//|https?://#i', $next);

        if (!$ok) {
            $next = '/dashboard';
        }

        return auth()->check()
            ? redirect($next)                                // logueado → va directo
            : redirect()->route('login', ['next' => $next]); // sin sesión → login con next
    }
}
