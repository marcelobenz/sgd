@extends('layouts.main')

@section('heading', 'Dashboard')

@section('contenidoPrincipal')
<div class="sgc-dashboard">
    <section class="dashboard-hero">
        <div>
            <span class="hero-eyebrow">Sistema de Gestión de la Calidad</span>
            <h1>{{ $periodo?->nombre ?? 'Planificación ISO' }}</h1>
            <p>
                @if($periodo)
                    Período {{ $periodo->estado }} · Tu vista reúne planificación, documentos y acciones asignadas.
                @else
                    Todavía no existe un período de planificación. Tus pendientes documentales siguen disponibles.
                @endif
            </p>
        </div>
        <div class="hero-actions">
            <a class="btn btn-light" href="{{ route('pendientes.index') }}"><i class="fa-solid fa-list-check"></i> Ver mis pendientes</a>
            @if($puedeVerIso)
                <a class="btn btn-outline-light" href="{{ route('planificacion.index') }}">Ir a planificación <i class="fa-solid fa-arrow-right"></i></a>
            @endif
        </div>
    </section>

    <section class="kpi-grid" aria-label="Resumen personal">
        <a class="kpi-card danger" href="{{ route('pendientes.index', ['prioridad' => 'vencida']) }}">
            <span class="kpi-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <span class="kpi-value">{{ $resumenPendientes['vencidas'] }}</span>
            <span class="kpi-label">Acciones vencidas</span>
            <small>Requieren atención inmediata</small>
        </a>
        <a class="kpi-card warning" href="{{ route('pendientes.index', ['prioridad' => 'proxima']) }}">
            <span class="kpi-icon"><i class="fa-solid fa-calendar-week"></i></span>
            <span class="kpi-value">{{ $resumenPendientes['proximas'] }}</span>
            <span class="kpi-label">Próximos {{ $preferencias['horizonte_dias'] }} días</span>
            <small>{{ $resumenPendientes['hoy'] }} para hoy · horizonte {{ $preferencias['horizonte_dias'] }} días</small>
        </a>
        @if($iso)
            <a class="kpi-card critical" href="{{ route('planificacion.riesgos.index', ['periodo' => $periodo->id]) }}">
                <span class="kpi-icon"><i class="fa-solid fa-shield-halved"></i></span>
                <span class="kpi-value">{{ $iso['riesgos_altos'] }}</span>
                <span class="kpi-label">Riesgos altos</span>
                <small>Abiertos en el período</small>
            </a>
            <a class="kpi-card info" href="{{ route('planificacion.objetivos.index', ['periodo' => $periodo->id]) }}">
                <span class="kpi-icon"><i class="fa-solid fa-bullseye"></i></span>
                <span class="kpi-value">{{ $iso['objetivos_activos'] }}</span>
                <span class="kpi-label">Objetivos activos</span>
                <small>{{ $iso['acciones_abiertas'] }} acciones ISO abiertas</small>
            </a>
        @else
            <a class="kpi-card info" href="{{ route('pendientes.index', ['grupo' => 'documentos']) }}">
                <span class="kpi-icon"><i class="fa-solid fa-file-circle-check"></i></span>
                <span class="kpi-value">{{ $documentosResumen['por_aprobar'] }}</span>
                <span class="kpi-label">Por aprobar</span>
                <small>Documentos que requieren tu decisión</small>
            </a>
            <a class="kpi-card neutral" href="{{ route('recordatorios.mis') }}">
                <span class="kpi-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                <span class="kpi-value">{{ $documentosResumen['revisiones'] }}</span>
                <span class="kpi-label">Revisiones</span>
                <small>{{ $documentosResumen['revisiones_vencidas'] }} vencidas</small>
            </a>
        @endif
    </section>

    <div class="dashboard-grid">
        <section class="dashboard-panel attention-panel">
            <header>
                <div><span class="section-kicker">Tu trabajo</span><h2>Requiere tu atención</h2></div>
                <a href="{{ route('pendientes.index') }}">Ver todo <i class="fa-solid fa-arrow-right"></i></a>
            </header>
            <div class="attention-list">
                @forelse($pendientes as $pendiente)
                    <a class="attention-item" href="{{ $pendiente['url'] }}">
                        <span class="attention-icon {{ $pendiente['prioridad'] }}"><i class="fa-solid {{ $pendiente['icono'] }}"></i></span>
                        <span class="attention-copy">
                            <strong>{{ $pendiente['titulo'] }}</strong>
                            <small>{{ $pendiente['origen'] }} · {{ str($pendiente['descripcion'])->limit(75) }}</small>
                        </span>
                        <span class="attention-date {{ $pendiente['prioridad'] }}">
                            @if($pendiente['fecha']) {{ $pendiente['fecha']->format('d/m') }} @else Sin fecha @endif
                        </span>
                    </a>
                @empty
                    <div class="empty-state"><i class="fa-solid fa-circle-check"></i><strong>Estás al día</strong><span>No tenés acciones pendientes asignadas.</span></div>
                @endforelse
            </div>
        </section>

        @if($iso)
            <section class="dashboard-panel chart-panel">
                <header><div><span class="section-kicker">Período {{ $periodo->anio }}</span><h2>Mapa de riesgos</h2></div></header>
                <div class="chart-container"><canvas id="riskChart" aria-label="Distribución de riesgos por nivel"></canvas></div>
                <div class="chart-legend">
                    @foreach($iso['riesgos_distribucion'] as $label => $cantidad)
                        <span><i class="legend-dot {{ strtolower($label) }}"></i>{{ $label }} <strong>{{ $cantidad }}</strong></span>
                    @endforeach
                </div>
            </section>

            <section class="dashboard-panel chart-panel">
                <header><div><span class="section-kicker">Ejecución</span><h2>Estado de las acciones</h2></div></header>
                <div class="chart-container"><canvas id="actionsChart" aria-label="Estado de acciones ISO"></canvas></div>
            </section>

            <section class="dashboard-panel iso-summary-panel">
                <header><div><span class="section-kicker">Control del SGC</span><h2>Señales del período</h2></div></header>
                <a href="{{ route('planificacion.riesgos.index', ['periodo' => $periodo->id]) }}"><span><i class="fa-solid fa-magnifying-glass-chart"></i> Verificaciones vencidas</span><strong>{{ $iso['verificaciones_pendientes'] }}</strong></a>
                <a href="{{ route('planificacion.acciones.index', ['periodo' => $periodo->id]) }}"><span><i class="fa-solid fa-bars-progress"></i> Acciones abiertas</span><strong>{{ $iso['acciones_abiertas'] }}</strong></a>
                <a href="{{ route('planificacion.objetivos.index', ['periodo' => $periodo->id]) }}"><span><i class="fa-solid fa-bullseye"></i> Objetivos activos</span><strong>{{ $iso['objetivos_activos'] }}</strong></a>
            </section>
        @endif

        <section class="dashboard-panel documents-panel {{ $iso ? 'wide' : '' }}">
            <header>
                <div><span class="section-kicker">Información documentada</span><h2>Documentos y revisiones</h2></div>
                <a href="{{ route('documentos.index') }}">Abrir documentos <i class="fa-solid fa-arrow-right"></i></a>
            </header>
            <div class="document-stats">
                <div><i class="fa-regular fa-folder-open"></i><strong>{{ $documentosResumen['accesibles'] }}</strong><span>Documentos accesibles</span></div>
                <div><i class="fa-solid fa-file-signature"></i><strong>{{ $documentosResumen['por_aprobar'] }}</strong><span>Esperan tu aprobación</span></div>
                <div><i class="fa-solid fa-clipboard-check"></i><strong>{{ $documentosResumen['revisiones'] }}</strong><span>Revisiones pendientes</span></div>
                <div class="{{ $documentosResumen['revisiones_vencidas'] ? 'has-alert' : '' }}"><i class="fa-solid fa-clock"></i><strong>{{ $documentosResumen['revisiones_vencidas'] }}</strong><span>Revisiones vencidas</span></div>
            </div>
        </section>
    </div>
