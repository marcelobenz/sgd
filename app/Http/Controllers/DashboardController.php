<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Documento;
use App\Models\DocumentoRecordatorio;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Totales generales
        $totalDocumentos = Documento::count();

        $documentosAprobados = Documento::whereRaw('LOWER(estado) = ?', ['aprobado'])->count();

        $documentosPendientes = Documento::whereRaw('LOWER(estado) = ?', ['pendiente de aprobación'])->count();

        $documentosRegistro = Documento::whereRaw('LOWER(estado) = ?', ['registro'])->count();

        // Últimos documentos modificados
        $ultimosDocumentos = Documento::orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        // Total de documentos pendientes que el usuario puede aprobar
        $documentosPendientesUsuario = Documento::whereRaw('LOWER(estado) = ?', ['pendiente de aprobación'])
            ->whereHas('permisos', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('puede_aprobar', 1);
            })
            ->count();

        // Recordatorios vencidos
        $recordatoriosVencidos = 0;
        $proximosRecordatorios = collect();

        if (class_exists(DocumentoRecordatorio::class)) {
            $recordatoriosVencidos = DocumentoRecordatorio::with('documento')
                ->where('activo', 1)
                ->whereNotNull('proxima_ejecucion')
                ->where('proxima_ejecucion', '<', now())
                ->count();

            $proximosRecordatorios = DocumentoRecordatorio::with('documento')
                ->where('activo', 1)
                ->whereNotNull('proxima_ejecucion')
                ->where('proxima_ejecucion', '>=', now())
                ->orderBy('proxima_ejecucion', 'asc')
                ->take(5)
                ->get();
        }

        // Si todavía no tenés una entidad real de tareas/trámites pendientes, lo dejamos en 0
        $tramitesPendientes = 0;

        // Actividad reciente básica a partir de últimos documentos
        $actividadReciente = $ultimosDocumentos->map(function ($doc) {
            return [
                'titulo' => $doc->titulo,
                'descripcion' => 'Documento modificado',
                'fecha' => optional($doc->updated_at)?->format('d/m/Y H:i'),
            ];
        });

        return view('dashboard', [
            'totalDocumentos' => $totalDocumentos,
            'documentosAprobados' => $documentosAprobados,
            'documentosPendientes' => $documentosPendientes,
            'documentosRegistro' => $documentosRegistro,
            'documentosPendientesUsuario' => $documentosPendientesUsuario,
            'recordatoriosVencidos' => $recordatoriosVencidos,
            'tramitesPendientes' => $tramitesPendientes,
            'ultimosDocumentos' => $ultimosDocumentos,
            'proximosRecordatorios' => $proximosRecordatorios,
            'actividadReciente' => $actividadReciente,
        ]);
    }
}