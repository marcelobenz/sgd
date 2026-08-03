@extends('layouts.main')
@include('iso._styles')
@section('heading','Detalle de riesgo u oportunidad')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    @php
        $evaluacionCierre = $riesgo->estado === 'finalizado'
            ? $riesgo->verificaciones->where('estado_resultante', 'finalizado')->sortByDesc('id')->first()
            : null;
        $evaluacionResumen = $evaluacionCierre
            ?? $riesgo->verificaciones->sortByDesc('id')->first();
        $resultadoCierre = match($evaluacionCierre?->eficacia ?? $riesgo->eficacia) {
            'si' => ['Eficaz', 'ok'],
            'parcial' => ['Parcialmente eficaz', 'warn'],
            'no' => ['No eficaz', 'danger'],
            default => ['Resultado no registrado', ''],
        };
        $evaluacionConErrores = $errors->hasAny(['fecha', 'eficacia', 'conclusion', 'criterio_eficacia', 'impacto_final', 'probabilidad_final', 'decision', 'proxima_evaluacion', 'justificacion_excepcion']);
        $historialActivo = $errors->has('fecha_verificacion_prevista') || $evaluacionConErrores;
        $accionesConErrores = $errors->hasAny(['estado', 'resultado', 'detalle', 'documento_id', 'enlace_externo', 'motivo', 'confirmacion']);
    @endphp
    <a href="{{ route('planificacion.riesgos.index',['periodo'=>$riesgo->periodo_id]) }}">← Volver a riesgos y oportunidades</a>
    <div class="iso-toolbar"><div><div class="iso-code">{{ $riesgo->codigo }}</div><h1 class="iso-title">{{ ucfirst($riesgo->tipo) }}</h1><p class="iso-subtitle">{{ $riesgo->identificacion }}</p></div><div class="text-right"><span class="iso-status {{ $riesgo->indice_inicial>6?'danger':($riesgo->indice_inicial>=3?'warn':'ok') }}">Índice inicial {{ $riesgo->indice_inicial }}</span><div class="mt-2">{{ ucfirst(str_replace('_',' ',$riesgo->estado)) }} · Eficacia {{ ucfirst($riesgo->eficacia) }}</div></div></div>
    <div class="iso-panel mb-4"><div class="iso-panel-body iso-detail-grid">
        <div class="iso-field"><label>Origen</label><div>@if($riesgo->contexto)<a href="{{ route('planificacion.foda.show',$riesgo->contexto) }}">{{ $riesgo->contexto->codigo }} — {{ $riesgo->contexto->titulo }}</a>@else Registro independiente @endif</div></div>
        <div class="iso-field"><label>Proceso</label><div>{{ $riesgo->proceso }}</div></div>
        <div class="iso-field"><label>Efecto potencial</label><div>{{ $riesgo->efecto_potencial }}</div></div>
        <div class="iso-field"><label>Resultado esperado / criterio de eficacia</label><div>{{ $riesgo->criterio_eficacia ?: 'Pendiente de definir' }}</div></div>
        @if($riesgo->estado==='finalizado')
            <div class="iso-field"><label>Evaluación de cierre</label><div>@if($evaluacionCierre){{ $evaluacionCierre->fecha->format('d/m/Y') }}@elseif($riesgo->finalizado_en){{ $riesgo->finalizado_en->format('d/m/Y') }}@else Fecha no disponible @endif <span class="iso-status {{ $resultadoCierre[1] }}">{{ $resultadoCierre[0] }}</span><br><small>Registro finalizado</small></div></div>
        @else
            <div class="iso-field"><label>Próxima evaluación de eficacia</label><div>@if($riesgo->fecha_verificacion_prevista){{ $riesgo->fecha_verificacion_prevista->format('d/m/Y') }} @if($riesgo->fecha_verificacion_prevista->lte(today()))<span class="iso-status danger">Vencida</span>@elseif($riesgo->fecha_verificacion_prevista->lte(today()->addDays(30)))<span class="iso-status warn">Próxima</span>@else<span class="iso-status">Programada</span>@endif @else Sin programar @endif</div></div>
        @endif
        <div class="iso-field"><label>Responsable</label><div>{{ $riesgo->responsable?->name ?: 'Sin asignar' }}</div></div>
        <div class="iso-field"><label>Valoración</label><div>Inicial: {{ $riesgo->impacto_inicial }} × {{ $riesgo->probabilidad_inicial }} = {{ $riesgo->indice_inicial }}@if($riesgo->indice_final)<br>{{ $riesgo->estado==='finalizado'?'Final':'Actual' }}: {{ $riesgo->impacto_final }} × {{ $riesgo->probabilidad_final }} = {{ $riesgo->indice_final }}@endif</div></div>
        @if(filled($evaluacionResumen?->justificacion_excepcion))
            <div class="iso-field iso-field-wide iso-decision-reason"><label>Justificación de la decisión</label><div>{{ $evaluacionResumen->justificacion_excepcion }}</div><small>Registrada en la evaluación del {{ $evaluacionResumen->fecha->format('d/m/Y') }}.</small></div>
        @endif
    </div></div>

    <ul class="nav nav-tabs iso-tabs mb-3" id="riesgoDetalleTabs" role="tablist">
        <li class="nav-item"><a class="nav-link {{ $historialActivo?'':'active' }}" id="acciones-tab" data-toggle="tab" href="#acciones" role="tab" aria-controls="acciones" aria-selected="{{ $historialActivo?'false':'true' }}"><i class="fa-solid fa-list-check mr-1"></i> Acciones y evidencias <span class="badge badge-light ml-1">{{ $riesgo->acciones->whereIn('estado',['completada','cancelada'])->count() }}/{{ $riesgo->acciones->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link {{ $historialActivo?'active':'' }}" id="historial-tab" data-toggle="tab" href="#historial" role="tab" aria-controls="historial" aria-selected="{{ $historialActivo?'true':'false' }}"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Historial de evaluaciones de eficacia <span class="badge badge-light ml-1">{{ $riesgo->verificaciones->count() }}</span></a></li>
    </ul>
    <div class="tab-content" id="riesgoDetalleTabsContent">
        <div class="tab-pane fade {{ $historialActivo?'':'show active' }} mb-4" id="acciones" role="tabpanel" aria-labelledby="acciones-tab"><div class="iso-panel iso-section-card"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Acciones y evidencias</h2><span class="iso-status">{{ $riesgo->acciones->whereIn('estado',['completada','cancelada'])->count() }}/{{ $riesgo->acciones->count() }} resueltas</span></div><div class="iso-panel-body">
            @forelse($riesgo->acciones as $accion)
                <div class="iso-action-card"><button type="button" class="iso-action-summary" data-toggle="collapse" data-target="#detalleAccion{{ $accion->id }}" aria-expanded="{{ $accionesConErrores?'true':'false' }}" aria-controls="detalleAccion{{ $accion->id }}"><span><strong>{{ $accion->descripcion }}</strong><small>{{ $accion->responsable?->name ?: 'Sin responsable' }} · Objetivo {{ $accion->fecha_objetivo?->format('d/m/Y') }}</small></span><span class="d-flex align-items-center"><span class="iso-status {{ $accion->estado==='completada'?'ok':($accion->estado==='cancelada'?'danger':'') }}">{{ ucfirst(str_replace('_',' ',$accion->estado)) }}</span><i class="fa-solid fa-chevron-down ml-3 iso-action-chevron"></i></span></button><div class="collapse {{ $accionesConErrores?'show':'' }}" id="detalleAccion{{ $accion->id }}"><div class="iso-action-detail">
                    @if($accion->resultado)<div class="alert alert-light mt-2 mb-0"><strong>{{ $accion->estado==='cancelada'?'Motivo de cancelación':'Resultado' }}:</strong> {{ $accion->resultado }}</div>@endif
                    @foreach($accion->seguimientos as $s)<div class="mt-3 pl-3 border-left">@if($s->tipo==='evidencia_complementaria')<span class="iso-status warn">Evidencia complementaria posterior al cierre</span><br>@endif<strong>{{ $s->fecha->format('d/m/Y') }}</strong> — {{ $s->detalle }}@if($s->resultado)<div><em>Resultado: {{ $s->resultado }}</em></div>@endif @if($s->documento)<div><a href="{{ route('documentos.validaPermiso',['id'=>$s->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">Documento SGD: {{ $s->documento->titulo }}</a></div>@endif @if($s->enlace_externo)<div><a href="{{ $s->enlace_externo }}" target="_blank" rel="noopener">Evidencia externa</a></div>@endif</div>@endforeach
                    @foreach($accion->transiciones as $transicion)<div class="alert alert-warning mt-3 mb-0"><strong>Acción reabierta el {{ $transicion->created_at->format('d/m/Y H:i') }}</strong><div>{{ $transicion->motivo }}</div><small>{{ $transicion->realizadoPor?->name }} · {{ ucfirst($transicion->estado_anterior) }} → En proceso</small></div>@endforeach
                    @if(auth()->user()->puedeGestionarPlanificacion())
                        @if(in_array($accion->estado,['completada','cancelada']))
                            <hr><h4 class="h6 font-weight-bold">Agregar evidencia complementaria</h4><p class="text-muted">La evidencia respaldará el cierre existente y no reabrirá la acción. Para registrar nueva actividad, reabrila primero.</p><form method="POST" action="{{ route('planificacion.acciones.seguimientos.store',$accion) }}">@csrf<div class="form-row"><div class="form-group col-md-4"><label>Fecha de incorporación</label><input type="date" name="fecha" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="form-group col-md-8"><label>Descripción de la evidencia</label><input name="detalle" class="form-control" placeholder="Qué evidencia se incorpora y qué respalda" required></div></div><div class="form-row"><div class="form-group col-md-5"><select name="documento_id" class="form-control"><option value="">Documento del SGD</option>@foreach($documentos as $d)<option value="{{ $d->id }}">{{ $d->titulo }}</option>@endforeach</select></div><div class="form-group col-md-5"><input type="url" name="enlace_externo" class="form-control" placeholder="https://... evidencia externa"></div><div class="col-md-2"><button class="btn btn-outline-primary btn-block">Agregar evidencia</button></div></div><small class="form-text text-muted mb-3">Debe vincularse un documento del SGD o un enlace externo.</small></form>
                            <div class="d-flex justify-content-between align-items-center"><small class="text-muted">El estado y el resultado permanecen bloqueados mientras la acción esté cerrada.</small><button type="button" class="btn btn-sm btn-outline-warning" data-toggle="modal" data-target="#reabrirAccion{{ $accion->id }}">Reabrir acción</button></div>
                            <div class="modal fade" id="reabrirAccion{{ $accion->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><form method="POST" action="{{ route('planificacion.acciones.reabrir',$accion) }}" class="modal-content">@csrf @method('PATCH')
                                <div class="modal-header bg-light"><div><h5 class="modal-title">Reabrir acción</h5><small>{{ Str::limit($accion->descripcion,120) }}</small></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                                <div class="modal-body"><div class="alert alert-warning"><strong>Impacto de la reapertura:</strong> la acción volverá a En proceso, se conservarán su cierre y las evaluaciones anteriores en el historial, y la eficacia actual del riesgo volverá a Pendiente.</div><div class="form-group"><label>Motivo de la reapertura *</label><textarea name="motivo" class="form-control" rows="3" required minlength="10"></textarea></div><div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="comprendeAccion{{ $accion->id }}" name="comprende_impacto" value="1" required><label class="custom-control-label" for="comprendeAccion{{ $accion->id }}">Comprendo el impacto y confirmo que la acción requiere continuar su tratamiento.</label></div><div class="form-group"><label>Escribí <strong>REABRIR ACCION</strong> para confirmar *</label><input name="confirmacion" class="form-control" required autocomplete="off"></div></div>
                                <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-warning">Confirmar reapertura</button></div>
                            </form></div></div>
                        @else
                            <hr><h4 class="h6 font-weight-bold">Agregar seguimiento o evidencia</h4><form method="POST" action="{{ route('planificacion.acciones.seguimientos.store',$accion) }}">@csrf<div class="form-row"><div class="form-group col-md-4"><input type="date" name="fecha" value="{{ now()->toDateString() }}" class="form-control" required></div><div class="form-group col-md-8"><input name="detalle" class="form-control" placeholder="Actividad, avance o verificación realizada" required></div></div><div class="form-row"><div class="form-group col-md-5"><select name="documento_id" class="form-control"><option value="">Sin documento del SGD</option>@foreach($documentos as $d)<option value="{{ $d->id }}">{{ $d->titulo }}</option>@endforeach</select></div><div class="form-group col-md-5"><input type="url" name="enlace_externo" class="form-control" placeholder="https://... evidencia externa"></div><div class="col-md-2"><button class="btn btn-outline-primary btn-block">Agregar</button></div></div></form>
                            <form method="POST" action="{{ route('planificacion.acciones.actualizar',$accion) }}" class="form-row align-items-end">@csrf @method('PATCH')<div class="form-group col-md-4"><label>Estado</label><select name="estado" class="form-control">@foreach(['pendiente','en_proceso','completada','cancelada'] as $e)<option value="{{ $e }}" @selected($accion->estado===$e)>{{ ucfirst(str_replace('_',' ',$e)) }}</option>@endforeach</select></div><div class="form-group col-md-6"><label>Resultado o motivo de cancelación</label><input name="resultado" value="{{ $accion->resultado }}" class="form-control" placeholder="Obligatorio al completar o cancelar"></div><div class="form-group col-md-2"><button class="btn btn-secondary btn-block">Guardar</button></div></form>
                        @endif
                    @endif
                </div></div>
                </div>
            @empty<p class="text-muted">No hay acciones registradas.</p>@endforelse
            @if(auth()->user()->puedeGestionarPlanificacion())
                <hr><h3 class="h6 font-weight-bold">Agregar acción</h3>
                @if($riesgo->estado==='finalizado')
                    <p class="text-muted">El registro está finalizado. Para agregar una acción es necesario reabrirlo explícitamente.</p><button type="button" class="btn btn-outline-warning" data-toggle="modal" data-target="#reabrirRiesgoConAccion">Reabrir para agregar una acción</button>
                    <div class="modal fade" id="reabrirRiesgoConAccion" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><form method="POST" action="{{ route('planificacion.acciones.store',$riesgo) }}" class="modal-content">@csrf
                        <div class="modal-header bg-light"><div><h5 class="modal-title">Reabrir {{ $riesgo->codigo }} y agregar acción</h5><small>Esta operación modifica un registro formalmente finalizado.</small></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
                        <div class="modal-body"><div class="alert alert-warning"><strong>Impacto:</strong> el registro volverá a En proceso, la eficacia vigente volverá a Pendiente y la valoración de cierre dejará de ser la vigente. El cierre y las evaluaciones anteriores permanecerán en el historial.</div><div class="form-group"><label>Nueva acción *</label><textarea name="descripcion" class="form-control" rows="3" required></textarea></div><div class="form-row"><div class="form-group col-md-7"><label>Responsable *</label><select name="responsable_id" class="form-control" required><option value="">Seleccionar</option>@foreach($usuarios as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div><div class="form-group col-md-5"><label>Fecha objetivo *</label><input type="date" name="fecha_objetivo" class="form-control" required></div></div><div class="form-group"><label>Motivo de la reapertura *</label><textarea name="motivo_reapertura" class="form-control" rows="3" required minlength="10"></textarea></div><div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="comprendeReaperturaRiesgo" name="comprende_impacto" value="1" required><label class="custom-control-label" for="comprendeReaperturaRiesgo">Comprendo el impacto de reabrir el registro y confirmo que la nueva acción requiere continuar el tratamiento.</label></div><div class="form-group"><label>Escribí <strong>REABRIR {{ $riesgo->codigo }}</strong> para confirmar *</label><input name="confirmacion_reapertura" class="form-control" required autocomplete="off"></div></div>
                        <div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-warning">Reabrir y agregar acción</button></div>
                    </form></div></div>
                @else
                    <form method="POST" action="{{ route('planificacion.acciones.store',$riesgo) }}">@csrf<div class="form-group"><textarea name="descripcion" class="form-control" rows="2" placeholder="Acción concreta y verificable" required></textarea></div><div class="form-row"><div class="col-md-6"><select name="responsable_id" class="form-control" required><option value="">Responsable</option>@foreach($usuarios as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div><div class="col-md-4"><input type="date" name="fecha_objetivo" class="form-control" required></div><div class="col-md-2"><button class="btn btn-primary btn-block">Agregar</button></div></div></form>
                @endif
            @endif
        </div></div></div>

        <div class="tab-pane fade {{ $historialActivo?'show active':'' }} mb-4" id="historial" role="tabpanel" aria-labelledby="historial-tab">
            <div class="iso-panel iso-section-card mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Historial de evaluaciones de eficacia</h2></div><div class="iso-panel-body">
                @forelse($riesgo->verificaciones->sortByDesc('fecha') as $verificacion)<div class="iso-evaluation-card {{ $verificacion->eficacia==='si'?'is-effective':($verificacion->eficacia==='no'?'is-ineffective':'is-partial') }}"><div class="d-flex justify-content-between"><strong>{{ $verificacion->fecha->format('d/m/Y') }} · {{ $verificacion->estado_resultante==='finalizado'?'Cierre del registro':'Evaluación de eficacia' }}</strong><span class="iso-status {{ $verificacion->eficacia==='si'?'ok':($verificacion->eficacia==='no'?'danger':'warn') }}">{{ ucfirst($verificacion->eficacia) }}</span></div><small class="text-muted">{{ $verificacion->verificador?->name }}</small><div>{{ $verificacion->conclusion }}</div>@if($verificacion->indice)<div>Valoración: {{ $verificacion->impacto }} × {{ $verificacion->probabilidad }} = {{ $verificacion->indice }} · {{ $verificacion->estado_resultante==='finalizado'?'Finalizado':'Continúa en tratamiento' }}</div>@endif @if($verificacion->justificacion_excepcion)<div><strong>Justificación:</strong> {{ $verificacion->justificacion_excepcion }}</div>@endif @if($verificacion->documento)<div><a href="{{ route('documentos.validaPermiso',['id'=>$verificacion->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">Documento SGD: {{ $verificacion->documento->titulo }}</a></div>@endif @if($verificacion->enlace_externo)<div><a href="{{ $verificacion->enlace_externo }}" target="_blank" rel="noopener">Evidencia externa</a></div>@endif</div>@empty<div class="iso-empty-card"><i class="fa-regular fa-clipboard mr-2"></i><div><strong>Sin evaluaciones registradas</strong><small>Cuando se evalúe la eficacia, cada resultado aparecerá aquí en una tarjeta independiente.</small></div></div>@endforelse
                @foreach($riesgo->transiciones as $transicion)<div class="alert alert-warning mb-3"><strong>Registro reabierto el {{ $transicion->created_at->format('d/m/Y H:i') }}</strong><div>{{ $transicion->motivo }}</div><small>{{ $transicion->realizadoPor?->name }} · Finalizado → En proceso</small></div>@endforeach
            </div></div>

            @if(auth()->user()->puedeGestionarPlanificacion() && $riesgo->estado!=='finalizado')
            <div class="iso-panel iso-section-card mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Programación de la próxima evaluación</h2></div><form method="POST" action="{{ route('planificacion.riesgos.fecha-verificacion.update',$riesgo) }}" class="iso-panel-body">@csrf @method('PATCH')<div class="form-row align-items-end"><div class="form-group col-md-4 mb-md-0"><label>Reprogramar evaluación de eficacia</label><input type="date" name="fecha_verificacion_prevista" value="{{ old('fecha_verificacion_prevista',$riesgo->fecha_verificacion_prevista?->toDateString()) }}" class="form-control"><small class="form-text text-muted">El cambio queda registrado en el historial de auditoría.</small></div><div class="col-md-3"><button class="btn btn-outline-primary">Guardar fecha</button></div></div></form></div>
            @endif

            @if(auth()->user()->puedeGestionarPlanificacion() && $riesgo->estado!=='finalizado')
            <div class="text-right mb-3"><button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#formularioEvaluacionEficacia" aria-expanded="{{ $evaluacionConErrores?'true':'false' }}" aria-controls="formularioEvaluacionEficacia"><i class="fa-solid fa-clipboard-check mr-1"></i> Evaluar eficacia</button></div>
            <div class="collapse {{ $evaluacionConErrores?'show':'' }}" id="formularioEvaluacionEficacia"><div class="iso-panel iso-section-card"><div class="iso-panel-header"><div><h2 class="iso-section-title mb-0">Nueva evaluación de eficacia</h2><small class="text-muted">Completá este formulario cuando corresponda realizar la evaluación programada.</small></div></div><form method="POST" action="{{ route('planificacion.riesgos.verificar',$riesgo) }}" class="iso-panel-body iso-evaluation-form">@csrf @method('PATCH')
                <div class="alert alert-info">Evaluar la eficacia no implica cerrar el riesgo. Primero registrá el resultado y después indicá si el tratamiento continúa o si corresponde finalizarlo.</div>
                <div class="form-row"><div class="form-group col-lg-8"><label>Resultado esperado / criterio de eficacia *</label><textarea name="criterio_eficacia" class="form-control" rows="2" required>{{ old('criterio_eficacia',$riesgo->criterio_eficacia) }}</textarea></div><div class="form-group col-lg-4"><label>Fecha de evaluación</label><input type="date" name="fecha" value="{{ old('fecha',now()->toDateString()) }}" class="form-control" required></div></div>
                <div class="form-row"><div class="form-group col-lg-6"><label>Resultado de eficacia</label><select name="eficacia" id="eficaciaEvaluacion" class="form-control"><option value="si" @selected(old('eficacia')==='si')>Sí — se alcanzó el resultado esperado</option><option value="parcial" @selected(old('eficacia','parcial')==='parcial')>Parcial — hubo mejora, pero falta completar</option><option value="no" @selected(old('eficacia')==='no')>No — no se alcanzó el resultado</option></select></div><div class="form-group col-lg-6"><label>Decisión posterior a la evaluación *</label><select name="decision" id="decisionEvaluacion" class="form-control" required><option value="continuar" @selected(old('decision','continuar')==='continuar')>Continuar tratamiento</option><option value="finalizar" @selected(old('decision')==='finalizar')>Finalizar riesgo u oportunidad</option></select><small class="form-text text-muted">Con eficacia parcial o no eficaz, lo habitual es continuar.</small></div></div>
                <div class="form-row"><div class="form-group col-lg-3"><label>{{ $riesgo->tipo==='riesgo'?'Gravedad actual':'Beneficio actual' }}</label><select name="impacto_final" id="impactoFinalEvaluacion" class="form-control">@foreach([1=>'1 — Bajo',2=>'2 — Medio',3=>'3 — Alto'] as $n=>$texto)<option value="{{ $n }}" @selected(old('impacto_final',$riesgo->impacto_final)==$n)>{{ $texto }}</option>@endforeach</select></div><div class="form-group col-lg-3"><label>Probabilidad actual</label><select name="probabilidad_final" id="probabilidadFinalEvaluacion" class="form-control">@foreach([1,2,3] as $n)<option value="{{ $n }}" @selected(old('probabilidad_final',$riesgo->probabilidad_final)==$n)>{{ $n }}</option>@endforeach</select></div><div class="form-group col-lg-6"><label>Próxima fecha de evaluación</label><input type="date" name="proxima_evaluacion" id="proximaEvaluacion" value="{{ old('proxima_evaluacion',$riesgo->fecha_verificacion_prevista?->toDateString()) }}" class="form-control"><small id="ayudaProximaEvaluacion" class="form-text text-muted">Obligatoria cuando se decide continuar.</small></div></div>
                <div class="form-row"><div class="form-group col-12"><label>Conclusión y evidencia *</label><textarea name="conclusion" class="form-control" rows="3" required>{{ old('conclusion') }}</textarea></div></div>
                <div class="form-row {{ $errors->has('justificacion_excepcion') || old('justificacion_excepcion') ? '' : 'd-none' }}" id="bloqueJustificacionEvaluacion" data-server-error="{{ $errors->has('justificacion_excepcion') ? '1' : '0' }}" data-tipo="{{ $riesgo->tipo }}" data-indice-inicial="{{ $riesgo->indice_inicial }}"><div class="form-group col-12"><label>Justificación de la excepción *</label><textarea name="justificacion_excepcion" id="justificacionEvaluacion" class="form-control" rows="2" placeholder="Explicá la excepción o decisión adoptada">{{ old('justificacion_excepcion') }}</textarea><small id="ayudaJustificacion" class="form-text text-muted"></small></div></div>
                <div class="form-row"><div class="form-group col-lg-6"><label>Documento del SGD</label><select name="documento_id" class="form-control"><option value="">Sin documento</option>@foreach($documentos as $d)<option value="{{ $d->id }}">{{ $d->titulo }}</option>@endforeach</select></div><div class="form-group col-lg-6"><label>Evidencia externa</label><input type="url" name="enlace_externo" class="form-control" placeholder="https://..."></div></div>
                <button class="btn btn-primary">Registrar evaluación</button>
            </form></div></div>
            @endif
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
$(function () {
    const $decision = $('#decisionEvaluacion');
    const $eficacia = $('#eficaciaEvaluacion');
    const $proxima = $('#proximaEvaluacion');
    const $ayudaProxima = $('#ayudaProximaEvaluacion');
    const $justificacion = $('#justificacionEvaluacion');
    const $ayudaJustificacion = $('#ayudaJustificacion');
    const $bloqueJustificacion = $('#bloqueJustificacionEvaluacion');
    const $impacto = $('#impactoFinalEvaluacion');
    const $probabilidad = $('#probabilidadFinalEvaluacion');

    function actualizarReglasEvaluacion() {
        const continuar = $decision.val() === 'continuar';
        const eficacia = $eficacia.val();
        $proxima.prop('disabled', !continuar).prop('required', continuar);
        if (!continuar) $proxima.val('');
        $ayudaProxima.text(continuar
            ? 'Obligatoria porque el tratamiento continuará abierto.'
            : 'No corresponde programar otra evaluación porque el registro será finalizado.');

        const indiceFinal = Number($impacto.val()) * Number($probabilidad.val());
        const indiceInicial = Number($bloqueJustificacion.data('indice-inicial'));
        const tipo = $bloqueJustificacion.data('tipo');
        const valoracionSinMejora = eficacia === 'si' && (
            (tipo === 'riesgo' && indiceFinal >= indiceInicial) ||
            (tipo === 'oportunidad' && indiceFinal <= indiceInicial)
        );
        const justificacionObligatoria = (continuar && eficacia === 'si') || (!continuar && eficacia !== 'si') || valoracionSinMejora;
        const conservarVisible = $bloqueJustificacion.data('server-error') === 1 || $justificacion.val().trim() !== '';
        $justificacion.prop('required', justificacionObligatoria);
        $bloqueJustificacion.toggleClass('d-none', !justificacionObligatoria && !conservarVisible);
        if (continuar && eficacia === 'si') {
            $ayudaJustificacion.text('Obligatoria: explicá por qué se continuará aunque el resultado haya sido eficaz.');
        } else if (!continuar && eficacia !== 'si') {
            $ayudaJustificacion.text('Obligatoria: justificá la aceptación del riesgo residual para cerrar con eficacia parcial o no eficaz.');
        } else if (valoracionSinMejora) {
            $ayudaJustificacion.text(tipo === 'riesgo'
                ? 'Obligatoria: explicá por qué se declara eficaz si el índice no disminuyó.'
                : 'Obligatoria: explicá por qué se declara eficaz si el índice no aumentó.');
        } else {
            $ayudaJustificacion.text('Justificación adicional registrada para esta evaluación.');
        }
    }

    $decision.add($eficacia).add($impacto).add($probabilidad).on('change', actualizarReglasEvaluacion);
    actualizarReglasEvaluacion();
});
</script>
@endpush
