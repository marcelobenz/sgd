@extends('layouts.main')
@include('iso._styles')
@section('heading','Planificación')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Sistema de gestión</div>
    <h1 class="iso-title">Planificación</h1>
    <p class="iso-subtitle">Analizá el contexto, administrá riesgos y oportunidades y verificá la eficacia de las acciones.</p>
    @if($periodo)
        <div class="iso-kpi-row">
            <div class="iso-kpi"><strong>{{ $stats['foda_pendientes'] }}</strong><span>FODA pendientes de decisión</span></div>
            <div class="iso-kpi"><strong>{{ $stats['riesgos_altos'] }}</strong><span>Riesgos u oportunidades altos</span></div>
            <div class="iso-kpi"><strong>{{ $stats['acciones_vencidas'] }}</strong><span>Acciones vencidas</span></div>
            <a class="iso-kpi text-reset text-decoration-none" href="{{ route('planificacion.riesgos.index',['periodo'=>$periodo->id,'verificacion_vencida'=>1,'orden'=>'verificacion','direccion'=>'asc']) }}"><strong>{{ $stats['verificaciones_pendientes'] }}</strong><span>Evaluaciones de eficacia vencidas</span></a>
        </div>
    @endif
    <div class="iso-card-grid">
        <a class="iso-menu-card" href="{{ route('planificacion.foda.index') }}"><span class="iso-icon"><i class="fa-solid fa-table-cells-large"></i></span><div><h3>FODA</h3><p>Registrá y evaluá fortalezas, debilidades, oportunidades y amenazas.</p>@if($stats['foda_pendientes'])<span class="iso-badge-count">{{ $stats['foda_pendientes'] }} pendientes</span>@endif</div></a>
        <a class="iso-menu-card" href="{{ route('planificacion.riesgos.index') }}"><span class="iso-icon"><i class="fa-solid fa-shield-halved"></i></span><div><h3>Riesgos y oportunidades</h3><p>Valorá los efectos, definí tratamientos y consultá su evolución.</p></div></a>
        <a class="iso-menu-card" href="{{ route('planificacion.acciones.index') }}"><span class="iso-icon"><i class="fa-solid fa-list-check"></i></span><div><h3>Acciones y seguimiento</h3><p>Consultá responsables, vencimientos, evidencias y verificaciones.</p>@if($stats['acciones_vencidas'])<span class="iso-badge-count">{{ $stats['acciones_vencidas'] }} vencidas</span>@endif</div></a>
        <a class="iso-menu-card" href="{{ route('planificacion.partes.index') }}"><span class="iso-icon"><i class="fa-solid fa-people-group"></i></span><div><h3>Partes interesadas</h3><p>Identificá necesidades y requisitos relevantes para el sistema de gestión.</p></div></a>
        <a class="iso-menu-card" href="{{ route('planificacion.informes.index') }}"><span class="iso-icon"><i class="fa-solid fa-file-lines"></i></span><div><h3>Informes</h3><p>Presentá contexto, riesgos, acciones y eficacia de forma clara en auditorías.</p></div></a>
        @if(auth()->user()->puedeAdministrarPlanificacion())<a class="iso-menu-card" href="{{ route('planificacion.periodos.index') }}"><span class="iso-icon"><i class="fa-solid fa-sliders"></i></span><div><h3>Períodos y parámetros</h3><p>Administrá el período vigente y la evaluación de cambio climático.</p></div></a>@endif
        @if(auth()->user()->puedeAdministrarPlanificacion())<a class="iso-menu-card" href="{{ route('planificacion.permisos.index') }}"><span class="iso-icon"><i class="fa-solid fa-user-shield"></i></span><div><h3>Permisos</h3><p>Definí quién puede consultar, gestionar o administrar la planificación.</p></div></a>@endif
    </div>
</div>
@endsection
