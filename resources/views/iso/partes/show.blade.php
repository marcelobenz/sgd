@extends('layouts.main')
@include('iso._styles')
@section('heading','Parte interesada')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <a href="{{ route('planificacion.partes.index') }}">← Volver a partes interesadas</a>
    <div class="iso-toolbar mt-3"><div><div class="iso-eyebrow">Ficha permanente</div><h1 class="iso-title">{{ $parte->nombre }}</h1></div><span class="iso-status {{ $parte->estado==='activa'?'ok':'' }}">{{ ucfirst($parte->estado) }}</span></div>

    <div class="row">
        <div class="col-lg-5 mb-4"><div class="iso-panel h-100"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Identificación y seguimiento</h2></div><div class="iso-panel-body">
            @php($areaPersonalizada=$parte->area_responsable && !$areas->contains($parte->area_responsable))
            @if(auth()->user()->puedeGestionarPlanificacion())
            <form method="POST" action="{{ route('planificacion.partes.update',$parte) }}">@csrf @method('PATCH')
                <div class="form-group"><label>Nombre *</label><input name="nombre" class="form-control" value="{{ $parte->nombre }}" required></div>
                <div class="form-row"><div class="form-group col-md-6"><label>Pertinente para el SGC</label><select name="pertinente_sgc" class="form-control"><option value="1" @selected($parte->pertinente_sgc)>Sí</option><option value="0" @selected(!$parte->pertinente_sgc)>No</option></select></div><div class="form-group col-md-6"><label>Estado</label><select name="estado" class="form-control"><option value="activa" @selected($parte->estado==='activa')>Activa</option><option value="archivada" @selected($parte->estado==='archivada')>Archivada</option></select></div></div>
                <div class="form-group"><label>Necesidades y requisitos *</label><textarea name="necesidades_requisitos" class="form-control" rows="4" required>{{ $parte->necesidades_requisitos }}</textarea></div>
                <div class="form-group"><label>Área responsable *</label><select name="area_responsable" class="form-control" required data-other-target="editarParteAreaOtro"><option value="">Seleccionar</option>@foreach($areas as $area)<option value="{{ $area }}" @selected($parte->area_responsable===$area)>{{ $area }}</option>@endforeach<option value="__otro__" @selected($areaPersonalizada)>Otra</option></select><div id="editarParteAreaOtro" class="mt-2 d-none"><input name="area_responsable_otro" class="form-control" value="{{ $areaPersonalizada?$parte->area_responsable:'' }}"></div></div>
                <div class="form-group"><label>Método de medición *</label><textarea name="metodo_medicion" class="form-control" rows="3" required>{{ $parte->metodo_medicion }}</textarea></div>
                <div class="form-row"><div class="form-group col-md-5"><label>Requisitos climáticos</label><select name="requisitos_climaticos" class="form-control"><option value="0" @selected($parte->requisitos_climaticos===false)>No identificados</option><option value="1" @selected($parte->requisitos_climaticos===true)>Sí</option></select></div><div class="form-group col-md-7"><label>Detalle o fundamento</label><textarea name="detalle_climatico" class="form-control" rows="3">{{ $parte->detalle_climatico }}</textarea></div></div>
                <button class="btn btn-primary">Guardar ficha</button>
            </form>
            @else
                <p><strong>Necesidades/requisitos:</strong><br>{{ $parte->necesidades_requisitos }}</p><p><strong>Área responsable:</strong> {{ $parte->area_responsable ?: 'Pendiente de definir' }}</p><p><strong>Método:</strong><br>{{ $parte->metodo_medicion }}</p>
            @endif
        </div></div></div>

        <div class="col-lg-7 mb-4">
            @if(auth()->user()->puedeGestionarPlanificacion() && $parte->estado==='activa')
            <div class="iso-panel mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Nueva evaluación periódica</h2></div><form method="POST" action="{{ route('planificacion.partes.evaluaciones.store',$parte) }}" class="iso-panel-body">@csrf
                <div class="form-row"><div class="form-group col-md-4"><label>Fecha *</label><input type="date" name="fecha_evaluacion" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="form-group col-md-4"><label>Resultado *</label><select name="resultado" class="form-control" required><option value="cumplido">Cumplido</option><option value="parcial">Parcialmente cumplido</option><option value="no_cumplido">No cumplido</option><option value="no_evaluado">No evaluado</option></select></div><div class="form-group col-md-4"><label>Período relacionado</label><select name="periodo_id" class="form-control"><option value="">Sin período</option>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected($periodo->estado==='vigente')>{{ $periodo->anio }}</option>@endforeach</select></div></div>
                <div class="form-group"><label>Resultado y observaciones *</label><textarea name="observaciones" class="form-control" rows="4" required placeholder="Qué se verificó, resultado obtenido y conclusión"></textarea></div>
                <div class="form-row"><div class="form-group col-md-4"><label>Próxima revisión</label><input type="date" name="proxima_revision" class="form-control"></div><div class="form-group col-md-8"><label>Riesgos u oportunidades vinculados</label><select name="riesgos[]" class="form-control" multiple size="4">@foreach($riesgos as $riesgo)<option value="{{ $riesgo->id }}">{{ $riesgo->codigo }} — {{ Str::limit($riesgo->identificacion,80) }}</option>@endforeach</select><small class="form-text text-muted">Vínculo opcional. Seleccioná sólo si la evaluación genera o afecta un riesgo u oportunidad.</small></div></div>
                <div class="form-row"><div class="form-group col-md-6"><label>Evidencia documental</label><select name="documento_id" class="form-control"><option value="">Sin documento</option>@foreach($documentos as $documento)<option value="{{ $documento->id }}">{{ $documento->titulo }}</option>@endforeach</select></div><div class="form-group col-md-6"><label>Enlace externo</label><input type="url" name="enlace_externo" class="form-control" placeholder="https://..."></div></div>
                <button class="btn btn-primary">Registrar evaluación</button>
            </form></div>
            @endif

            <div class="iso-panel"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Historial de evaluaciones</h2></div><div class="iso-panel-body">
                @forelse($parte->evaluaciones as $evaluacion)
                    <div class="border rounded p-3 mb-3"><div class="d-flex justify-content-between"><strong>{{ $evaluacion->fecha_evaluacion->format('d/m/Y') }}</strong><span class="iso-status {{ $evaluacion->resultado==='cumplido'?'ok':($evaluacion->resultado==='no_cumplido'?'warn':'') }}">{{ ucfirst(str_replace('_',' ',$evaluacion->resultado)) }}</span></div><small class="text-muted">{{ $evaluacion->evaluador?->name }}@if($evaluacion->periodo) · Período {{ $evaluacion->periodo->anio }}@endif</small><p class="mt-2 mb-2">{{ $evaluacion->observaciones }}</p>@if($evaluacion->proxima_revision)<div><strong>Próxima revisión:</strong> {{ $evaluacion->proxima_revision->format('d/m/Y') }}</div>@endif @if($evaluacion->documento)<div>@if($documentosAccesibles->contains($evaluacion->documento_id))<a href="{{ route('documentos.validaPermiso',['id'=>$evaluacion->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">Documento SGD: {{ $evaluacion->documento->titulo }}</a>@else<span class="text-muted">Documento interno vinculado — sin permiso de acceso</span>@endif</div>@endif @if($evaluacion->enlace_externo)<div><a href="{{ $evaluacion->enlace_externo }}" target="_blank" rel="noopener">Evidencia externa</a></div>@endif @if($evaluacion->riesgos->isNotEmpty())<div class="mt-2"><strong>Vínculos:</strong> @foreach($evaluacion->riesgos as $riesgo)<a class="ml-1" href="{{ route('planificacion.riesgos.show',$riesgo) }}">{{ $riesgo->codigo }}</a>@endforeach</div>@endif</div>
                @empty<p class="text-muted mb-0">Todavía no hay evaluaciones periódicas.</p>@endforelse
            </div></div>
        </div>
    </div>
</div>
@include('iso._guided_fields')
@endsection
