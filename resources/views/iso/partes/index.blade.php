@extends('layouts.main')
@include('iso._styles')
@section('heading','Partes interesadas')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Contexto y requisitos</div>
    <h1 class="iso-title">Partes interesadas</h1>
    <p class="iso-subtitle">Cada parte se registra una sola vez. Sus resultados se incorporan mediante evaluaciones periódicas, conservando el historial.</p>
    <div class="iso-toolbar">
        <form method="GET" class="form-inline"><label class="mr-2">Mostrar</label><select name="estado" class="form-control" onchange="this.form.submit()"><option value="activa" @selected(request('estado','activa')==='activa')>Activas</option><option value="archivada" @selected(request('estado')==='archivada')>Archivadas</option><option value="todas" @selected(request('estado')==='todas')>Todas</option></select></form>
        @if(auth()->user()->puedeGestionarPlanificacion())<button class="btn btn-primary" data-toggle="modal" data-target="#nuevaParte"><i class="fa-solid fa-plus mr-1"></i>Nueva parte interesada</button>@endif
    </div>
    <div class="iso-panel"><div class="table-responsive"><table class="table iso-table mb-0"><thead><tr><th>Parte interesada</th><th>Necesidades / requisitos</th><th>Área responsable</th><th>Método</th><th>Última evaluación</th><th></th></tr></thead><tbody>
        @forelse($partes as $parte)
            <tr><td><strong>{{ $parte->nombre }}</strong><div><span class="iso-status {{ $parte->pertinente_sgc?'ok':'' }}">{{ $parte->pertinente_sgc?'Pertinente':'No pertinente' }}</span> @if($parte->estado==='archivada')<span class="iso-status">Archivada</span>@endif</div></td><td>{{ Str::limit($parte->necesidades_requisitos,180) }}</td><td>{{ $parte->area_responsable ?: 'Pendiente de definir' }}</td><td>{{ Str::limit($parte->metodo_medicion,100) }}</td><td>@if($parte->ultimaEvaluacion)<strong>{{ $parte->ultimaEvaluacion->fecha_evaluacion->format('d/m/Y') }}</strong><div>{{ ucfirst(str_replace('_',' ',$parte->ultimaEvaluacion->resultado)) }}</div>@else<span class="text-muted">Sin evaluar</span>@endif</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('planificacion.partes.show',$parte) }}">Ver ficha</a></td></tr>
        @empty<tr><td colspan="6" class="text-center text-muted py-4">Sin partes interesadas registradas.</td></tr>@endforelse
    </tbody></table></div></div>
</div>

<div class="modal fade" id="nuevaParte"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('planificacion.partes.store') }}" class="modal-content">@csrf
    <div class="modal-header"><div><h5 class="modal-title">Nueva parte interesada</h5><small>Esta ficha será permanente; las evaluaciones se cargarán después.</small></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body">
        <div class="form-row"><div class="form-group col-md-8"><label>Parte interesada *</label><select name="nombre" class="form-control" required data-other-target="parteNombreOtro"><option value="">Seleccionar</option>@foreach($partesCatalogo as $opcion)<option value="{{ $opcion }}">{{ $opcion }}</option>@endforeach<option value="__otro__">Otra</option></select><div id="parteNombreOtro" class="mt-2 d-none"><input name="nombre_otro" class="form-control" placeholder="Escribí la parte interesada"></div></div><div class="form-group col-md-4"><label>¿Pertinente para el SGC? *</label><select name="pertinente_sgc" class="form-control" required><option value="1">Sí</option><option value="0">No</option></select></div></div>
        <div class="form-group"><label>Necesidades y requisitos *</label><textarea name="necesidades_requisitos" class="form-control" rows="4" required placeholder="Qué espera, necesita o exige esta parte interesada"></textarea></div>
        <div class="form-group"><label>Área responsable *</label><select name="area_responsable" class="form-control" required data-other-target="parteAreaOtro"><option value="">Seleccionar</option>@foreach($areas as $area)<option value="{{ $area }}">{{ $area }}</option>@endforeach<option value="__otro__">Otra</option></select><div id="parteAreaOtro" class="mt-2 d-none"><input name="area_responsable_otro" class="form-control" placeholder="Escribí el área o grupo responsable"></div><small class="form-text text-muted">Área, función o grupo que administra la relación; no una persona individual.</small></div>
        <div class="form-group"><label>Método de medición o seguimiento *</label><textarea name="metodo_medicion" class="form-control" rows="3" required placeholder="Ej.: encuesta anual, reuniones, evaluación técnica trimestral o indicador de pagos"></textarea><small class="form-text text-muted">Definí cómo se conocerá si sus necesidades y requisitos se mantienen atendidos.</small></div>
        <div class="form-row"><div class="form-group col-md-4"><label>¿Tiene requisitos climáticos? *</label><select name="requisitos_climaticos" class="form-control" required><option value="0">No identificados</option><option value="1">Sí</option></select></div><div class="form-group col-md-8"><label>Detalle o fundamento climático</label><textarea name="detalle_climatico" class="form-control" rows="2" placeholder="Requisito identificado o fundamento de la evaluación"></textarea></div></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Crear ficha</button></div>
</form></div></div>
@include('iso._guided_fields')
@endsection
