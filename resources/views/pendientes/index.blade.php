@extends('layouts.main')

@section('heading', 'Mis pendientes')

@section('contenidoPrincipal')
<div class="inbox-page {{ $preferencias['densidad']==='compacta' ? 'density-compact' : '' }}">
    <header class="inbox-header">
        <div class="inbox-identity"><x-user-avatar :user="auth()->user()" :size="58" /><div><span>Centro de trabajo personal</span><h1>Mis pendientes</h1><p>Acciones que requieren tu intervención{{ $periodo ? ' en '.$periodo->nombre : '' }} · horizonte de {{ $preferencias['horizonte_dias'] }} días.</p></div></div>
        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary"><i class="fa-solid fa-chart-line"></i> Volver al dashboard</a>
    </header>

    <section class="inbox-counters">
        <a href="{{ route('pendientes.index') }}"><strong>{{ $resumen['total'] }}</strong><span>Total</span></a>
        <a class="danger" href="{{ route('pendientes.index', ['prioridad'=>'vencida']) }}"><strong>{{ $resumen['vencidas'] }}</strong><span>Vencidas</span></a>
        <a class="warning" href="{{ route('pendientes.index', ['prioridad'=>'hoy']) }}"><strong>{{ $resumen['hoy'] }}</strong><span>Para hoy</span></a>
        <a class="info" href="{{ route('pendientes.index', ['prioridad'=>'proxima']) }}"><strong>{{ $resumen['proximas'] }}</strong><span>Próximos {{ $preferencias['horizonte_dias'] }} días</span></a>
    </section>

    <form class="inbox-filters" method="get">
        <div><label for="grupo">Origen</label><select id="grupo" name="grupo" class="form-control"><option value="">Todos</option><option value="iso" @selected(request('grupo')==='iso')>Planificación ISO</option><option value="documentos" @selected(request('grupo')==='documentos')>Documentos</option><option value="vacaciones" @selected(request('grupo')==='vacaciones')>Vacaciones</option></select></div>
        <div><label for="prioridad">Vencimiento</label><select id="prioridad" name="prioridad" class="form-control"><option value="">Todos</option><option value="vencida" @selected(request('prioridad')==='vencida')>Vencidas</option><option value="hoy" @selected(request('prioridad')==='hoy')>Hoy</option><option value="proxima" @selected(request('prioridad')==='proxima')>Próximos {{ $preferencias['horizonte_dias'] }} días</option><option value="futura" @selected(request('prioridad')==='futura')>Posteriores</option><option value="sin_fecha" @selected(request('prioridad')==='sin_fecha')>Sin fecha</option></select></div>
        <div><label for="estado">Estado</label><select id="estado" name="estado" class="form-control"><option value="">Todos</option><option value="pendiente" @selected(request('estado')==='pendiente')>Pendiente</option><option value="en-proceso" @selected(request('estado')==='en-proceso')>En proceso</option><option value="postergada" @selected(request('estado')==='postergada')>Postergada</option></select></div>
        <button class="btn btn-primary"><i class="fa-solid fa-filter"></i> Aplicar</button>
        @if(request()->hasAny(['grupo','prioridad','estado']))<a class="clear-filter" href="{{ route('pendientes.index') }}">Limpiar</a>@endif
    </form>

    <section class="inbox-list">
        <div class="inbox-list-head"><span>Acción</span><span>Origen</span><span>Vencimiento</span><span>Estado</span><span></span></div>
        @forelse($pendientes as $pendiente)
            <article class="inbox-row">
                <div class="task-main"><span class="task-icon {{ $pendiente['prioridad'] }}"><i class="fa-solid {{ $pendiente['icono'] }}"></i></span><div><strong>{{ $pendiente['titulo'] }}</strong><small>{{ $pendiente['descripcion'] }}</small></div></div>
                <div class="task-origin"><span class="origin-pill {{ $pendiente['grupo'] }}">{{ $pendiente['grupo']==='iso'?'ISO':($pendiente['grupo']==='vacaciones'?'Vacaciones':'Documentos') }}</span><small>{{ $pendiente['origen'] }}</small></div>
                <div class="task-date {{ $pendiente['prioridad'] }}">@if($pendiente['fecha'])<strong>{{ $pendiente['fecha']->format('d/m/Y') }}</strong><small>{{ ['vencida'=>'Vencida','hoy'=>'Hoy','proxima'=>'Próxima','futura'=>'Programada'][$pendiente['prioridad']] ?? '' }}</small>@else<strong>Sin fecha</strong><small>Requiere decisión</small>@endif</div>
                <div><span class="status-pill">{{ $pendiente['estado'] }}</span></div>
                <a class="task-open" href="{{ $pendiente['url'] }}" title="Abrir acción" aria-label="Abrir {{ $pendiente['titulo'] }}"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            </article>
        @empty
            <div class="inbox-empty"><i class="fa-solid fa-circle-check"></i><h2>No hay pendientes para estos filtros</h2><p>Probá ampliar los filtros o volvé al dashboard.</p></div>
        @endforelse
    </section>
