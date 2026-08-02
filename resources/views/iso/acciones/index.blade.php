@extends('layouts.main')
@include('iso._styles')
@section('heading','Acciones y seguimiento')
@section('contenidoPrincipal')
@php
    $ordenActual = request('orden');
    $direccionActual = request('direccion', 'asc');
    $ordenUrl = function ($campo) use ($ordenActual, $direccionActual) {
        $direccion = $ordenActual === $campo && $direccionActual === 'asc' ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['orden' => $campo, 'direccion' => $direccion]);
    };
    $ordenIcono = fn ($campo) => $ordenActual === $campo ? ($direccionActual === 'asc' ? ' ↑' : ' ↓') : '';
@endphp
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Ejecución y control</div>
    <h1 class="iso-title">Acciones y seguimiento</h1>
    <p class="iso-subtitle">Tablero operativo de acciones, vencimientos, evidencias y riesgos pendientes de verificación.</p>
    <form method="GET" class="iso-panel mb-4"><div class="iso-panel-body"><div class="form-row">
        <div class="form-group col-md-2"><label>Período</label><select name="periodo" class="form-control"><option value="">Seleccionar</option>@foreach($periodos as $p)<option value="{{ $p->id }}" @selected($periodo?->id===$p->id)>{{ $p->anio }}</option>@endforeach</select></div>
        <div class="form-group col-md-2"><label>Tipo</label><select name="tipo" class="form-control"><option value="">Todos</option><option value="riesgo" @selected(request('tipo')==='riesgo')>Riesgo</option><option value="oportunidad" @selected(request('tipo')==='oportunidad')>Oportunidad</option></select></div>
        <div class="form-group col-md-2"><label>Estado</label><select name="estado" class="form-control"><option value="">Todos</option>@foreach(['pendiente'=>'Pendiente','en_proceso'=>'En proceso','completada'=>'Completada','cancelada'=>'Cancelada'] as $valor=>$etiqueta)<option value="{{ $valor }}" @selected(request('estado')===$valor)>{{ $etiqueta }}</option>@endforeach</select></div>
        <div class="form-group col-md-3"><label>Responsable</label><select name="responsable" class="form-control"><option value="">Todos</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(request('responsable')==$usuario->id)>{{ $usuario->name }}</option>@endforeach</select></div>
        <div class="form-group col-md-3 d-flex align-items-end"><button class="btn btn-primary mr-2">Aplicar filtros</button><a class="btn btn-light" href="{{ route('planificacion.acciones.index') }}">Limpiar</a></div>
    </div><div class="d-flex flex-wrap" style="gap:16px"><label><input type="checkbox" name="mis_acciones" value="1" @checked(request()->boolean('mis_acciones'))> Mis acciones</label><label><input type="checkbox" name="vencidas" value="1" @checked(request()->boolean('vencidas'))> Sólo vencidas</label><label><input type="checkbox" name="sin_evidencia" value="1" @checked(request()->boolean('sin_evidencia'))> Sin evidencia</label><label><input type="checkbox" name="pendiente_verificacion" value="1" @checked(request()->boolean('pendiente_verificacion'))> Pendientes de verificación</label></div></div></form>

    <div class="iso-panel"><div class="table-responsive"><table class="table iso-table mb-0"><thead><tr>@foreach(['accion'=>'Acción','riesgo'=>'Riesgo / oportunidad','responsable'=>'Responsable','fecha'=>'Fecha objetivo','estado'=>'Estado','seguimientos'=>'Seguimiento / evidencia','verificacion'=>'Evaluación de eficacia'] as $campo=>$titulo)<th><a href="{{ $ordenUrl($campo) }}" class="text-reset text-decoration-none">{{ $titulo }}{{ $ordenIcono($campo) }}</a></th>@endforeach<th></th></tr></thead><tbody>
        @forelse($acciones as $accion)
        @php
            $vencida = in_array($accion->estado, ['pendiente', 'en_proceso']) && $accion->fecha_objetivo?->isPast();
            $tieneEvidencia = $accion->seguimientos->contains(function ($seguimiento) {
                return $seguimiento->documento_id || $seguimiento->enlace_externo;
            });
        @endphp
        <tr><td><strong>{{ $accion->descripcion }}</strong>@if($accion->resultado)<div class="text-muted">{{ Str::limit($accion->resultado,120) }}</div>@endif</td><td><span class="iso-code">{{ $accion->riesgo->codigo }}</span><div>{{ ucfirst($accion->riesgo->tipo) }}</div></td><td>{{ $accion->responsable?->name ?: 'Sin asignar' }}</td><td>{{ $accion->fecha_objetivo?->format('d/m/Y') }} @if($vencida)<span class="iso-status danger">Vencida</span>@endif</td><td><span class="iso-status {{ $accion->estado==='completada'?'ok':($vencida?'danger':'') }}">{{ ucfirst(str_replace('_',' ',$accion->estado)) }}</span></td><td>{{ $accion->seguimientos->count() }} registros @if(!$tieneEvidencia)<div class="text-muted">Sin evidencia vinculada</div>@endif</td><td>@if($accion->riesgo->estado==='finalizado')<span class="iso-status ok">Finalizado: {{ ucfirst($accion->riesgo->eficacia) }}</span>@elseif($accion->riesgo->fecha_verificacion_prevista){{ $accion->riesgo->fecha_verificacion_prevista->format('d/m/Y') }} @if($accion->riesgo->fecha_verificacion_prevista->lte(today()))<span class="iso-status danger">Vencida</span>@else<span class="iso-status">Programada</span>@endif<div class="text-muted">Última eficacia: {{ ucfirst($accion->riesgo->eficacia) }}</div>@else<span class="text-muted">Sin programar</span>@endif</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('planificacion.riesgos.show',$accion->riesgo) }}">Abrir</a></td></tr>
        @empty<tr><td colspan="8" class="text-center text-muted py-4">No hay acciones para los filtros seleccionados.</td></tr>@endforelse
    </tbody></table></div></div>
</div>
@endsection
