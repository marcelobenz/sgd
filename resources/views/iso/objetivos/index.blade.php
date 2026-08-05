@extends('layouts.main')
@include('iso._styles')
@section('heading','Objetivos de calidad')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Planificación y seguimiento</div>
    <h1 class="iso-title">Objetivos de calidad</h1>
    <p class="iso-subtitle">Definí metas medibles, registrá resultados durante el período y conservá las decisiones y evidencias.</p>

    <div class="iso-toolbar">
        <form method="get" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="periodo" class="form-select" onchange="this.form.submit()">
                @foreach($periodos as $item)<option value="{{ $item->id }}" @selected($periodo?->id === $item->id)>{{ $item->anio }} — {{ ucfirst($item->estado) }}</option>@endforeach
            </select>
            <select name="estado" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                @foreach(['activo'=>'Activo','cerrado'=>'Cerrado','suspendido'=>'Suspendido'] as $valor=>$texto)<option value="{{ $valor }}" @selected(request('estado')===$valor)>{{ $texto }}</option>@endforeach
            </select>
            <select name="proceso" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los procesos</option>
                @foreach($procesos as $proceso)<option value="{{ $proceso }}" @selected(request('proceso')===$proceso)>{{ $proceso }}</option>@endforeach
            </select>
            @if(request()->hasAny(['estado','proceso']))<a href="{{ route('planificacion.objetivos.index',['periodo'=>$periodo?->id]) }}" class="btn btn-link">Limpiar</a>@endif
        </form>
        @if($periodo && $periodo->estado !== 'cerrado' && auth()->user()->puedeGestionarPlanificacion())<a href="{{ route('planificacion.objetivos.create',['periodo'=>$periodo->id]) }}" class="btn btn-primary"><i class="fa-solid fa-plus me-1"></i> Nuevo objetivo</a>@endif
    </div>

    <div class="iso-panel overflow-hidden">
        <div class="table-responsive">
            <table class="table iso-table mb-0">
                <thead><tr><th>Código</th><th>Objetivo</th><th>Proceso</th><th>Indicador y meta</th><th>Resultado actual</th><th>Acciones</th><th>Estado</th><th></th></tr></thead>
                <tbody>
                @forelse($objetivos as $objetivo)
                    @php
                        $estadoClase = match($objetivo->cumplimiento_actual){'cumplido'=>'ok','aceptable'=>'warn','incumplido'=>'danger',default=>''};
                        $estadoTexto = match($objetivo->cumplimiento_actual){'cumplido'=>'Cumplido','aceptable'=>'Aceptable','incumplido'=>'Incumplido',default=>'Pendiente de medición'};
                        $abiertas = $objetivo->acciones->whereNotIn('estado',['completada','cancelada'])->count();
                    @endphp
                    <tr>
                        <td><span class="iso-code">{{ $objetivo->codigo }}</span></td>
                        <td><strong>{{ $objetivo->titulo }}</strong><small class="d-block text-muted mt-1">{{ $objetivo->area_responsable }} · {{ $objetivo->responsable?->name ?? 'Sin asignar' }}</small></td>
                        <td>{{ $objetivo->proceso }}</td>
                        <td><span>{{ $objetivo->indicadorPrincipal?->nombre }}</span><small class="d-block text-muted mt-1">Meta: {{ $objetivo->indicadorPrincipal?->meta !== null ? rtrim(rtrim(number_format((float)$objetivo->indicadorPrincipal->meta,4,',','.'),'0'),',') : 'Sin definir' }} {{ $objetivo->indicadorPrincipal?->unidad }}</small></td>
                        <td>@if($objetivo->resultado_actual !== null)<strong>{{ rtrim(rtrim(number_format($objetivo->resultado_actual,4,',','.'),'0'),',') }}</strong><small class="d-block text-muted">{{ $objetivo->indicadorPrincipal?->unidad }}</small>@else<span class="text-muted">Sin mediciones</span>@endif</td>
                        <td>{{ $abiertas }} abiertas<small class="d-block text-muted">{{ $objetivo->acciones->count() }} totales</small></td>
                        <td><span class="iso-status {{ $estadoClase }}">{{ $estadoTexto }}</span><small class="d-block text-muted mt-1">{{ ucfirst($objetivo->estado) }}</small></td>
                        <td><a href="{{ route('planificacion.objetivos.show',$objetivo) }}" class="btn btn-outline-primary btn-sm">Ver</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="iso-empty-card m-3"><i class="fa-solid fa-bullseye me-3"></i><div><strong>No hay objetivos para este período</strong><small>Creá el primero para comenzar a registrar metas y mediciones.</small></div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