</div>
@endsection

@push('styles')
<style>
    :root{--sgc-navy:#142b4a;--sgc-blue:#2563a6;--sgc-green:#2f7d62;--sgc-red:#c53b45;--sgc-amber:#c67a13;--sgc-ink:#172033;--sgc-muted:#657286;--sgc-border:#e2e8f0;--sgc-bg:#f3f6fa}
    body{background:var(--sgc-bg)}.sgc-dashboard{max-width:1500px;margin:0 auto;padding:8px 4px 30px}.dashboard-hero{background:linear-gradient(120deg,#122a49 0%,#1d4f78 62%,#2f7d62 130%);color:#fff;border-radius:18px;padding:28px 32px;display:flex;align-items:center;justify-content:space-between;gap:24px;box-shadow:0 14px 35px rgba(20,43,74,.18);position:relative;overflow:hidden}.dashboard-hero:after{content:"";position:absolute;width:300px;height:300px;border:50px solid rgba(255,255,255,.055);border-radius:50%;right:-90px;top:-135px}.hero-eyebrow,.section-kicker{text-transform:uppercase;letter-spacing:.11em;font-size:.7rem;font-weight:800}.dashboard-hero h1{font-size:2rem;margin:5px 0}.dashboard-hero p{margin:0;opacity:.78}.hero-actions{display:flex;gap:10px;z-index:1;flex-wrap:wrap}.hero-actions .btn{border-radius:9px;font-weight:700;padding:9px 14px}.hero-actions i{margin:0 4px}.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin:18px 0}.kpi-card{position:relative;background:#fff;border:1px solid var(--sgc-border);border-radius:14px;padding:20px;color:var(--sgc-ink);text-decoration:none!important;overflow:hidden;transition:.2s ease;box-shadow:0 5px 17px rgba(28,46,70,.05)}.kpi-card:before{content:"";position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--accent)}.kpi-card:hover{transform:translateY(-3px);box-shadow:0 10px 25px rgba(28,46,70,.1);color:var(--sgc-ink)}.kpi-card.danger,.kpi-card.critical{--accent:var(--sgc-red)}.kpi-card.warning{--accent:var(--sgc-amber)}.kpi-card.info{--accent:var(--sgc-blue)}.kpi-card.neutral{--accent:#738197}.kpi-icon{position:absolute;right:17px;top:17px;width:42px;height:42px;border-radius:11px;display:grid;place-items:center;color:var(--accent);background:color-mix(in srgb,var(--accent) 10%,white);font-size:1.1rem}.kpi-value{display:block;font-size:2.35rem;line-height:1;font-weight:800;color:var(--accent);margin-bottom:7px}.kpi-label{display:block;font-weight:800}.kpi-card small{color:var(--sgc-muted)}.dashboard-grid{display:grid;grid-template-columns:1.35fr 1fr;gap:18px}.dashboard-panel{background:#fff;border:1px solid var(--sgc-border);border-radius:15px;box-shadow:0 5px 17px rgba(28,46,70,.045);overflow:hidden}.dashboard-panel>header{display:flex;justify-content:space-between;align-items:center;padding:20px 22px 14px;gap:16px}.dashboard-panel h2{font-size:1.08rem;margin:2px 0 0;color:var(--sgc-ink);font-weight:800}.section-kicker{color:var(--sgc-blue)}.dashboard-panel header>a{color:var(--sgc-blue);font-size:.82rem;font-weight:700}.attention-list{padding:0 10px 12px}.attention-item{display:flex;align-items:center;gap:12px;padding:11px 12px;border-radius:10px;text-decoration:none!important;color:var(--sgc-ink);border-top:1px solid #edf1f5}.attention-item:hover{background:#f6f9fc;color:var(--sgc-ink)}.attention-icon{flex:0 0 38px;height:38px;border-radius:10px;display:grid;place-items:center;background:#edf3f9;color:var(--sgc-blue)}.attention-icon.vencida{background:#fcebed;color:var(--sgc-red)}.attention-icon.hoy,.attention-icon.proxima{background:#fff4df;color:var(--sgc-amber)}.attention-copy{display:flex;flex-direction:column;min-width:0;flex:1}.attention-copy strong{font-size:.9rem;white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.attention-copy small{color:var(--sgc-muted);white-space:nowrap;text-overflow:ellipsis;overflow:hidden}.attention-date{font-size:.76rem;font-weight:800;color:var(--sgc-muted)}.attention-date.vencida{color:var(--sgc-red)}.attention-date.hoy,.attention-date.proxima{color:var(--sgc-amber)}.empty-state{padding:40px;display:flex;flex-direction:column;text-align:center;color:var(--sgc-muted);gap:5px}.empty-state i{font-size:2rem;color:var(--sgc-green);margin-bottom:5px}.chart-container{height:205px;padding:4px 20px 12px}.chart-legend{display:flex;justify-content:center;gap:18px;padding:0 15px 17px;font-size:.78rem;color:var(--sgc-muted)}.legend-dot{display:inline-block;width:8px;height:8px;border-radius:50%;margin-right:5px}.legend-dot.bajo{background:#3c9975}.legend-dot.medio{background:#e5a02d}.legend-dot.alto{background:#d24b55}.iso-summary-panel>a{display:flex;justify-content:space-between;align-items:center;padding:16px 22px;border-top:1px solid #edf1f5;color:var(--sgc-ink);text-decoration:none}.iso-summary-panel>a:hover{background:#f7f9fc}.iso-summary-panel a i{width:24px;color:var(--sgc-blue)}.iso-summary-panel a strong{font-size:1.25rem;color:var(--sgc-navy)}.documents-panel.wide{grid-column:1/-1}.document-stats{display:grid;grid-template-columns:repeat(4,1fr);border-top:1px solid #edf1f5}.document-stats>div{display:grid;grid-template-columns:36px auto;grid-template-rows:auto auto;column-gap:10px;padding:20px;border-right:1px solid #edf1f5}.document-stats>div:last-child{border:0}.document-stats i{grid-row:1/3;font-size:1.25rem;color:var(--sgc-blue);align-self:center}.document-stats strong{font-size:1.45rem;line-height:1;color:var(--sgc-ink)}.document-stats span{font-size:.76rem;color:var(--sgc-muted)}.document-stats .has-alert i,.document-stats .has-alert strong{color:var(--sgc-red)}
    @media(max-width:991px){.kpi-grid{grid-template-columns:repeat(2,1fr)}.dashboard-grid{grid-template-columns:1fr}.documents-panel.wide{grid-column:auto}.dashboard-hero{align-items:flex-start;flex-direction:column}.document-stats{grid-template-columns:repeat(2,1fr)}}@media(max-width:575px){.kpi-grid{grid-template-columns:1fr 1fr;gap:10px}.kpi-card{padding:17px 13px}.kpi-icon{display:none}.kpi-value{font-size:1.9rem}.dashboard-hero{padding:23px 20px}.dashboard-hero h1{font-size:1.55rem}.document-stats{grid-template-columns:1fr}.hero-actions{width:100%}.hero-actions .btn{flex:1}.attention-date{display:none}}
</style>
@endpush

@if($iso)
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const defaults={responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false},ticks:{color:'#657286'}},y:{grid:{color:'#edf1f5'},ticks:{precision:0,color:'#657286'}}}};
    new Chart(document.getElementById('riskChart'),{type:'bar',data:{labels:@json(array_keys($iso['riesgos_distribucion'])),datasets:[{data:@json(array_values($iso['riesgos_distribucion'])),backgroundColor:['#3c9975','#e5a02d','#d24b55'],borderRadius:7,barThickness:34}]},options:defaults});
    new Chart(document.getElementById('actionsChart'),{type:'doughnut',data:{labels:@json(array_keys($iso['acciones_distribucion'])),datasets:[{data:@json(array_values($iso['acciones_distribucion'])),backgroundColor:['#d24b55','#e5a02d','#3c9975'],borderWidth:4,borderColor:'#fff'}]},options:{responsive:true,maintainAspectRatio:false,cutout:'68%',plugins:{legend:{position:'bottom',labels:{usePointStyle:true,boxWidth:8,color:'#657286'}}}}});
});
</script>
@endpush
@endif
