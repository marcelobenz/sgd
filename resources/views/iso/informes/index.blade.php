@extends('layouts.main')
@include('iso._styles')
@section('heading','Informe de planificacion')
@section('contenidoPrincipal')
<style>
    .report-summary-grid{display:grid;grid-template-columns:repeat(6,minmax(125px,1fr));gap:12px}.report-kpi{padding:14px;border:1px solid #dfe4ea;border-radius:10px;background:#f8fafc}.report-kpi strong{display:block;font-size:1.45rem;color:#0b2f5b}.report-kpi small{color:#667085}.report-alert{border-left:4px solid #d99b16;background:#fff8e5;padding:12px 16px}.report-ok{border-left-color:#2f9e62;background:#eafaf1}.report-meta{display:flex;flex-wrap:wrap;gap:12px 24px}.report-detail-card{border:1px solid #dfe4ea;border-radius:9px;padding:14px;background:#fbfcfe}.report-detail-card+.report-detail-card{margin-top:12px}.report-label{font-size:.73rem;text-transform:uppercase;color:#667085;font-weight:700;letter-spacing:.02em}.report-status{display:inline-block;border-radius:999px;padding:3px 9px;font-weight:700;font-size:.76rem;background:#eef2f6}.report-status.ok{background:#d9f7e5;color:#176b3a}.report-status.warn{background:#fff0c2;color:#765500}.report-status.bad{background:#fde0de;color:#9c2420}
    @media(max-width:1100px){.report-summary-grid{grid-template-columns:repeat(3,1fr)}}
    @media print{@page{size:landscape;margin:10mm}.report-summary-grid{grid-template-columns:repeat(6,1fr)}.iso-panel,.report-detail-card{break-inside:avoid}.iso-panel-header{break-after:avoid}.iso-table thead{display:table-header-group}.iso-table tr{break-inside:avoid}.report-page-break{break-before:page}.report-source-note{display:block!important}}
</style>
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-toolbar no-print align-items-end">
        <form method="GET" class="d-flex align-items-end flex-wrap gap-2">
            <div><label class="form-label">Per&iacute;odo</label><select name="periodo" class="form-control">@foreach($periodos as $p)<option value="{{ $p->id }}" @selected($periodo->id===$p->id)>{{ $p->anio }} &mdash; {{ ucfirst($p->estado) }}</option>@endforeach</select></div>
            <div><label class="form-label">Tipo de informe</label><select name="tipo" class="form-control"><option value="resumido" @selected($tipo==='resumido')>Resumido</option><option value="detallado" @selected($tipo==='detallado')>Detallado</option></select></div>
            <button class="btn btn-outline-primary">Generar informe</button>
        </form>
        <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print mr-1"></i> Imprimir / guardar PDF</button>
    </div>

    <div class="iso-eyebrow">Informe para auditor&iacute;a &middot; {{ ucfirst($tipo) }}</div>
    <h1 class="iso-title">Planificaci&oacute;n y seguimiento del SGC &mdash; {{ $periodo->anio }}</h1>
    <div class="report-meta iso-subtitle">
        <span>Generado {{ now()->format('d/m/Y H:i') }}</span><span>Por {{ auth()->user()->name }}</span><span>Estado del per&iacute;odo: <strong>{{ ucfirst($periodo->estado) }}</strong></span>
        @if($periodo->cerrado_en)<span>Cierre: {{ $periodo->cerrado_en->format('d/m/Y H:i') }} &middot; {{ $periodo->cerradoPor?->name ?? 'Usuario no disponible' }}</span>@endif
    </div>
    <p class="text-muted report-source-note">Este informe refleja la informaci&oacute;n registrada en el SGD al momento de su generaci&oacute;n.</p>

    @include('iso.informes._resumen_ejecutivo')
    @include($tipo==='detallado' ? 'iso.informes._detallado' : 'iso.informes._resumido')
</div>
@endsection
