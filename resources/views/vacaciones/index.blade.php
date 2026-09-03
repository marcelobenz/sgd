@extends('layouts.main')

@push('styles')
<style>
    .vacaciones-gantt { overflow-x: auto; }
    .vacaciones-gantt-grid { min-width: 760px; }
    .vacaciones-gantt-months, .vacaciones-gantt-row { display: grid; grid-template-columns: 150px repeat(12, minmax(48px, 1fr)); }
    .vacaciones-gantt-months { border-bottom: 1px solid #dee2e6; }
    .vacaciones-gantt-month { padding: .45rem .25rem; text-align: center; font-size: .78rem; color: #6c757d; border-left: 1px solid #f0f0f0; }
    .vacaciones-gantt-name { padding: .55rem .5rem .55rem 0; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .vacaciones-gantt-track { grid-column: 2 / 14; position: relative; min-height: 42px; background: repeating-linear-gradient(to right, transparent 0, transparent calc(8.333% - 1px), #f0f0f0 calc(8.333% - 1px), #f0f0f0 8.333%); border-bottom: 1px solid #f0f0f0; }
    .vacaciones-gantt-bar { position: absolute; top: 9px; min-width: 4px; height: 24px; border-radius: 4px; }
    .vacaciones-gantt-bar-label { position: absolute; left: calc(100% + 5px); top: 3px; z-index: 2; white-space: nowrap; padding: 1px 4px; border-radius: 3px; background: rgba(255,255,255,.92); color: #263238; font-size: .72rem; font-weight: 600; }
    .vacaciones-gantt-bar-label.label-left { left: auto; right: calc(100% + 5px); }
    .vacaciones-gantt-bar.aprobada { background: #28a745; }
    .vacaciones-gantt-bar.pendiente { background: #f0ad4e; color: #212529; }
    .vacaciones-gantt-legend { display: flex; gap: 1rem; flex-wrap: wrap; font-size: .8rem; color: #6c757d; }
    .vacaciones-gantt-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 4px; }
</style>
@endpush

@section('contenidoPrincipal')
<div class="container" style="margin-top: 80px;">
    <a href="{{ route('vacaciones.index') }}" class="btn btn-link px-0 mb-3">← Menú de Vacaciones</a>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div><h2 class="mb-1">Vacaciones</h2><p class="text-muted mb-0">Gestión de solicitudes y días disponibles.</p></div>
        @if($modo !== 'configuracion')<form method="GET" action="{{ route('vacaciones.'.($modo === 'admin' ? 'admin' : 'mis')) }}" class="form-inline">
            <label for="anio" class="mr-2">Período</label>
            <select id="anio" name="anio" class="form-control" onchange="this.form.submit()">
                @for($y = now()->year - 1; $y <= now()->year + 1; $y++)<option value="{{ $y }}" @selected($anio === $y)>{{ $y }}</option>@endfor
            </select>
        </form>@endif
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

    @if($modo === 'admin')
        <form method="GET" action="{{ route('vacaciones.admin') }}" class="form-row align-items-end mb-4">
            <input type="hidden" name="anio" value="{{ $anio }}">
            <div class="form-group col-md-4"><label for="empleado_id">Empleado</label><select id="empleado_id" name="empleado_id" class="form-control"><option value="">Todos</option>@foreach($resumenEquipo as $item)<option value="{{ $item['usuario']->id }}" @selected($filtroEmpleado === $item['usuario']->id)>{{ $item['usuario']->name }}</option>@endforeach</select></div>
            <div class="form-group col-md-3"><label for="estado_licencia">Estado</label><select id="estado_licencia" name="estado_licencia" class="form-control"><option value="">Todos</option><option value="pendiente" @selected($filtroEstado === 'pendiente')>Pendiente</option><option value="aprobada" @selected($filtroEstado === 'aprobada')>Aprobada</option><option value="rechazada" @selected($filtroEstado === 'rechazada')>Rechazada</option><option value="cancelada" @selected($filtroEstado === 'cancelada')>Cancelada</option></select></div>
            <div class="form-group col-md-2"><button class="btn btn-primary btn-block">Filtrar</button></div>
            <div class="form-group col-md-2"><a href="{{ route('vacaciones.admin', ['anio' => $anio]) }}" class="btn btn-outline-secondary btn-block">Limpiar</a></div>
        </form>
    @endif

    @if($modo === 'mis')<div class="row mb-4">
        @foreach([['Total anual', $resumen['total'], 'primary'], ['Tomados', $resumen['usados'], 'success'], ['Pendientes', $resumen['pendientes'], 'info'], ['En revisión', $resumen['reservados'], 'warning']] as [$label, $value, $color])
            <div class="col-md-3 mb-3"><div class="card border-{{ $color }} h-100"><div class="card-body"><small class="text-muted">{{ $label }}</small><div class="display-4">{{ $value === null ? '—' : $value }}</div><small>días corridos · {{ $anio }}</small></div></div></div>
        @endforeach
    </div>

    @if(!$usuario->fecha_ingreso)
        <div class="alert alert-warning">Tu perfil todavía no tiene fecha de ingreso configurada. Contactá al administrador.</div>
    @else
        <div class="card mb-4"><div class="card-header"><strong>Solicitar vacaciones</strong></div><div class="card-body">
            <form method="POST" action="{{ route('vacaciones.store') }}"><div class="form-row align-items-end">@csrf
                <div class="form-group col-md-3"><label>Desde</label><input type="date" name="fecha_desde" class="form-control" value="{{ old('fecha_desde') }}" required></div>
                <div class="form-group col-md-3"><label>Hasta</label><input type="date" name="fecha_hasta" class="form-control" value="{{ old('fecha_hasta') }}" required></div>
                <div class="form-group col-md-4"><label>Observaciones</label><input type="text" name="observaciones" class="form-control" value="{{ old('observaciones') }}" maxlength="2000"></div>
                <div class="form-group col-md-2"><button class="btn btn-primary btn-block">Enviar solicitud</button></div>
            </div></form>
        </div></div>
    @endif

    <div class="card mb-4"><div class="card-header"><strong>Mis solicitudes {{ $anio }}</strong></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Período</th><th>Días</th><th>Estado</th><th>Observaciones</th></tr></thead><tbody>
        @forelse($solicitudes as $solicitud)<tr><td>{{ $solicitud->fecha_desde->format('d/m/Y') }} al {{ $solicitud->fecha_hasta->format('d/m/Y') }}</td><td>{{ $solicitud->dias }}</td><td><span class="badge badge-{{ ['pendiente'=>'warning','aprobada'=>'success','rechazada'=>'danger','cancelada'=>'secondary'][$solicitud->estado] }}">{{ ucfirst($solicitud->estado) }}</span>@if($solicitud->motivo_rechazo)<div class="small text-danger">{{ $solicitud->motivo_rechazo }}</div>@endif</td><td>{{ $solicitud->observaciones ?: '—' }} @if($solicitud->estado === 'pendiente')<form method="POST" action="{{ route('vacaciones.cancelar', $solicitud) }}" class="d-inline ml-2">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">Cancelar</button></form>@endif</td></tr>@empty<tr><td colspan="4" class="text-muted text-center">No hay solicitudes para este período.</td></tr>@endforelse
    </tbody></table></div></div>@endif

    @if($modo === 'admin')<div class="card mb-4"><div class="card-header"><strong>Resumen del equipo {{ $anio }}</strong><small class="text-muted d-block">Sólo se muestran empleados a cargo del jefe.</small></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Empleado</th><th>Área</th><th>Total</th><th>Tomados</th><th>Pendientes</th><th>En revisión</th></tr></thead><tbody>@forelse($resumenEquipo as $item)<tr><td>{{ $item['usuario']->name }}</td><td>{{ $item['usuario']->area ?: '—' }}</td><td>{{ $item['resumen']['total'] ?? '—' }}</td><td>{{ $item['resumen']['usados'] }}</td><td>{{ $item['resumen']['pendientes'] ?? '—' }}</td><td>{{ $item['resumen']['reservados'] }}</td></tr>@empty<tr><td colspan="6" class="text-muted text-center">No hay empleados a cargo configurados.</td></tr>@endforelse</tbody></table></div></div>@endif

    @if($modo === 'admin')
        @php
            $inicioAnio = \Carbon\Carbon::create($anio, 1, 1)->startOfDay();
            $diasAnio = $inicioAnio->diffInDays($inicioAnio->copy()->endOfYear()) + 1;
            $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        @endphp
        <div class="card mb-4">
            <div class="card-header"><strong>Vacaciones planificadas {{ $anio }}</strong><small class="text-muted d-block">Vista Gantt del equipo. Las barras verdes están aprobadas y las amarillas están pendientes de validación.</small></div>
            <div class="card-body vacaciones-gantt">
                <div class="vacaciones-gantt-grid" role="img" aria-label="Cronograma de vacaciones planificadas del equipo para {{ $anio }}">
                    <div class="vacaciones-gantt-months"><div></div>@foreach($meses as $mes)<div class="vacaciones-gantt-month">{{ $mes }}</div>@endforeach</div>
                    @foreach($resumenEquipo as $item)
                        @php
                            $empleadoId = $item['usuario']->id;
                        @endphp
                        <div class="vacaciones-gantt-row">
                            <div class="vacaciones-gantt-name">{{ $item['usuario']->name }}</div>
                            <div class="vacaciones-gantt-track">
                                @foreach($historialEquipo->where('user_id', $empleadoId)->whereIn('estado', ['pendiente', 'aprobada']) as $solicitud)
                                    @php
                                        $desde = $solicitud->fecha_desde->greaterThan($inicioAnio) ? $solicitud->fecha_desde : $inicioAnio;
                                        $hasta = $solicitud->fecha_hasta->lessThan($inicioAnio->copy()->endOfYear()) ? $solicitud->fecha_hasta : $inicioAnio->copy()->endOfYear();
                                        $left = ($inicioAnio->diffInDays($desde) / $diasAnio) * 100;
                                        $width = (($desde->diffInDays($hasta) + 1) / $diasAnio) * 100;
                                        $etiqueta = $desde->month === $hasta->month ? $desde->format('d').'-'.$hasta->format('d') : $desde->format('d/m').'-'.$hasta->format('d/m');
                                        $posicionEtiqueta = $left > 82 ? 'label-left' : '';
                                    @endphp
                                    <span class="vacaciones-gantt-bar {{ $solicitud->estado }}" style="left: {{ $left }}%; width: {{ $width }}%;" aria-label="{{ $solicitud->fecha_desde->format('d/m/Y') }} al {{ $solicitud->fecha_hasta->format('d/m/Y') }}, {{ $solicitud->dias }} días, {{ $solicitud->estado }}"><span class="vacaciones-gantt-bar-label {{ $posicionEtiqueta }}">{{ $etiqueta }}</span></span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @if($resumenEquipo->isEmpty())<div class="text-muted py-3">No hay empleados a cargo configurados.</div>@endif
                </div>
                <div class="vacaciones-gantt-legend mt-3"><span><i class="vacaciones-gantt-dot" style="background:#28a745"></i>Aprobada</span><span><i class="vacaciones-gantt-dot" style="background:#f0ad4e"></i>Pendiente</span></div>
            </div>
        </div>
    @endif

    @if($modo === 'admin')<div class="card mb-4"><div class="card-header"><strong>Historial de vacaciones {{ $anio }}</strong><small class="text-muted d-block">Incluye solicitudes pendientes, aprobadas, rechazadas y canceladas del personal a cargo.</small></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Empleado</th><th>Período</th><th>Días</th><th>Estado</th><th>Resuelto por</th><th>Observaciones</th></tr></thead><tbody>@forelse($historialEquipo as $solicitud)<tr><td>{{ $solicitud->usuario->name }}</td><td>{{ $solicitud->fecha_desde->format('d/m/Y') }} al {{ $solicitud->fecha_hasta->format('d/m/Y') }}</td><td>{{ $solicitud->dias }}</td><td>{{ ucfirst($solicitud->estado) }}</td><td>{{ $solicitud->revisor?->name ?: '—' }}</td><td>{{ $solicitud->observaciones ?: ($solicitud->motivo_rechazo ?: '—') }}</td></tr>@empty<tr><td colspan="6" class="text-muted text-center">No hay movimientos registrados para este período.</td></tr>@endforelse</tbody></table></div></div>@endif

    @if($modo === 'admin')<div class="card mb-4"><div class="card-header"><strong>Solicitudes para validar</strong><small class="text-muted d-block">Las aprobadas sólo pueden revertirse mientras la fecha de inicio sea futura.</small></div><div class="table-responsive"><table class="table table-hover mb-0"><thead><tr><th>Persona</th><th>Período</th><th>Días</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
        @foreach($paraAprobar as $solicitud)<tr><td>{{ $solicitud->usuario->name }}</td><td>{{ $solicitud->fecha_desde->format('d/m/Y') }} al {{ $solicitud->fecha_hasta->format('d/m/Y') }}</td><td>{{ $solicitud->dias }}</td><td>{{ ucfirst($solicitud->estado) }}</td><td class="text-nowrap">@if($solicitud->estado === 'pendiente')<form class="d-inline" method="POST" action="{{ route('vacaciones.aprobar', $solicitud) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success">Aprobar</button></form> <form class="d-inline" method="POST" action="{{ route('vacaciones.rechazar', $solicitud) }}">@csrf @method('PATCH')<input type="hidden" name="motivo_rechazo" value="Revisar disponibilidad y coordinar con el área."><button class="btn btn-sm btn-outline-danger">Rechazar</button></form>@else<form class="d-inline" method="POST" action="{{ route('vacaciones.desaprobar', $solicitud) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning">Desaprobar</button></form>@endif</td></tr>@endforeach
    </tbody></table></div></div>@endif

    @if($modo === 'configuracion')<div class="card"><div class="card-header"><strong>Configuración laboral</strong><small class="text-muted d-block">Sólo el administrador general puede asignar fecha de ingreso, área y jefe.</small></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Usuario</th><th>Área</th><th>Fecha de ingreso</th><th>Jefe de área</th><th></th></tr></thead><tbody>@foreach($usuarios as $u)<tr><form method="POST" action="{{ route('vacaciones.usuarios.update', $u) }}">@csrf @method('PATCH')<td>{{ $u->name }}</td><td><input name="area" class="form-control form-control-sm" value="{{ $u->area }}"></td><td><input type="date" name="fecha_ingreso" class="form-control form-control-sm" value="{{ optional($u->fecha_ingreso)->format('Y-m-d') }}"></td><td><select name="jefe_id" class="form-control form-control-sm"><option value="">Sin jefe</option>@foreach($usuarios as $j)<option value="{{ $j->id }}" @selected($u->jefe_id === $j->id)>{{ $j->name }}</option>@endforeach</select></td><td><button class="btn btn-sm btn-outline-primary">Guardar</button></td></form></tr>@endforeach</tbody></table></div></div>@endif
</div>
@endsection
