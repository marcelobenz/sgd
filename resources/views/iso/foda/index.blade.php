@extends('layouts.main')
@include('iso._styles')
@section('heading','FODA')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Contexto de la organización</div>
    <h1 class="iso-title">FODA {{ $periodo?->anio }}</h1>
    <p class="iso-subtitle">Cada elemento debe evaluarse. Solo los que lo requieran generan un riesgo u oportunidad.</p>
    <div class="iso-toolbar">
        <form method="GET" class="form-inline"><label class="mr-2">Período</label><select name="periodo" class="form-control" onchange="this.form.submit()">@foreach($periodos as $p)<option value="{{ $p->id }}" @selected($periodo?->id===$p->id)>{{ $p->anio }} — {{ ucfirst($p->estado) }}</option>@endforeach</select></form>
        @if($periodo && $periodo->estado !== 'cerrado' && auth()->user()->puedeGestionarPlanificacion())<button class="btn btn-primary" data-toggle="modal" data-target="#nuevoFoda"><i class="fa-solid fa-plus mr-1"></i> Nuevo elemento</button>@endif
    </div>
    @if(!$periodo)
        <div class="alert alert-info">Primero debe crearse un período desde Períodos y parámetros.</div>
    @else
    <div class="iso-foda-grid">
        @foreach(['fortaleza'=>'Fortalezas','debilidad'=>'Debilidades','oportunidad'=>'Oportunidades','amenaza'=>'Amenazas'] as $tipo=>$titulo)
        <section class="iso-foda-column"><div class="d-flex justify-content-between"><h2 class="iso-section-title mb-0">{{ $titulo }}</h2><span class="iso-status">{{ $contextos->where('tipo',$tipo)->count() }}</span></div>
            @forelse($contextos->where('tipo',$tipo) as $item)
                <a class="iso-foda-item" href="{{ route('planificacion.foda.show',$item) }}">
                    <div class="d-flex justify-content-between"><span class="iso-code">{{ $item->codigo }}</span><span class="iso-status {{ $item->decision==='pendiente'?'warn':'ok' }}">{{ $item->decision==='pendiente'?'Pendiente':'Evaluado' }}</span></div>
                    <strong class="d-block mt-2">{{ $item->titulo }}</strong><small class="text-muted">{{ Str::limit($item->descripcion,150) }}</small>
                </a>
            @empty <p class="text-muted mt-3 mb-0">Sin elementos.</p> @endforelse
        </section>
        @endforeach
    </div>
    @endif
</div>

@if($periodo)
<div class="modal fade" id="nuevoFoda" tabindex="-1"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('planificacion.foda.store') }}" class="modal-content">@csrf
    <div class="modal-header"><h5 class="modal-title">Nuevo elemento FODA</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body"><input type="hidden" name="periodo_id" value="{{ $periodo->id }}"><div class="form-row">
        <div class="form-group col-md-4"><label>Tipo *</label><select name="tipo" class="form-control" required><option value="fortaleza">Fortaleza</option><option value="debilidad">Debilidad</option><option value="oportunidad">Oportunidad</option><option value="amenaza">Amenaza</option></select></div>
        <div class="form-group col-md-8"><label>Título *</label><input name="titulo" class="form-control" required maxlength="255"></div></div>
        <div class="form-group"><label>Descripción *</label><textarea name="descripcion" class="form-control" rows="4" required placeholder="Explicá concretamente qué situación se observó y por qué es relevante"></textarea><small class="form-text text-muted">Describí el hecho o condición. Evitá redactar aquí la acción que debería realizarse.</small></div>
        <div class="form-row"><div class="form-group col-md-5"><label>Proceso relacionado</label><select name="proceso" class="form-control" data-other-target="fodaProcesoOtro"><option value="">Sin definir</option>@foreach($procesos as $proceso)<option value="{{ $proceso }}">{{ $proceso }}</option>@endforeach<option value="__otro__">Otro</option></select><div id="fodaProcesoOtro" class="mt-2 d-none"><input name="proceso_otro" class="form-control" placeholder="Escribí el nombre del proceso"></div><small class="form-text text-muted">Proceso que origina la situación o podría verse afectado.</small></div><div class="form-group col-md-4"><label>Responsable de evaluación</label><select name="responsable_id" class="form-control"><option value="">Sin asignar</option>@foreach($usuarios as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div><div class="form-group col-md-3"><label>Fecha *</label><input type="date" name="fecha_identificacion" value="{{ now()->toDateString() }}" class="form-control" required></div></div>
        <div class="form-row"><div class="form-group col-md-4"><label>Origen de la identificación</label><select name="fuente_tipo" class="form-control"><option value="">Seleccionar</option>@foreach($fuentes as $fuente)<option value="{{ $fuente }}">{{ $fuente }}</option>@endforeach<option value="Otro">Otro</option></select><small class="form-text text-muted">Indicá de qué actividad o información surgió.</small></div><div class="form-group col-md-8"><label>Fuente o fundamento</label><textarea name="fuente" class="form-control" rows="2" placeholder="Ej.: Tendencia observada en lanzamientos tecnológicos y consultas recibidas de clientes"></textarea><small class="form-text text-muted">Registrá el dato, hecho o evidencia concreta que sostiene el elemento.</small></div></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar y evaluar después</button></div>
</form></div></div>
@endif
@include('iso._guided_fields')
@endsection
