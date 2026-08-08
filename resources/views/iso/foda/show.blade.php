@extends('layouts.main')
@include('iso._styles')
@section('heading','Detalle FODA')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <a href="{{ route('planificacion.foda.index',['periodo'=>$contexto->periodo_id]) }}">← Volver al FODA</a>
    <div class="iso-toolbar"><div><div class="iso-code">{{ $contexto->codigo }}</div><h1 class="iso-title">{{ $contexto->titulo }}</h1></div><span class="iso-status {{ $contexto->decision==='pendiente'?'warn':'ok' }}">{{ str_replace('_',' ',ucfirst($contexto->decision)) }}</span></div>
    <div class="iso-panel mb-4"><div class="iso-panel-body iso-detail-grid">
        <div class="iso-field"><label>Tipo</label><div>{{ $contexto->tipo_etiqueta }}</div></div><div class="iso-field"><label>Período</label><div>{{ $contexto->periodo->anio }}</div></div>
        <div class="iso-field"><label>Descripción</label><div>{{ $contexto->descripcion }}</div></div><div class="iso-field"><label>Proceso</label><div>{{ $contexto->proceso ?: '—' }}</div></div>
        <div class="iso-field"><label>Fuente</label><div>@if($contexto->fuente_tipo)<strong>{{ $contexto->fuente_tipo }}:</strong> @endif{{ $contexto->fuente ?: '—' }}</div></div><div class="iso-field"><label>Responsable</label><div><x-user-identity :user="$contexto->responsable" /></div></div>
    </div></div>
    <div class="row">
        <div class="col-lg-6 mb-4"><div class="iso-panel h-100"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Evaluación</h2></div><div class="iso-panel-body">
        @if(auth()->user()->puedeGestionarPlanificacion() && $contexto->periodo->estado !== 'cerrado')
        <form method="POST" action="{{ route('planificacion.foda.evaluar',$contexto) }}">@csrf @method('PATCH')
            <div class="form-group"><label>¿Es relevante para el SGC? *</label><select name="relevante_sgc" class="form-control" required><option value="">Seleccionar</option><option value="1" @selected($contexto->relevante_sgc===true)>Sí</option><option value="0" @selected($contexto->relevante_sgc===false)>No</option></select></div>
            <div class="form-group"><label>Decisión *</label><select name="decision" class="form-control" required><option value="pendiente" @selected($contexto->decision==='pendiente')>Pendiente</option><option value="tratar_riesgo" @selected($contexto->decision==='tratar_riesgo')>Tratar riesgo</option><option value="aprovechar_oportunidad" @selected($contexto->decision==='aprovechar_oportunidad')>Aprovechar oportunidad</option><option value="vincular_existente" @selected($contexto->decision==='vincular_existente')>Vincular tratamiento existente</option><option value="aceptar_sin_accion" @selected($contexto->decision==='aceptar_sin_accion')>Aceptar sin acción</option><option value="no_aplicable" @selected($contexto->decision==='no_aplicable')>No aplicable</option></select></div>
            <div class="form-group"><label>Justificación</label><textarea name="justificacion" class="form-control" rows="4">{{ $contexto->justificacion }}</textarea><small class="text-muted">Obligatoria para vincular, aceptar sin acción o no aplicar.</small></div>
            <div class="form-row"><div class="form-group col-md-5"><label>Tipo de referencia</label><select name="referencia_tipo" class="form-control"><option value="">Sin referencia</option>@foreach($referencias as $referencia)<option value="{{ $referencia }}" @selected($contexto->referencia_tipo===$referencia)>{{ $referencia }}</option>@endforeach<option value="Otra" @selected($contexto->referencia_tipo==='Otra')>Otra</option></select><small class="form-text text-muted">Elegí el antecedente que respalda o ya trata esta situación.</small></div><div class="form-group col-md-7"><label>Referencia existente</label><input name="referencia_existente" class="form-control" value="{{ $contexto->referencia_existente }}" placeholder="Código, título, tarjeta o enlace"><small class="form-text text-muted">Ej.: RO-2025-003, STO-1234 o título de un documento del SGD.</small></div></div>
            <button class="btn btn-primary">Guardar evaluación</button>
        </form>
        @else <p><strong>Relevante:</strong> {{ is_null($contexto->relevante_sgc)?'Pendiente':($contexto->relevante_sgc?'Sí':'No') }}</p><p>{{ $contexto->justificacion }}</p> @endif
        </div></div></div>
        <div class="col-lg-6 mb-4"><div class="iso-panel h-100"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Riesgos y oportunidades derivados</h2>@if(auth()->user()->puedeGestionarPlanificacion() && in_array($contexto->decision,['tratar_riesgo','aprovechar_oportunidad']))<a class="btn btn-sm btn-primary" href="{{ route('planificacion.riesgos.create',['contexto'=>$contexto->id]) }}">Crear registro</a>@endif</div><div class="iso-panel-body">
            @forelse($contexto->riesgos as $r)<a class="iso-foda-item" href="{{ route('planificacion.riesgos.show',$r) }}"><span class="iso-code">{{ $r->codigo }}</span><strong class="d-block">{{ Str::limit($r->identificacion,120) }}</strong><small>Índice {{ $r->indice_inicial }} · {{ str_replace('_',' ',$r->estado) }}</small></a>@empty<p class="text-muted">Todavía no hay registros derivados.</p>@endforelse
        </div></div></div>
    </div>
</div>
@endsection
