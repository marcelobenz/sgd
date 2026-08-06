@extends('layouts.main')
@include('iso._styles')
@section('heading','Proveedor')
@section('contenidoPrincipal')
@php
    $resultadoLabels=['aprobado'=>'Aprobado','condicional'=>'Condicional','no_aprobado'=>'No aprobado'];
    $decisionLabels=['continuar'=>'Continuar','continuar_con_acciones'=>'Continuar con acciones','reemplazar'=>'Reemplazar','suspender'=>'Suspender'];
    $cicloLabels=['cerrada'=>'Ciclo cerrado','en_tratamiento'=>'En tratamiento','pendiente_reevaluacion'=>'Pendiente de reevaluación'];
    $criteriosEvaluacion=['precio_calidad'=>'Relación precio-calidad','resolucion_imprevistos'=>'Resolución ante imprevistos','calidad_producto'=>'Calidad del producto o servicio','calidad_atencion'=>'Calidad de la atención'];
    $criteriosSeleccion=['caracteristicas'=>'Características del producto o servicio','recomendaciones'=>'Recomendaciones','precio_condiciones'=>'Precio y condiciones comerciales'];
@endphp
<div class="iso-shell">
    @include('iso._alerts')
    <a href="{{ route('planificacion.proveedores.index') }}">← Volver a proveedores</a>
    <div class="d-flex justify-content-between align-items-start gap-3 mt-3 flex-wrap">
        <div><div class="iso-code">{{ $proveedor->codigo }}</div><h1 class="iso-title">{{ $proveedor->nombre }}</h1><p class="iso-subtitle mb-0">{{ $proveedor->producto_servicio }}</p></div>
        <div class="text-end"><span class="iso-status {{ $proveedor->criticidad==='critico'?'danger':'' }}">{{ $proveedor->criticidad==='critico'?'Proveedor crítico':'Proveedor no crítico' }}</span><div class="mt-2">{{ ucfirst($proveedor->estado) }}</div></div>
    </div>

    <ul class="nav nav-tabs iso-tabs mt-4" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#ficha" role="tab">Ficha</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#seleccion" role="tab">Selección <span class="badge badge-light">{{ $proveedor->selecciones->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#evaluaciones" role="tab">Evaluaciones <span class="badge badge-light">{{ $proveedor->evaluaciones->count() }}</span></a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#acciones" role="tab">Acciones y evidencias</a></li>
    </ul>

    <div class="tab-content pt-3">
        <div id="ficha" class="tab-pane fade show active">
            <div class="iso-panel provider-profile-panel"><div class="iso-panel-header provider-profile-header"><strong>Ficha permanente</strong>@if(auth()->user()->puedeGestionarPlanificacion())<button class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#editarFicha"><i class="fa-regular fa-pen-to-square mr-1"></i> Editar ficha</button>@endif</div><div class="iso-panel-body">
                <div class="iso-detail-grid provider-profile-grid">
                    <div class="iso-field"><label>Área responsable</label><div>{{ $proveedor->area_responsable }}</div></div>
                    <div class="iso-field"><label>Fecha de alta</label><div>{{ $proveedor->fecha_alta?->format('d/m/Y') ?? 'Sin registrar' }}</div></div>
                    <div class="iso-field"><label>Periodicidad</label><div>{{ $proveedor->periodicidad_meses }} meses</div></div>
                    <div class="iso-field"><label>Próxima evaluación</label><div>{{ $proveedor->ultimaEvaluacion?->proxima_evaluacion?->format('d/m/Y') ?? 'Sin programar' }}</div></div>
                    @if($proveedor->fecha_baja)<div class="iso-field"><label>Última baja</label><div>{{ $proveedor->fecha_baja->format('d/m/Y H:i') }}</div></div><div class="iso-field"><label>Motivo de baja</label><div>{{ $proveedor->motivo_baja }}</div></div>@endif
                    @if($proveedor->fecha_reactivacion)<div class="iso-field"><label>Última reactivación</label><div>{{ $proveedor->fecha_reactivacion->format('d/m/Y H:i') }}</div></div><div class="iso-field"><label>Motivo de reactivación</label><div>{{ $proveedor->motivo_reactivacion }}</div></div>@endif
                    <div class="iso-field iso-field-wide"><label>Observaciones</label><div>{{ $proveedor->observaciones ?: 'Sin observaciones' }}</div></div>
                    @if($proveedor->documento)<div class="iso-field"><label>Documento del SGD</label><div><a href="{{ route('documentos.validaPermiso',['id'=>$proveedor->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">{{ $proveedor->documento->titulo }}</a></div></div>@endif
                    @if($proveedor->enlace_externo)<div class="iso-field"><label>Enlace externo</label><div><a href="{{ $proveedor->enlace_externo }}" target="_blank" rel="noopener">Abrir referencia</a></div></div>@endif
                </div>
                @if(auth()->user()->puedeGestionarPlanificacion())
                <form id="editarFicha" method="POST" action="{{ route('planificacion.proveedores.update',$proveedor) }}" class="collapse row g-3 mt-3 provider-form provider-inline-form">@csrf @method('PATCH')
                    <div class="col-md-4"><label class="form-label">Proveedor *</label><input name="nombre" value="{{ $proveedor->nombre }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Producto o servicio *</label><input name="producto_servicio" value="{{ $proveedor->producto_servicio }}" class="form-control" required></div><div class="col-md-4"><label class="form-label">Área responsable *</label><input name="area_responsable" value="{{ $proveedor->area_responsable }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Fecha de alta</label><input type="date" name="fecha_alta" value="{{ $proveedor->fecha_alta?->toDateString() }}" class="form-control"></div><div class="col-md-3"><label class="form-label">Criticidad *</label><select name="criticidad" class="form-select"><option value="no_critico" @selected($proveedor->criticidad==='no_critico')>No crítico</option><option value="critico" @selected($proveedor->criticidad==='critico')>Crítico</option></select></div><div class="col-md-3"><label class="form-label">Periodicidad *</label><input type="number" name="periodicidad_meses" min="1" max="60" value="{{ $proveedor->periodicidad_meses }}" class="form-control" required></div><div class="col-md-3"><label class="form-label">Estado</label><div class="provider-readonly-value">{{ ucfirst($proveedor->estado) }}<small>Se administra desde Gestión del proveedor.</small></div></div>
                    <div class="col-12"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control">{{ $proveedor->observaciones }}</textarea></div><div class="col-md-6"><label class="form-label">Documento del SGD</label><select name="documento_id" class="form-select"><option value="">Sin documento</option>@foreach($documentos as $documento)<option value="{{ $documento->id }}" @selected($proveedor->documento_id===$documento->id)>{{ $documento->titulo }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Enlace externo</label><input type="url" name="enlace_externo" value="{{ $proveedor->enlace_externo }}" class="form-control"></div><div><button class="btn btn-primary">Guardar ficha</button></div>
                </form>@endif
            </div></div>
            @if(auth()->user()->puedeGestionarPlanificacion())
            @php($puedeEliminar=$proveedor->selecciones->isEmpty() && $proveedor->evaluaciones->isEmpty())
            <div class="iso-panel mt-3 provider-lifecycle-panel"><div class="iso-panel-header"><div><strong>Gestión del proveedor</strong><small class="d-block text-muted">Estas operaciones no modifican las evaluaciones ni el historial existente.</small></div></div><div class="iso-panel-body">
                @if($proveedor->estado==='activo')
                    <div class="provider-lifecycle-option"><div><strong>Dar de baja</strong><p>Deja de estar disponible para nuevas evaluaciones, pero conserva selección, evaluaciones, acciones, evidencias y riesgos relacionados.</p></div><button class="btn btn-outline-warning" data-toggle="collapse" data-target="#bajaProveedor">Dar de baja</button></div>
                    <form id="bajaProveedor" class="collapse provider-confirm-form provider-form" method="POST" action="{{ route('planificacion.proveedores.baja',$proveedor) }}">@csrf @method('PATCH')<div class="provider-impact-message"><strong>Impacto de la baja</strong><span>El proveedor quedará inactivo. Todo su historial seguirá disponible para consulta y auditoría.</span></div><label class="form-label">Motivo de la baja *</label><textarea name="motivo" class="form-control" required maxlength="2000" placeholder="Explicá por qué deja de utilizarse este proveedor"></textarea><label class="provider-confirm-check"><input type="checkbox" name="confirmacion" value="1" required><span>Comprendo que no podrán registrarse nuevas evaluaciones hasta que el proveedor sea reactivado.</span></label><div class="provider-confirm-actions"><button type="button" class="btn btn-light" data-toggle="collapse" data-target="#bajaProveedor">Cancelar</button><button class="btn btn-warning">Confirmar baja</button></div></form>
                @else
                    <div class="provider-lifecycle-option"><div><strong>Reactivar proveedor</strong><p>Vuelve a habilitar la gestión y permite registrar nuevas evaluaciones. El historial anterior se mantiene sin cambios.</p></div><button class="btn btn-outline-primary" data-toggle="collapse" data-target="#reactivarProveedor">Reactivar</button></div>
                    <form id="reactivarProveedor" class="collapse provider-confirm-form provider-form" method="POST" action="{{ route('planificacion.proveedores.reactivar',$proveedor) }}">@csrf @method('PATCH')<div class="provider-impact-message"><strong>Impacto de la reactivación</strong><span>El proveedor volverá a estar activo y disponible para su evaluación y seguimiento.</span></div><label class="form-label">Motivo de la reactivación *</label><textarea name="motivo" class="form-control" required maxlength="2000" placeholder="Explicá por qué vuelve a utilizarse este proveedor"></textarea><label class="provider-confirm-check"><input type="checkbox" name="confirmacion" value="1" required><span>Comprendo que el proveedor volverá a estar habilitado para su gestión.</span></label><div class="provider-confirm-actions"><button type="button" class="btn btn-light" data-toggle="collapse" data-target="#reactivarProveedor">Cancelar</button><button class="btn btn-primary">Confirmar reactivación</button></div></form>
                @endif
                @if($puedeEliminar)
                    <div class="provider-lifecycle-option provider-delete-option"><div><strong>Eliminar ficha creada por error</strong><p>Disponible porque todavía no existen selecciones, evaluaciones, acciones ni evidencias relacionadas.</p></div><button class="btn btn-outline-danger" data-toggle="collapse" data-target="#eliminarProveedor">Eliminar</button></div>
                    <form id="eliminarProveedor" class="collapse provider-confirm-form provider-form is-danger" method="POST" action="{{ route('planificacion.proveedores.destroy',$proveedor) }}">@csrf @method('DELETE')<div class="provider-impact-message"><strong>Eliminación definitiva</strong><span>Se eliminará la ficha {{ $proveedor->codigo }}. Esta operación no puede deshacerse.</span></div><label class="form-label">Motivo de la eliminación *</label><textarea name="motivo" class="form-control" required maxlength="2000" placeholder="Indicá por qué la ficha fue creada por error"></textarea><label class="provider-confirm-check"><input type="checkbox" name="confirmacion" value="1" required><span>Comprendo que esta ficha será eliminada definitivamente.</span></label><div class="provider-confirm-actions"><button type="button" class="btn btn-light" data-toggle="collapse" data-target="#eliminarProveedor">Cancelar</button><button class="btn btn-danger">Eliminar definitivamente</button></div></form>
                @endif
                <section class="provider-history-section"><div class="provider-history-title"><div><strong>Historial de bajas y reactivaciones</strong><small>Trazabilidad cronológica para consulta y auditoría.</small></div><span>{{ $historialCicloVida->count() }} eventos</span></div>@include('iso.proveedores._historial_ciclo')</section>
            </div></div>
            @endif
        </div>

        <div id="seleccion" class="tab-pane fade">
            @foreach($proveedor->selecciones as $seleccion)
                <div class="iso-evaluation-card {{ $seleccion->resultado==='aprobado'?'is-effective':($seleccion->resultado==='condicional'?'is-partial':'is-ineffective') }}">
                    <div class="d-flex justify-content-between gap-3"><strong>{{ $seleccion->fecha->format('d/m/Y') }} · Selección inicial</strong><span class="iso-status {{ $seleccion->resultado==='aprobado'?'ok':($seleccion->resultado==='condicional'?'warn':'danger') }}">{{ $resultadoLabels[$seleccion->resultado] }} · {{ number_format($seleccion->puntaje,2,',','.') }}</span></div>
                    <div class="row mt-3">@foreach($criteriosSeleccion as $clave=>$label)<div class="col-md-4"><small class="text-muted d-block">{{ $label }}</small>{{ data_get($seleccion->calificaciones,$clave) ?? 'No aplica' }}</div>@endforeach</div>
                    <p class="mb-0 mt-3">{{ $seleccion->conclusion }}</p>
                </div>
            @endforeach
            @if(auth()->user()->puedeGestionarPlanificacion() && $proveedor->estado==='activo' && $proveedor->selecciones->isEmpty())
            <div class="iso-panel mt-3"><div class="iso-panel-header"><strong>Registrar selección</strong><small>Escala vigente: 2 a 10. Los campos opcionales no participan del promedio.</small></div>
                <form method="POST" action="{{ route('planificacion.proveedores.selecciones.store',$proveedor) }}" class="iso-panel-body provider-form" id="seleccionProveedorForm">@csrf
                    <div class="row g-3"><div class="col-md-3"><label class="form-label">Fecha *</label><input type="date" name="fecha" value="{{ old('fecha',today()->toDateString()) }}" class="form-control" required></div>
                    @foreach($criteriosSeleccion as $clave=>$label)<div class="col-md-3"><label class="form-label">{{ $label }} {{ $clave==='caracteristicas'?'*':'' }}</label><select name="{{ $clave }}" class="form-select js-selection-score" {{ $clave==='caracteristicas'?'required':'' }}>@if($clave!=='caracteristicas')<option value="">No aplica</option>@endif @for($n=2;$n<=10;$n++)<option value="{{ $n }}" @selected(old($clave)==$n)>{{ $n }}</option>@endfor</select></div>@endforeach
                    <div class="col-12"><div class="provider-result-preview" id="selectionResultPreview">El resultado se calculará con los criterios seleccionados.</div></div></div>
                    <button class="btn btn-primary mt-3">Registrar selección</button>
                </form>
            </div>@elseif($proveedor->selecciones->isNotEmpty())<div class="alert alert-light border mt-3">La selección inicial ya está registrada. Las revisiones posteriores se realizan desde Evaluaciones.</div>@elseif($proveedor->estado==='inactivo')<div class="alert alert-light border mt-3">El proveedor está inactivo. Reactivalo desde la ficha antes de registrar su selección inicial.</div>@endif
        </div>

        <div id="evaluaciones" class="tab-pane fade">
            @foreach($proveedor->evaluaciones as $evaluacion)
                <div id="evaluacion-{{ $evaluacion->id }}" class="iso-evaluation-card provider-evaluation-anchor {{ $evaluacion->resultado==='aprobado'?'is-effective':($evaluacion->resultado==='condicional'?'is-partial':'is-ineffective') }}">
                    <div class="d-flex justify-content-between gap-3 flex-wrap"><div><strong>{{ $evaluacion->fecha_evaluacion->format('d/m/Y') }} · {{ $evaluacion->tipo==='reevaluacion'?'Reevaluación':'Evaluación periódica' }}</strong><small class="d-block text-muted">{{ $evaluacion->evaluador->name }}{{ $evaluacion->periodo?' · '.$evaluacion->periodo->nombre:'' }}</small></div><div class="text-right"><span class="iso-status {{ $evaluacion->resultado==='aprobado'?'ok':($evaluacion->resultado==='condicional'?'warn':'danger') }}">{{ $resultadoLabels[$evaluacion->resultado] }} · {{ number_format($evaluacion->puntaje,2,',','.') }}</span><span class="iso-status d-block mt-1 {{ $evaluacion->estado_ciclo==='en_tratamiento'?'warn':($evaluacion->estado_ciclo==='pendiente_reevaluacion'?'warn':'ok') }}">{{ $cicloLabels[$evaluacion->estado_ciclo] }}</span></div></div>
                    <div class="row mt-3">@foreach($criteriosEvaluacion as $clave=>$label)<div class="col-md-3"><small class="text-muted d-block">{{ $label }}</small>{{ data_get($evaluacion->calificaciones,$clave) }}</div>@endforeach</div>
                    <p class="mt-3 mb-1"><strong>Decisión:</strong> {{ $decisionLabels[$evaluacion->decision] }}</p><p class="mb-1">{{ $evaluacion->conclusion }}</p>
                    @if($evaluacion->justificacion)<div class="iso-decision-reason mt-2"><label>Justificación</label><div>{{ $evaluacion->justificacion }}</div></div>@endif
                    <div class="mt-2"><strong>Próxima evaluación:</strong> {{ $evaluacion->proxima_evaluacion?->format('d/m/Y') ?? 'No corresponde' }}</div>
                    @if($evaluacion->riesgos->isNotEmpty())<div class="mt-2"><strong>Riesgos vinculados:</strong> @foreach($evaluacion->riesgos as $riesgo)<a href="{{ route('planificacion.riesgos.show',$riesgo) }}" class="me-2">{{ $riesgo->codigo }}</a>@endforeach</div>@endif
                    @if($evaluacion->requiere_analisis_riesgo && $evaluacion->riesgos->isEmpty())<div class="mt-2"><span class="iso-status warn">Análisis de riesgo pendiente</span></div>@endif
                    @if($evaluacion->evaluacionAnterior)<div class="provider-cycle-link mt-3"><strong>Reevaluación vinculada</strong><div>Resultado anterior: {{ $resultadoLabels[$evaluacion->evaluacionAnterior->resultado] }} · {{ number_format($evaluacion->evaluacionAnterior->puntaje,2,',','.') }} → Resultado actual: {{ $resultadoLabels[$evaluacion->resultado] }} · {{ number_format($evaluacion->puntaje,2,',','.') }}</div><small>Tratamiento anterior: {{ $evaluacion->evaluacionAnterior->acciones->where('obligatoria',true)->whereIn('estado',['completada','cancelada'])->count() }}/{{ $evaluacion->evaluacionAnterior->acciones->where('obligatoria',true)->count() }} acciones resueltas.</small></div>@endif
                </div>
            @endforeach
            @if(auth()->user()->puedeGestionarPlanificacion() && $proveedor->estado==='activo' && $periodos->isNotEmpty())
            @if($proveedor->cicloActivo?->estado_ciclo === 'en_tratamiento')
                <div class="alert alert-warning"><strong>Evaluación en tratamiento.</strong><div>Resolvé las acciones obligatorias antes de registrar la reevaluación. El resultado actual permanece {{ $resultadoLabels[$proveedor->cicloActivo->resultado] }}.</div></div>
            @else
            <button class="btn btn-primary mb-3" data-toggle="collapse" data-target="#nuevaEvaluacion"><i class="fa-solid fa-clipboard-check mr-1"></i> {{ $proveedor->cicloActivo?'Reevaluar proveedor después del tratamiento':'Evaluar proveedor' }}</button>
            <div id="nuevaEvaluacion" class="collapse iso-panel provider-wizard @if($errors->any()) show @endif"><div class="iso-panel-header"><div><strong>Nueva evaluación periódica</strong><small class="d-block text-muted">El formulario mostrará solamente la información necesaria para el resultado obtenido.</small></div></div>
                <form method="POST" action="{{ route('planificacion.proveedores.evaluaciones.store',$proveedor) }}" id="providerEvaluationForm" class="provider-form">@csrf
                    @if($proveedor->cicloActivo)<input type="hidden" name="evaluacion_anterior_id" value="{{ $proveedor->cicloActivo->id }}"><div class="provider-cycle-link m-3"><strong>Reevaluación del tratamiento</strong><div>{{ $resultadoLabels[$proveedor->cicloActivo->resultado] }} · {{ number_format($proveedor->cicloActivo->puntaje,2,',','.') }} · {{ $proveedor->cicloActivo->acciones->where('obligatoria',true)->count() }} acciones obligatorias resueltas.</div></div>@endif
                    <input type="hidden" name="requiere_accion" id="requiereAccionValue" value="{{ old('requiere_accion',0) }}">
                    <div class="provider-wizard-nav"><span class="active" data-step-indicator="1">1. Evaluación</span><span data-step-indicator="2">2. Decisión</span><span data-step-indicator="3">3. Seguimiento</span></div>
                    <section class="iso-panel-body provider-step" data-step="1">
                        <h3 class="iso-section-title">Evaluación del desempeño</h3><p class="text-muted">Seleccioná las cuatro calificaciones. El resultado se calcula inmediatamente.</p>
                        <div class="provider-control-grid provider-control-grid-meta"><div><label class="form-label">Fecha *</label><input type="date" name="fecha_evaluacion" value="{{ old('fecha_evaluacion',today()->toDateString()) }}" class="form-control" required></div><div><label class="form-label">Período *</label><select name="periodo_id" class="form-select" required>@foreach($periodos as $periodo)<option value="{{ $periodo->id }}" @selected(old('periodo_id')==$periodo->id)>{{ $periodo->nombre }}</option>@endforeach</select></div></div>
                        <div class="provider-control-grid provider-control-grid-scores">@foreach($criteriosEvaluacion as $clave=>$label)<div><label class="form-label">{{ $label }} *</label><select name="{{ $clave }}" class="form-select js-evaluation-score" required><option value="">Seleccionar</option>@for($n=2;$n<=10;$n++)<option value="{{ $n }}" @selected(old($clave)==$n)>{{ $n }}</option>@endfor</select></div>@endforeach</div>
                        <div id="evaluationResultPreview" class="provider-result-preview mt-3">Completá los cuatro criterios para conocer el resultado.</div>
                    </section>
                    <section class="iso-panel-body provider-step d-none" data-step="2">
                        <h3 class="iso-section-title">Decisión sobre el proveedor</h3><p class="text-muted">Definí si la prestación continúa, se reemplaza o se suspende.</p>
                        <div class="row g-3"><div class="col-md-4"><label class="form-label">Decisión *</label><select name="decision" id="providerDecision" class="form-select" required><option value="continuar" @selected(old('decision','continuar')==='continuar')>Continuar</option><option value="reemplazar" @selected(old('decision')==='reemplazar')>Reemplazar</option><option value="suspender" @selected(old('decision')==='suspender')>Suspender</option></select></div><div class="col-md-8"><label class="form-label">Conclusión *</label><textarea name="conclusion" class="form-control" rows="2" required>{{ old('conclusion') }}</textarea></div><div class="col-12 d-none" id="justificationBlock"><label class="form-label" id="justificationLabel">Justificación de la decisión *</label><textarea name="justificacion" id="providerJustification" class="form-control" rows="2">{{ old('justificacion') }}</textarea><small class="text-muted">Explicá por qué se adopta esta decisión considerando el resultado obtenido.</small></div></div>
                    </section>
                    <section class="iso-panel-body provider-step d-none" data-step="3">
                        <h3 class="iso-section-title">Seguimiento</h3><p class="text-muted">Solo aparecen los controles que corresponden a la evaluación y decisión informadas.</p>
                        <div class="row g-3">
                            <div class="col-md-4" id="nextEvaluationBlock"><label class="form-label">Próxima evaluación *</label><input type="date" name="proxima_evaluacion" id="nextEvaluation" value="{{ old('proxima_evaluacion',today()->addMonths($proveedor->periodicidad_meses)->toDateString()) }}" class="form-control"></div>
                            <div class="col-md-5"><label class="form-label">¿El resultado puede afectar significativamente al SGC? *</label><select name="requiere_analisis_riesgo" id="riskAnalysis" class="form-select"><option value="0" @selected(old('requiere_analisis_riesgo',0)==0)>No, se gestiona dentro de proveedores</option><option value="1" @selected(old('requiere_analisis_riesgo')==1)>Sí, requiere análisis de riesgo</option></select></div>
                            <div class="col-12 d-none" id="relatedRisksBlock"><label class="form-label">Riesgos relacionados</label><select name="riesgos[]" class="form-select" multiple size="4">@foreach($riesgos as $riesgo)<option value="{{ $riesgo->id }}" @selected(in_array($riesgo->id,old('riesgos',[])))>{{ $riesgo->codigo }} — {{ str($riesgo->identificacion)->limit(100) }}</option>@endforeach</select><small class="text-muted">Podés vincular uno existente ahora. Si no seleccionás ninguno, quedará identificado como análisis de riesgo pendiente.</small></div>
                            <div class="col-12" id="optionalActionChoice"><label class="mb-0"><input type="checkbox" id="addActionToggle" class="mr-2">Agregar una acción de mejora o seguimiento</label></div>
                            <div class="col-12 d-none" id="actionBlock"><div class="provider-conditional-card"><strong id="actionHeading">Acción inicial</strong><small class="d-block text-muted mb-3" id="actionHelp">Definí la medida que se realizará.</small><div class="row g-3"><div class="col-md-6"><label class="form-label">Descripción *</label><textarea name="accion_descripcion" id="actionDescription" class="form-control">{{ old('accion_descripcion') }}</textarea></div><div class="col-md-3"><label class="form-label">Área responsable *</label><select name="accion_area_responsable" id="actionArea" class="form-select"><option value="">Seleccionar</option>@foreach($areas as $area)<option @selected(old('accion_area_responsable')===$area)>{{ $area }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Responsable</label><select name="accion_responsable_id" class="form-select"><option value="">Sin asignar</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(old('accion_responsable_id')==$usuario->id)>{{ $usuario->name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Fecha objetivo *</label><input type="date" name="accion_fecha_objetivo" id="actionDate" value="{{ old('accion_fecha_objetivo') }}" class="form-control"></div></div></div></div>
                            <div class="col-12"><label class="mb-0"><input type="checkbox" id="addEvidenceToggle" class="mr-2">Adjuntar evidencia</label></div><div class="col-12 d-none" id="evidenceBlock"><div class="row g-3">@include('iso.proveedores._evidencia')</div></div>
                            <div class="col-12"><div class="provider-summary"><strong>Resumen antes de registrar</strong><div id="evaluationSummary" class="mt-2"></div></div></div>
                        </div>
                    </section>
                    <div class="provider-wizard-footer"><button type="button" class="btn btn-outline-secondary d-none" id="providerPrev">Anterior</button><span id="providerStepLabel">Paso 1 de 3</span><button type="button" class="btn btn-primary" id="providerNext" disabled>Siguiente</button><button type="submit" class="btn btn-primary d-none" id="providerSubmit">Registrar evaluación</button></div>
                </form>
            </div>@endif
            @elseif($proveedor->estado==='inactivo')
                <div class="alert alert-light border"><strong>Proveedor inactivo.</strong><div>El historial continúa disponible, pero no pueden registrarse nuevas evaluaciones hasta reactivarlo.</div></div>
            @endif
        </div>

        <div id="acciones" class="tab-pane fade">
            @php($evaluacionesConAcciones=$proveedor->evaluaciones->filter(fn($evaluacion)=>$evaluacion->acciones->isNotEmpty()))
            @forelse($evaluacionesConAcciones as $evaluacionAccion)
                <section class="provider-action-cycle">
                    <header class="provider-action-cycle-header">
                        <div><span class="provider-action-cycle-kicker">Evaluación relacionada</span><strong>{{ $evaluacionAccion->fecha_evaluacion->format('d/m/Y') }} · {{ $evaluacionAccion->tipo==='reevaluacion'?'Reevaluación':'Evaluación periódica' }}</strong><small>{{ $resultadoLabels[$evaluacionAccion->resultado] }} · {{ number_format($evaluacionAccion->puntaje,2,',','.') }} · {{ $cicloLabels[$evaluacionAccion->estado_ciclo] }}</small></div>
                        <button type="button" class="btn btn-sm btn-outline-primary provider-show-evaluation" data-evaluation="evaluacion-{{ $evaluacionAccion->id }}"><i class="fa-regular fa-eye mr-1"></i> Ver evaluación</button>
                    </header>
                    <div class="provider-action-cycle-body">
                    @foreach($evaluacionAccion->acciones as $accion)
                        <div class="iso-action-card provider-action-card"><div class="d-flex justify-content-between gap-3"><div><div class="provider-action-kind {{ $accion->obligatoria?'required':'optional' }}">{{ $accion->obligatoria?'Acción requerida':'Mejora opcional' }}</div><strong>{{ $accion->descripcion }}</strong><small class="d-block text-muted mt-1">{{ $accion->area_responsable }}{{ $accion->responsable?' · '.$accion->responsable->name:'' }} · Objetivo {{ $accion->fecha_objetivo->format('d/m/Y') }}</small></div><span class="iso-status {{ $accion->estado==='completada'?'ok':($accion->estado==='cancelada'?'danger':'warn') }}">{{ str($accion->estado)->replace('_',' ')->title() }}</span></div>
                            @if($accion->resultado)<div class="provider-action-result"><span>Resultado</span>{{ $accion->resultado }}</div>@endif
                            @if(auth()->user()->puedeGestionarPlanificacion() && ($evaluacionAccion->periodo?->estaAbierto() ?? true) && !in_array($accion->estado,['completada','cancelada']))<form method="POST" action="{{ route('planificacion.proveedores.acciones.update',$accion) }}" class="row g-2 mt-3 provider-form provider-action-update">@csrf @method('PATCH')<div class="col-md-3"><label class="form-label">Estado</label><select name="estado" class="form-select"><option value="pendiente">Pendiente</option><option value="en_proceso">En proceso</option><option value="completada">Completada</option><option value="cancelada">Cancelada</option></select></div><div class="col-md-7"><label class="form-label">Resultado o motivo</label><input name="resultado" class="form-control" placeholder="Describí el resultado o el motivo de cancelación"></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary w-100">Guardar</button></div></form>@endif
                        </div>
                    @endforeach
                    </div>
                </section>
            @empty<div class="iso-empty-card">No hay acciones registradas para este proveedor.</div>@endforelse
            @if(auth()->user()->puedeGestionarPlanificacion() && $proveedor->estado==='activo' && $proveedor->evaluaciones->isNotEmpty() && (($proveedor->cicloActivo ?? $proveedor->ultimaEvaluacion)->periodo?->estaAbierto() ?? true))
                @php($evaluacionDestinoAccion=$proveedor->cicloActivo ?? $proveedor->ultimaEvaluacion)
                <button class="btn btn-outline-primary" data-toggle="collapse" data-target="#agregarAccion">Agregar acción</button>
                <form id="agregarAccion" class="collapse iso-panel iso-panel-body mt-3 provider-form provider-action-form" method="POST" action="{{ route('planificacion.proveedores.acciones.store',$evaluacionDestinoAccion) }}">@csrf
                    <input type="hidden" name="obligatoria" value="{{ $evaluacionDestinoAccion->estado_ciclo==='cerrada'?0:1 }}"><p class="text-muted">La acción quedará asociada a la evaluación del {{ $evaluacionDestinoAccion->fecha_evaluacion->format('d/m/Y') }} — {{ $resultadoLabels[$evaluacionDestinoAccion->resultado] }}. {{ $evaluacionDestinoAccion->estado_ciclo==='cerrada'?'Será una mejora opcional y no reabrirá el ciclo.':'Será obligatoria para completar el tratamiento.' }}</p><div class="row g-3"><div class="col-md-6"><label class="form-label">Descripción *</label><textarea name="descripcion" class="form-control" required></textarea></div><div class="col-md-3"><label class="form-label">Área responsable *</label><select name="area_responsable" class="form-select" required><option value="">Seleccionar</option>@foreach($areas as $area)<option>{{ $area }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Responsable</label><select name="responsable_id" class="form-select"><option value="">Sin asignar</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}">{{ $usuario->name }}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Fecha objetivo *</label><input type="date" name="fecha_objetivo" class="form-control" required></div>@include('iso.proveedores._evidencia')</div><button class="btn btn-primary mt-3">Crear acción</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
@push('styles')
<style>
    .provider-wizard-nav{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:16px 18px;background:#f8fafc;border-bottom:1px solid #dfe4ea}.provider-wizard-nav span{padding:10px 14px;border-radius:8px;color:#667085;font-weight:700;text-align:center}.provider-wizard-nav span.active{background:#eef4ff;color:#2457e6}.provider-result-preview{padding:16px 18px;border:1px solid #cdd8e5;border-radius:10px;background:#f4f7fb;color:#52657c;font-weight:700}.provider-result-preview.ok{background:#ecfdf3;border-color:#a6dfb5;color:#166534}.provider-result-preview.warn{background:#fff8e6;border-color:#ecd58d;color:#795b00}.provider-result-preview.danger{background:#fff1f1;border-color:#efb1b1;color:#991b1b}.provider-conditional-card{padding:18px;border:1px solid #d6dfeb;border-left:4px solid #7294f4;border-radius:10px;background:#f6f8fb}.provider-summary{padding:18px;border:1px solid #cdd8e5;border-radius:10px;background:#eef4ff;color:#344054}.provider-cycle-link{padding:14px 16px;border:1px solid #cdd8e5;border-left:4px solid #7294f4;border-radius:9px;background:#eef4ff;color:#344054}.provider-wizard-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:#f8fafc;border-top:1px solid #dfe4ea}@media(max-width:600px){.provider-wizard-nav span{font-size:.78rem;padding:8px 4px}.provider-wizard-footer{flex-wrap:wrap}}
    .provider-form{--form-border:#dce4ee;--form-muted:#667085}.provider-form .form-label{display:block;width:100%;font-weight:700;color:#344054;margin:0 0 7px;line-height:1.35}.provider-form .form-control,.provider-form .form-select{display:block;width:100%;min-height:42px;border:1px solid #cbd5e1;border-radius:7px;background-color:#fff}.provider-form select.form-select{height:42px;padding:7px 34px 7px 12px}.provider-form textarea.form-control{min-height:76px}.provider-form .form-control:focus,.provider-form .form-select:focus{border-color:#7294f4;box-shadow:0 0 0 3px rgba(36,87,230,.1)}.provider-form .row>[class*="col-"]{margin-bottom:16px}.provider-form .row>[class*="col-"]>.text-muted{display:block;margin-top:6px}
    .provider-profile-header{display:flex;align-items:center;justify-content:space-between;gap:16px;padding-top:12px;padding-bottom:12px}.provider-profile-panel>.iso-panel-body{padding:16px 20px}.provider-profile-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px 18px}.provider-profile-grid .iso-field{min-width:0;padding:10px 12px;border-radius:8px;background:#f8fafc}.provider-profile-grid .iso-field label{display:block;margin:0 0 3px;font-size:.68rem;line-height:1.2;letter-spacing:.045em}.provider-profile-grid .iso-field>div{line-height:1.35;overflow-wrap:anywhere}.provider-profile-grid .iso-field-wide{grid-column:1/-1}.provider-profile-grid+.provider-inline-form{margin-top:16px!important}
    .provider-control-grid{display:grid;gap:18px;margin-bottom:20px;align-items:start}.provider-control-grid-meta{grid-template-columns:repeat(2,minmax(220px,1fr));max-width:760px}.provider-control-grid-scores{grid-template-columns:repeat(4,minmax(0,1fr))}.provider-control-grid>div{min-width:0}.provider-control-grid .form-label{min-height:38px;display:flex;align-items:flex-end}.provider-control-grid-meta .form-label{min-height:auto}.provider-control-grid .form-control,.provider-control-grid .form-select{width:100%!important;max-width:none!important}
    .provider-inline-form{margin-left:0;margin-right:0;padding:20px;border:1px solid var(--form-border);border-radius:12px;background:#f8fafc}.provider-inline-form:before{content:'Editar ficha permanente';display:block;width:100%;font-weight:800;color:#183153;font-size:1.05rem;margin-bottom:4px}.provider-inline-form>div:last-child{width:100%;display:flex;justify-content:flex-end}
    .provider-readonly-value{min-height:42px;padding:9px 12px;border:1px solid #dce4ee;border-radius:7px;background:#f1f5f9;font-weight:700;color:#475467}.provider-readonly-value small{display:block;font-weight:400;color:#667085;margin-top:2px}.provider-lifecycle-panel .iso-panel-body{padding-top:8px}.provider-lifecycle-option{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:16px 0;border-bottom:1px solid #e4eaf2}.provider-lifecycle-option strong{display:block;color:#183153}.provider-lifecycle-option p{margin:4px 0 0;color:#667085}.provider-delete-option{margin-top:8px;border-bottom:0}.provider-delete-option strong{color:#991b1b}.provider-confirm-form{margin-top:12px;padding:18px;border:1px solid #e3c878;border-radius:11px;background:#fffbeb}.provider-confirm-form.is-danger{border-color:#efb1b1;background:#fff5f5}.provider-impact-message{display:flex;flex-direction:column;padding:12px 14px;margin-bottom:16px;border-radius:8px;background:rgba(255,255,255,.75);color:#344054}.provider-impact-message span{margin-top:3px;color:#667085}.provider-confirm-check{display:flex;align-items:flex-start;gap:10px;margin:14px 0 0;padding:12px 14px;border-radius:8px;background:#fff;cursor:pointer}.provider-confirm-check input{margin-top:4px}.provider-confirm-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
    .provider-history-section{margin-top:24px;padding-top:20px;border-top:1px solid #dce4ee}.provider-history-title{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:8px}.provider-history-title strong,.provider-history-title small{display:block}.provider-history-title small{color:#667085;margin-top:2px}.provider-history-title>span{padding:4px 9px;border-radius:999px;background:#eef2f7;color:#475467;font-size:.75rem;font-weight:800}.provider-lifecycle-history{display:flex;flex-direction:column}.provider-history-event{display:grid;grid-template-columns:38px minmax(0,1fr);gap:12px;position:relative;padding:14px 0}.provider-history-event:not(:last-child):before{content:'';position:absolute;left:18px;top:48px;bottom:-8px;width:2px;background:#dce4ee}.provider-history-marker{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:#f1f5f9;color:#667085;z-index:1}.provider-history-event.is-baja .provider-history-marker{background:#fff4d6;color:#8a6500}.provider-history-event.is-reactivacion .provider-history-marker{background:#dcfce7;color:#166534}.provider-history-event.is-eliminacion .provider-history-marker{background:#fee2e2;color:#991b1b}.provider-history-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.provider-history-heading strong,.provider-history-type{display:block}.provider-history-heading time{white-space:nowrap;color:#667085;font-size:.84rem}.provider-history-type{text-transform:uppercase;letter-spacing:.05em;font-size:.68rem;font-weight:800;color:#667085;margin-bottom:2px}.provider-history-content p{margin:7px 0 2px;color:#344054}.provider-history-content small{color:#667085}
    #seleccionProveedorForm{background:#fbfcfe}#seleccionProveedorForm>.row{padding:18px;border:1px solid var(--form-border);border-radius:12px;background:#fff}#seleccionProveedorForm .provider-result-preview{margin-top:4px}
    .provider-wizard{overflow:hidden;background:#f7f9fc}.provider-wizard .provider-step{margin:18px;border:1px solid var(--form-border);border-radius:12px;background:#fff;padding:22px}.provider-wizard .iso-section-title{font-size:1.15rem;color:#183153;margin-bottom:4px}.provider-wizard .provider-step>.text-muted{margin-bottom:20px}.provider-wizard-nav{position:relative}.provider-wizard-nav span{border:1px solid transparent}.provider-wizard-nav span.active{border-color:#cbdaf9;box-shadow:0 2px 7px rgba(36,87,230,.08)}
    .provider-action-cycle{border:1px solid #dbe4ef;border-radius:14px;background:#fff;overflow:hidden;margin-bottom:20px;box-shadow:0 4px 15px rgba(23,43,77,.04)}.provider-action-cycle-header{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:16px 20px;background:#f3f7fc;border-bottom:1px solid #dbe4ef}.provider-action-cycle-header strong,.provider-action-cycle-header small{display:block}.provider-action-cycle-header small{color:#667085;margin-top:3px}.provider-action-cycle-kicker{display:block;color:#2457e6;text-transform:uppercase;letter-spacing:.06em;font-size:.69rem;font-weight:800;margin-bottom:3px}.provider-action-cycle-body{padding:16px}.provider-action-card{margin-bottom:12px!important;background:#fbfcfe!important;border-left-color:#86a5d8!important}.provider-action-card:last-child{margin-bottom:0!important}.provider-action-kind{display:inline-block;padding:3px 8px;border-radius:999px;font-size:.7rem;font-weight:800;margin-bottom:7px}.provider-action-kind.required{background:#fff0d2;color:#805600}.provider-action-kind.optional{background:#eaf1ff;color:#2457e6}.provider-action-result{margin-top:14px;padding:12px 14px;border-radius:8px;background:#f1f5f9;color:#344054}.provider-action-result span{display:block;color:#667085;text-transform:uppercase;font-size:.68rem;font-weight:800;letter-spacing:.05em;margin-bottom:2px}.provider-action-update{padding-top:14px;border-top:1px solid #e4eaf2}.provider-action-form>p{padding:14px 16px;border-radius:9px;background:#eef4ff;border-left:4px solid #7294f4;color:#344054!important}.provider-evaluation-anchor{scroll-margin-top:20px}.provider-evaluation-anchor.provider-highlight{animation:providerHighlight 1.8s ease}@keyframes providerHighlight{0%,100%{box-shadow:none}35%{box-shadow:0 0 0 4px rgba(36,87,230,.2)}}
    @media(max-width:991px){.provider-control-grid-scores{grid-template-columns:repeat(2,minmax(0,1fr))}.provider-profile-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:767px){.provider-wizard .provider-step{margin:10px;padding:16px}.provider-action-cycle-header{align-items:flex-start;flex-direction:column}.provider-action-cycle-header .btn{width:100%}.provider-inline-form{padding:16px}.provider-control-grid-meta,.provider-control-grid-scores{grid-template-columns:1fr}.provider-control-grid .form-label{min-height:auto}.provider-form .row>[class*="col-"]{margin-bottom:14px}.provider-lifecycle-option{align-items:flex-start;flex-direction:column}.provider-lifecycle-option .btn{width:100%}.provider-confirm-actions{flex-direction:column-reverse}.provider-confirm-actions .btn{width:100%}.provider-history-title,.provider-history-heading{align-items:flex-start;flex-direction:column}.provider-profile-panel>.iso-panel-body{padding:12px}.provider-profile-grid{gap:8px}}
    @media(max-width:520px){.provider-profile-grid{grid-template-columns:1fr}.provider-profile-header{align-items:flex-start;flex-direction:column}.provider-profile-header .btn{width:100%}}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.provider-show-evaluation').forEach(button => button.addEventListener('click', function () {
        const tabLink = document.querySelector('a[href="#evaluaciones"]');
        const target = document.getElementById(this.dataset.evaluation);
        if (!tabLink || !target) return;
        tabLink.click();
        window.setTimeout(() => { target.classList.add('provider-highlight'); target.scrollIntoView({behavior:'smooth',block:'center'}); window.setTimeout(()=>target.classList.remove('provider-highlight'),1900); }, 180);
    }));
    const classify = score => score >= 7 ? {key:'aprobado',label:'Aprobado',css:'ok'} : (score >= 5 ? {key:'condicional',label:'Condicional',css:'warn'} : {key:'no_aprobado',label:'No aprobado',css:'danger'});
    const formatScore = value => value.toFixed(2).replace('.', ',');

    const selectionScores = Array.from(document.querySelectorAll('.js-selection-score'));
    const selectionPreview = document.getElementById('selectionResultPreview');
    const updateSelection = () => {
        if (!selectionPreview) return;
        const values = selectionScores.map(field => Number(field.value)).filter(Boolean);
        selectionPreview.className = 'provider-result-preview';
        if (!values.length) { selectionPreview.textContent = 'El resultado se calculará con los criterios seleccionados.'; return; }
        const score = values.reduce((total,value)=>total+value,0)/values.length;
        const result = classify(score); selectionPreview.classList.add(result.css);
        selectionPreview.textContent = `Resultado calculado: ${result.label} · Puntaje ${formatScore(score)}`;
    };
    selectionScores.forEach(field => field.addEventListener('change', updateSelection)); updateSelection();

    const form = document.getElementById('providerEvaluationForm');
    if (!form) return;
    const scores = Array.from(form.querySelectorAll('.js-evaluation-score'));
    const preview = document.getElementById('evaluationResultPreview');
    const decision = document.getElementById('providerDecision');
    const justificationBlock = document.getElementById('justificationBlock');
    const justification = document.getElementById('providerJustification');
    const nextBlock = document.getElementById('nextEvaluationBlock');
    const nextDate = document.getElementById('nextEvaluation');
    const riskAnalysis = document.getElementById('riskAnalysis');
    const risksBlock = document.getElementById('relatedRisksBlock');
    const actionChoice = document.getElementById('optionalActionChoice');
    const actionToggle = document.getElementById('addActionToggle');
    const actionBlock = document.getElementById('actionBlock');
    const actionValue = document.getElementById('requiereAccionValue');
    const actionFields = [document.getElementById('actionDescription'),document.getElementById('actionArea'),document.getElementById('actionDate')];
    const evidenceToggle = document.getElementById('addEvidenceToggle');
    const evidenceBlock = document.getElementById('evidenceBlock');
    const summary = document.getElementById('evaluationSummary');
    const nextButton = document.getElementById('providerNext');
    const prevButton = document.getElementById('providerPrev');
    const submitButton = document.getElementById('providerSubmit');
    let step = 1, currentResult = null, currentScore = null;

    function calculate() {
        const values = scores.map(field => Number(field.value));
        const complete = values.every(value => value >= 2);
        preview.className = 'provider-result-preview mt-3';
        if (!complete) { currentResult=null; currentScore=null; preview.textContent='Completá los cuatro criterios para conocer el resultado.'; nextButton.disabled=true; updateConditionalFields(); return; }
        currentScore = values.reduce((total,value)=>total+value,0)/values.length; currentResult=classify(currentScore);
        preview.classList.add(currentResult.css); preview.textContent=`${currentResult.label} · Puntaje ${formatScore(currentScore)}. ${currentResult.key==='aprobado'?'El proveedor puede continuar con seguimiento normal.':(currentResult.key==='condicional'?'Si continúa, debe definirse una acción y justificarse la decisión.':'Si continúa excepcionalmente, requiere justificación y acción obligatorias.')}`;
        nextButton.disabled=false; updateConditionalFields();
    }
    function updateConditionalFields() {
        if (!currentResult) return;
        const continues = decision.value === 'continuar';
        const justificationRequired = currentResult.key !== 'aprobado' || !continues;
        justificationBlock.classList.toggle('d-none', !justificationRequired); justification.required = justificationRequired;
        nextBlock.classList.toggle('d-none', !continues); nextDate.required = continues;
        risksBlock.classList.toggle('d-none', riskAnalysis.value !== '1');
        const mandatoryAction = continues && currentResult.key !== 'aprobado';
        if (mandatoryAction) actionToggle.checked=true;
        actionChoice.classList.toggle('d-none', mandatoryAction);
        const showAction = mandatoryAction || actionToggle.checked;
        actionBlock.classList.toggle('d-none', !showAction); actionValue.value = showAction ? '1' : '0';
        actionFields.forEach(field => field.required = showAction);
        summary.innerHTML = `<div><strong>Resultado:</strong> ${currentResult.label} · ${formatScore(currentScore)}</div><div><strong>Decisión:</strong> ${decision.options[decision.selectedIndex].text}</div><div><strong>Acción:</strong> ${showAction ? (mandatoryAction?'Obligatoria':'Se registrará una acción') : 'No requerida'}</div><div><strong>Análisis de riesgo:</strong> ${riskAnalysis.value==='1'?'Requerido; puede vincularse ahora o quedar pendiente':'No requerido'}</div><div><strong>Próxima evaluación:</strong> ${continues ? (nextDate.value || 'Pendiente de indicar') : 'No corresponde'}</div>`;
    }
    function renderStep() {
        form.querySelectorAll('.provider-step').forEach(section => section.classList.toggle('d-none', Number(section.dataset.step)!==step));
        form.querySelectorAll('[data-step-indicator]').forEach(item => item.classList.toggle('active',Number(item.dataset.stepIndicator)===step));
        prevButton.classList.toggle('d-none',step===1); nextButton.classList.toggle('d-none',step===3); submitButton.classList.toggle('d-none',step!==3);
        document.getElementById('providerStepLabel').textContent=`Paso ${step} de 3`; if(step===3) updateConditionalFields();
    }
    scores.forEach(field => field.addEventListener('change',calculate)); decision.addEventListener('change',updateConditionalFields); riskAnalysis.addEventListener('change',updateConditionalFields); actionToggle.addEventListener('change',updateConditionalFields); nextDate.addEventListener('change',updateConditionalFields);
    evidenceToggle.addEventListener('change',()=>evidenceBlock.classList.toggle('d-none',!evidenceToggle.checked));
    nextButton.addEventListener('click',()=>{ if(step===1 && !currentResult) return; if(step===2){ const conclusion=form.querySelector('[name="conclusion"]'); if(!conclusion.value.trim()){ conclusion.reportValidity(); return; } updateConditionalFields(); } step=Math.min(3,step+1); renderStep(); });
    prevButton.addEventListener('click',()=>{step=Math.max(1,step-1);renderStep();});
    calculate(); renderStep();
});
</script>
@endpush
