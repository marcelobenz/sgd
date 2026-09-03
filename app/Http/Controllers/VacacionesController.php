<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\VacacionesSolicitud;
use App\Notifications\VacacionesSolicitudActualizada;
use App\Services\VacacionesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VacacionesController extends Controller
{
    public function __construct(private VacacionesService $vacaciones) {}

    public function index()
    {
        return view('vacaciones.menu');
    }

    public function mis(Request $request)
    {
        return $this->estado($request, 'mis');
    }

    public function admin(Request $request)
    {
        abort_unless($request->user()->puedeGestionarVacaciones(), 403, 'No tenés personal a cargo para administrar licencias.');

        return $this->estado($request, 'admin');
    }

    public function configuracion(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403, 'Sólo el administrador general puede configurar datos laborales.');

        return $this->estado($request, 'configuracion');
    }

    private function estado(Request $request, string $modo)
    {
        $anio = (int) $request->integer('anio', now()->year);
        abort_unless($anio >= 2020 && $anio <= 2100, 422, 'El período seleccionado no es válido.');

        $usuario = $request->user()->load('jefe');
        $resumen = $this->vacaciones->resumen($usuario, $anio);
        $solicitudes = $usuario->solicitudesVacaciones()->whereYear('fecha_desde', $anio)->latest('fecha_desde')->get();

        $paraAprobar = VacacionesSolicitud::with('usuario')
            ->whereYear('fecha_desde', $anio)
            ->where(function ($query) {
                $query->where('estado', 'pendiente')
                    ->orWhere(function ($future) {
                        $future->where('estado', 'aprobada')->whereDate('fecha_desde', '>', today());
                    });
            })
            ->when(! $usuario->isAdmin(), fn ($query) => $query->whereHas('usuario', fn ($q) => $q->where('jefe_id', $usuario->id)))
            ->orderBy('fecha_desde')
            ->get();

        $equipo = collect();
        if ($usuario->isAdmin()) {
            $equipo = User::habilitados()->with('jefe')->orderBy('name')->get();
        } elseif ($usuario->colaboradores()->exists()) {
            $equipo = $usuario->colaboradores()->where('habilitado', true)->orderBy('name')->get();
        }

        $resumenEquipo = $equipo->map(fn (User $miembro) => [
            'usuario' => $miembro,
            'resumen' => $this->vacaciones->resumen($miembro, $anio),
        ]);

        $filtroEmpleado = $request->integer('empleado_id') ?: null;
        $filtroEstado = $request->input('estado_licencia');
        if (! in_array($filtroEstado, ['pendiente', 'aprobada', 'rechazada', 'cancelada'], true)) {
            $filtroEstado = null;
        }
        if ($filtroEmpleado && ! $equipo->contains('id', $filtroEmpleado)) {
            $filtroEmpleado = null;
        }

        $historialQuery = VacacionesSolicitud::with(['usuario', 'revisor'])
            ->whereIn('user_id', $equipo->pluck('id'))
            ->whereYear('fecha_desde', $anio)
            ->when($filtroEmpleado, fn ($query) => $query->where('user_id', $filtroEmpleado))
            ->when($filtroEstado, fn ($query) => $query->where('estado', $filtroEstado));

        $historialEquipo = $historialQuery->latest('fecha_desde')->get();

        if ($filtroEstado) {
            $paraAprobar = $paraAprobar->where('estado', $filtroEstado)->values();
        }
        if ($filtroEmpleado) {
            $paraAprobar = $paraAprobar->where('user_id', $filtroEmpleado)->values();
        }

        $usuarios = $request->user()->isAdmin()
            ? User::habilitados()->with('jefe')->orderBy('name')->get()
            : collect();

        return view('vacaciones.index', compact('modo', 'anio', 'usuario', 'resumen', 'solicitudes', 'paraAprobar', 'usuarios', 'resumenEquipo', 'historialEquipo', 'filtroEmpleado', 'filtroEstado'));
    }

    public function store(Request $request)
    {
        $usuario = $request->user();
        abort_unless($usuario->fecha_ingreso, 422, 'Tu perfil laboral todavía no tiene fecha de ingreso.');

        $data = $request->validate([
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['required', 'date', 'after_or_equal:fecha_desde'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $desde = Carbon::parse($data['fecha_desde']);
        $hasta = Carbon::parse($data['fecha_hasta']);
        abort_unless($desde->year === $hasta->year, 422, 'La solicitud debe estar dentro del mismo año.');

        $dias = $this->vacaciones->diasEntre($desde, $hasta);
        $solapa = VacacionesSolicitud::where('user_id', $usuario->id)
            ->whereIn('estado', ['pendiente', 'aprobada'])
            ->whereDate('fecha_desde', '<=', $hasta)
            ->whereDate('fecha_hasta', '>=', $desde)
            ->exists();
        abort_if($solapa, 422, 'El período se superpone con otra solicitud vigente.');

        $resumen = $this->vacaciones->resumen($usuario, $desde->year);
        abort_if($resumen['total'] !== null && $resumen['total'] < $resumen['usados'] + $resumen['reservados'] + $dias, 422, 'La solicitud supera los días disponibles.');

        VacacionesSolicitud::create([
            'user_id' => $usuario->id,
            'creada_por' => $usuario->id,
            'fecha_desde' => $desde,
            'fecha_hasta' => $hasta,
            'dias' => $dias,
            'observaciones' => $data['observaciones'] ?? null,
        ]);

        return back()->with('success', 'Solicitud de vacaciones enviada al jefe del área.');
    }

    public function aprobar(Request $request, VacacionesSolicitud $solicitud)
    {
        $this->autorizar($request, $solicitud);
        abort_if($solicitud->estado !== 'pendiente', 422, 'Sólo se pueden aprobar solicitudes pendientes.');
        $resumen = $this->vacaciones->resumen($solicitud->usuario, $solicitud->fecha_desde->year);
        abort_if($resumen['total'] !== null && $resumen['total'] < $resumen['usados'] + $solicitud->dias, 422, 'La aprobación supera los días disponibles.');

        $solicitud->update(['estado' => 'aprobada', 'revisada_por' => $request->user()->id, 'revisada_at' => now()]);
        $solicitud->usuario->notify(new VacacionesSolicitudActualizada($solicitud, 'aprobada'));

        return back()->with('success', 'Solicitud aprobada.');
    }

    public function rechazar(Request $request, VacacionesSolicitud $solicitud)
    {
        $this->autorizar($request, $solicitud);
        abort_if($solicitud->estado !== 'pendiente', 422, 'Sólo se pueden rechazar solicitudes pendientes.');
        $data = $request->validate(['motivo_rechazo' => ['required', 'string', 'max:2000']]);
        $solicitud->update(['estado' => 'rechazada', 'motivo_rechazo' => $data['motivo_rechazo'], 'revisada_por' => $request->user()->id, 'revisada_at' => now()]);
        $solicitud->usuario->notify(new VacacionesSolicitudActualizada($solicitud, 'rechazada', $data['motivo_rechazo']));

        return back()->with('success', 'Solicitud rechazada.');
    }

    public function desaprobar(Request $request, VacacionesSolicitud $solicitud)
    {
        $this->autorizar($request, $solicitud);
        abort_unless($solicitud->estado === 'aprobada', 422, 'Sólo se pueden desaprobar solicitudes aprobadas.');
        abort_unless($solicitud->fecha_desde->isFuture(), 422, 'La solicitud ya comenzó o su fecha ya pasó.');

        $solicitud->update([
            'estado' => 'pendiente',
            'revisada_por' => null,
            'revisada_at' => null,
            'motivo_rechazo' => null,
        ]);
        $solicitud->usuario->notify(new VacacionesSolicitudActualizada($solicitud, 'desaprobada'));

        return back()->with('success', 'La aprobación fue revertida y la solicitud volvió a quedar pendiente.');
    }

    public function cancelar(Request $request, VacacionesSolicitud $solicitud)
    {
        abort_unless($solicitud->user_id === $request->user()->id, 403, 'Sólo podés cancelar tus propias solicitudes.');
        abort_if($solicitud->estado !== 'pendiente', 422, 'Sólo se pueden cancelar solicitudes pendientes.');
        $solicitud->update(['estado' => 'cancelada']);

        return back()->with('success', 'Solicitud cancelada.');
    }

    public function actualizarUsuario(Request $request, User $user)
    {
        abort_unless($request->user()->isAdmin(), 403, 'No autorizado.');
        $data = $request->validate([
            'fecha_ingreso' => ['nullable', 'date'],
            'area' => ['nullable', 'string', 'max:150'],
            'jefe_id' => ['nullable', Rule::exists('users', 'id')->where('habilitado', true)],
        ]);
        abort_if((int) ($data['jefe_id'] ?? 0) === $user->id, 422, 'El usuario no puede ser su propio jefe.');
        $user->update($data);

        return back()->with('success', 'Datos laborales actualizados.');
    }

    private function autorizar(Request $request, VacacionesSolicitud $solicitud): void
    {
        abort_unless($request->user()->isAdmin() || $solicitud->usuario->jefe_id === $request->user()->id, 403, 'Sólo el jefe del área del empleado puede resolver esta solicitud.');
    }
}
