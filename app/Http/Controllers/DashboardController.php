<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Documento; // Asegúrate de tener el modelo Documento

class DashboardController extends Controller
{
    public function index()
    {
        // Total de documentos por estado
        $totalPorEstado = Documento::selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->get()
            ->keyBy('estado')
            ->map->total;

        // Últimos 3 documentos modificados
        $ultimosDocumentos = Documento::orderBy('updated_at', 'desc')
            ->take(3)
            ->get();

        // Total de documentos sin aprobar
        $totalSinAprobar = Documento::where('estado', 'pendiente de aprobación')
            ->count();

        return view('dashboard', [
            'totalPorEstado' => $totalPorEstado,
            'ultimosDocumentos' => $ultimosDocumentos,
            'totalSinAprobar' => $totalSinAprobar,
        ]);
    }
}