</div>
@endsection

@push('styles')
<style>
body{background:#f3f6fa}.inbox-page{max-width:1450px;margin:0 auto;padding:8px 4px 32px;color:#172033}.inbox-header{display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:18px}.inbox-header>div>span{text-transform:uppercase;color:#2563a6;letter-spacing:.11em;font-size:.7rem;font-weight:800}.inbox-header h1{font-size:2rem;font-weight:800;margin:3px 0}.inbox-header p{color:#657286;margin:0}.inbox-counters{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:16px}.inbox-counters a{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px 20px;text-decoration:none;color:#172033;box-shadow:0 4px 14px rgba(28,46,70,.04);border-bottom:4px solid #738197}.inbox-counters a.danger{border-bottom-color:#c53b45}.inbox-counters a.warning{border-bottom-color:#c67a13}.inbox-counters a.info{border-bottom-color:#2563a6}.inbox-counters strong{font-size:1.8rem;display:block;line-height:1}.inbox-counters span{font-size:.78rem;color:#657286}.inbox-filters{display:flex;align-items:flex-end;gap:12px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:15px 18px;margin-bottom:16px}.inbox-filters>div{min-width:180px}.inbox-filters label{display:block;font-size:.72rem;font-weight:800;color:#657286;text-transform:uppercase}.inbox-filters .btn{height:38px}.clear-filter{align-self:center;margin-top:20px;font-size:.8rem}.inbox-list{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;box-shadow:0 5px 17px rgba(28,46,70,.045)}.inbox-list-head,.inbox-row{display:grid;grid-template-columns:minmax(320px,2.2fr) minmax(175px,1fr) 135px 115px 35px;gap:18px;align-items:center}.inbox-list-head{padding:11px 20px;background:#f7f9fc;text-transform:uppercase;letter-spacing:.07em;font-size:.66rem;font-weight:800;color:#718096}.inbox-row{padding:15px 20px;border-top:1px solid #edf1f5}.inbox-row:hover{background:#fbfcfe}.task-main{display:flex;align-items:center;gap:13px;min-width:0}.task-main>div,.task-origin,.task-date{display:flex;flex-direction:column;min-width:0}.task-main strong{font-size:.9rem;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.task-main small,.task-origin small,.task-date small{color:#718096;font-size:.75rem;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.task-icon{flex:0 0 40px;height:40px;display:grid;place-items:center;border-radius:11px;background:#edf3f9;color:#2563a6}.task-icon.vencida{background:#fcebed;color:#c53b45}.task-icon.hoy,.task-icon.proxima{background:#fff4df;color:#c67a13}.origin-pill,.status-pill{align-self:flex-start;border-radius:20px;padding:3px 8px;font-size:.68rem;font-weight:800;background:#eef2f6;color:#526174}.origin-pill.iso{background:#e8f1fb;color:#205b95}.origin-pill.documentos{background:#e9f6f0;color:#277157}.task-date strong{font-size:.84rem}.task-date.vencida strong{color:#c53b45}.task-date.hoy strong,.task-date.proxima strong{color:#b66c0b}.task-open{width:34px;height:34px;border-radius:9px;display:grid;place-items:center;color:#2563a6;background:#edf3f9;text-decoration:none!important}.task-open:hover{background:#2563a6;color:#fff}.inbox-empty{text-align:center;padding:65px;color:#657286}.inbox-empty i{font-size:2.5rem;color:#2f7d62}.inbox-empty h2{font-size:1.1rem;color:#172033;margin:12px 0 4px}
.inbox-identity{display:flex;align-items:center;gap:14px}.inbox-identity>div>span{text-transform:uppercase;color:#2563a6;letter-spacing:.11em;font-size:.7rem;font-weight:800}.density-compact .inbox-row{padding-top:9px;padding-bottom:9px}.density-compact .task-icon{width:34px;flex-basis:34px;height:34px}.density-compact .inbox-counters a{padding-top:11px;padding-bottom:11px}
@media(max-width:991px){.inbox-list-head{display:none}.inbox-row{grid-template-columns:1fr auto;gap:10px}.task-origin,.task-date,.inbox-row>div:nth-child(4){grid-column:1}.task-open{grid-column:2;grid-row:1}.inbox-filters{flex-wrap:wrap}.inbox-filters>div{flex:1}.inbox-counters{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.inbox-header{align-items:flex-start;flex-direction:column}.inbox-counters{gap:8px}.inbox-counters a{padding:13px}.inbox-filters>div{min-width:100%}.inbox-filters .btn{flex:1}.inbox-row{padding:14px}.task-main strong{white-space:normal}}
</style>
@endpush
