@extends('layouts.main')
@include('iso._styles')
@section('heading','Nuevo objetivo de calidad')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <a href="{{ route('planificacion.objetivos.index',['periodo'=>$periodo?->id]) }}" class="text-decoration-none">← Volver a objetivos</a>
    <div class="iso-eyebrow mt-3">Planificación del período</div>
    <h1 class="iso-title">Nuevo objetivo de calidad</h1>
    <p class="iso-subtitle">El asistente separa la definición del objetivo, la forma de medirlo y su tratamiento inicial.</p>

    @if(!$periodo)
        <div class="alert alert-warning">Primero debe existir un período abierto desde Períodos y parámetros.</div>
    @else
    <form method="post" action="{{ route('planificacion.objetivos.store') }}" class="iso-panel iso-wizard iso-objective-form" id="objetivoWizard" novalidate>
        @csrf
        <div class="iso-wizard-steps">
            <button type="button" class="iso-wizard-step active" data-step-target="1"><span>1</span><div><strong>Definición</strong><small>Propósito y responsables</small></div></button>
            <div class="iso-wizard-line"></div>
            <button type="button" class="iso-wizard-step" data-step-target="2"><span>2</span><div><strong>Indicador y meta</strong><small>Cómo se medirá</small></div></button>
            <div class="iso-wizard-line"></div>
            <button type="button" class="iso-wizard-step" data-step-target="3"><span>3</span><div><strong>Relaciones y acción</strong><small>Trazabilidad opcional</small></div></button>
        </div>

        <div class="iso-panel-body p-4 p-lg-5">
            <section data-step="1">
                <div class="iso-wizard-heading"><span>1</span><div><h2>Definición del objetivo</h2><p>Indicá qué resultado se pretende alcanzar, para qué período y quién lo seguirá.</p></div></div>
                <div class="row g-3">
                    <div class="col-md-3"><label class="form-label">Período *</label><select name="periodo_id" class="form-select" required>@foreach($periodos as $item)<option value="{{ $item->id }}" @selected(old('periodo_id',$periodo->id)==$item->id)>{{ $item->anio }} — {{ ucfirst($item->estado) }}</option>@endforeach</select></div>
                    <div class="col-md-5"><label class="form-label">Proceso *</label><select name="proceso" class="form-select js-other-select" data-other="proceso_otro" required><option value="">Seleccionar</option>@foreach($procesos as $valor)<option value="{{ $valor }}" @selected(old('proceso')===$valor)>{{ $valor }}</option>@endforeach<option value="__otro__">Otro proceso</option></select><input name="proceso_otro" class="form-control mt-2 d-none" placeholder="Escribí el proceso"></div>
                    <div class="col-md-4"><label class="form-label">Compromiso de la política *</label><select name="compromiso_politica" class="form-select js-other-select" data-other="compromiso_politica_otro" required><option value="">Seleccionar</option>@foreach($compromisos as $valor)<option value="{{ $valor }}" @selected(old('compromiso_politica')===$valor)>{{ $valor }}</option>@endforeach<option value="__otro__">Otro compromiso</option></select><textarea name="compromiso_politica_otro" class="form-control mt-2 d-none" rows="2" placeholder="Describí el compromiso"></textarea></div>
                    <div class="col-12"><label class="form-label">Objetivo *</label><input name="titulo" value="{{ old('titulo') }}" class="form-control" required placeholder="Ej.: Incrementar la satisfacción del cliente"></div>
                    <div class="col-12"><label class="form-label">Descripción</label><textarea name="descripcion" class="form-control" rows="3" placeholder="Explicá el alcance y el resultado que se busca alcanzar">{{ old('descripcion') }}</textarea></div>
                    <div class="col-md-4"><label class="form-label">Área responsable *</label><select name="area_responsable" class="form-select js-other-select" data-other="area_responsable_otro" required><option value="">Seleccionar</option>@foreach($areas as $valor)<option value="{{ $valor }}" @selected(old('area_responsable')===$valor)>{{ $valor }}</option>@endforeach<option value="__otro__">Otra área</option></select><input name="area_responsable_otro" class="form-control mt-2 d-none" placeholder="Escribí el área"></div>
                    <div class="col-md-4"><label class="form-label">Responsable de seguimiento</label><select name="responsable_id" class="form-select"><option value="">Sin asignar</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}" @selected(old('responsable_id')==$usuario->id)>{{ $usuario->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Periodicidad de seguimiento *</label><select name="periodicidad_seguimiento" class="form-select" required><option value="">Seleccionar</option>@foreach($frecuencias as $valor)<option value="{{ $valor }}" @selected(old('periodicidad_seguimiento')===$valor)>{{ $valor }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Fecha de inicio *</label><input type="date" name="fecha_inicio" value="{{ old('fecha_inicio',$periodo->anio.'-01-01') }}" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Fecha objetivo *</label><input type="date" name="fecha_objetivo" value="{{ old('fecha_objetivo',$periodo->anio.'-12-31') }}" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Observaciones iniciales</label><input name="observaciones" value="{{ old('observaciones') }}" class="form-control" placeholder="Antecedentes o aclaraciones necesarias"></div>
                </div>
            </section>

            <section data-step="2" class="d-none">
                <div class="iso-wizard-heading"><span>2</span><div><h2>Indicador y meta</h2><p>Definí una medición objetiva y una regla clara para determinar el cumplimiento.</p></div></div>
                <div class="iso-optional-note"><i class="fa-solid fa-calculator"></i><div><strong>El SGD calculará el resultado</strong><span>Las mediciones se registrarán por fecha; no es necesario reservar una columna para cada mes.</span></div></div>
                <div class="row g-3">
                    <div class="col-md-7"><label class="form-label">Nombre del indicador *</label><input name="indicador_nombre" value="{{ old('indicador_nombre') }}" class="form-control" required placeholder="Ej.: Porcentaje de respuestas positivas"></div>
                    <div class="col-md-5"><label class="form-label">Fuente *</label><input name="indicador_fuente" value="{{ old('indicador_fuente') }}" class="form-control" required placeholder="Ej.: Encuesta de satisfacción"></div>
                    <div class="col-12"><label class="form-label">Método de cálculo *</label><textarea name="indicador_metodo_calculo" class="form-control" rows="3" required placeholder="Ej.: Respuestas positivas ÷ total de respuestas × 100">{{ old('indicador_metodo_calculo') }}</textarea></div>
                    <div class="col-md-3"><label class="form-label">Unidad *</label><select name="indicador_unidad" class="form-select js-other-select" data-other="indicador_unidad_otro" required><option value="">Seleccionar</option>@foreach($unidades as $valor)<option value="{{ $valor }}" @selected(old('indicador_unidad')===$valor)>{{ $valor }}</option>@endforeach<option value="__otro__">Otra unidad</option></select><input name="indicador_unidad_otro" class="form-control mt-2 d-none" placeholder="Escribí la unidad"></div>
                    <div class="col-md-3"><label class="form-label">Frecuencia de medición *</label><select name="indicador_frecuencia" class="form-select" required><option value="">Seleccionar</option>@foreach($frecuencias as $valor)<option value="{{ $valor }}" @selected(old('indicador_frecuencia')===$valor)>{{ $valor }}</option>@endforeach</select></div>
                    <div class="col-md-3"><label class="form-label">Resultado del período *</label><select name="indicador_agregacion" class="form-select" required><option value="ultimo">Última medición</option><option value="promedio">Promedio</option><option value="suma">Suma acumulada</option><option value="variacion">Variación absoluta</option><option value="variacion_porcentual">Variación porcentual</option></select></div>
                    <div class="col-md-3"><label class="form-label">Condición de cumplimiento *</label><select name="indicador_comparador" id="comparador" class="form-select" required><option value="mayor_igual">Mayor o igual que</option><option value="mayor">Mayor que</option><option value="menor_igual">Menor o igual que</option><option value="menor">Menor que</option><option value="igual">Igual a</option><option value="rango">Dentro de un rango</option></select></div>
                    <div class="col-md-3"><label class="form-label">Meta *</label><input type="number" step="any" name="indicador_meta" value="{{ old('indicador_meta') }}" class="form-control" required><small class="text-muted">Para porcentajes ingresá 75, no 0,75.</small></div>
                    <div class="col-md-3 d-none" id="metaHastaGroup"><label class="form-label">Máximo del rango *</label><input type="number" step="any" name="indicador_meta_hasta" value="{{ old('indicador_meta_hasta') }}" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Tolerancia aceptable</label><input type="number" min="0" step="any" name="indicador_tolerancia" value="{{ old('indicador_tolerancia') }}" class="form-control"><small class="text-muted">Desvío máximo para clasificar como aceptable.</small></div>
                    <div class="col-md-3"><label class="form-label">Línea de base</label><input type="number" step="any" name="indicador_linea_base" value="{{ old('indicador_linea_base') }}" class="form-control"></div>
                </div>
            </section>

            <section data-step="3" class="d-none">
                <div class="iso-wizard-heading"><span>3</span><div><h2>Relaciones y acción inicial</h2><p>Vinculá solo los registros que fundamentan el objetivo. Todo este paso es opcional.</p></div></div>
                <div class="row g-4">
                    <div class="col-md-4"><label class="form-label">Elementos FODA relacionados</label><select name="contextos[]" class="form-select" multiple size="5">@foreach($contextos as $item)<option value="{{ $item->id }}">{{ $item->codigo }} — {{ $item->titulo }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Riesgos u oportunidades relacionados</label><select name="riesgos[]" class="form-select" multiple size="5">@foreach($riesgos as $item)<option value="{{ $item->id }}">{{ $item->codigo }} — {{ $item->identificacion }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Partes interesadas relacionadas</label><select name="partes[]" class="form-select" multiple size="5">@foreach($partes as $item)<option value="{{ $item->id }}">{{ $item->nombre }}</option>@endforeach</select></div>
                </div>
                <hr class="my-4">
                <h3 class="iso-section-title">Acción inicial opcional</h3>
                <p class="text-muted">Podés crear el objetivo sin acciones y agregarlas durante el seguimiento.</p>
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Descripción</label><textarea name="accion_descripcion" class="form-control" rows="3">{{ old('accion_descripcion') }}</textarea></div>
                    <div class="col-md-4"><label class="form-label">Área responsable</label><select name="accion_area_responsable" class="form-select js-other-select" data-other="accion_area_responsable_otro"><option value="">Seleccionar</option>@foreach($areas as $valor)<option value="{{ $valor }}">{{ $valor }}</option>@endforeach<option value="__otro__">Otra área</option></select><input name="accion_area_responsable_otro" class="form-control mt-2 d-none" placeholder="Escribí el área"></div>
                    <div class="col-md-4"><label class="form-label">Responsable</label><select name="accion_responsable_id" class="form-select"><option value="">Sin asignar</option>@foreach($usuarios as $usuario)<option value="{{ $usuario->id }}">{{ $usuario->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Fecha objetivo</label><input type="date" name="accion_fecha_objetivo" class="form-control"></div>
                </div>
            </section>
        </div>
        <div class="iso-wizard-footer">
            <span class="iso-wizard-counter">Paso <span id="pasoActual">1</span> de 3</span>
            <div><button type="button" class="btn btn-outline-secondary d-none" id="anterior">Anterior</button> <button type="button" class="btn btn-primary" id="siguiente">Siguiente</button><button type="submit" class="btn btn-primary d-none" id="guardar">Crear objetivo</button></div>
        </div>
    </form>
    @endif
</div>
@endsection
@push('styles')
<style>
.iso-objective-form .form-select{display:block;width:100%;min-height:38px;padding:.375rem .75rem;border:1px solid #ced4da;border-radius:.25rem;background:#fff;color:#212529}.iso-objective-form select[multiple].form-select{height:auto;min-height:118px}.iso-objective-form .form-label{font-weight:600;color:#344054}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('objetivoWizard'); if (!form) return;
    let step = 1; const sections = [...form.querySelectorAll('[data-step]')]; const markers = [...form.querySelectorAll('[data-step-target]')];
    const prev = document.getElementById('anterior'), next = document.getElementById('siguiente'), save = document.getElementById('guardar');
    function show(n){ step=n; sections.forEach(s=>s.classList.toggle('d-none',Number(s.dataset.step)!==n)); markers.forEach((m,i)=>{m.classList.toggle('active',i+1===n);m.classList.toggle('complete',i+1<n)}); prev.classList.toggle('d-none',n===1); next.classList.toggle('d-none',n===3); save.classList.toggle('d-none',n!==3); document.getElementById('pasoActual').textContent=n; }
    function valid(){ const fields=[...sections[step-1].querySelectorAll('[required]')].filter(f=>!f.closest('.d-none')); for(const field of fields){if(!field.checkValidity()){field.reportValidity();return false}} return true; }
    next.addEventListener('click',()=>{if(valid())show(Math.min(3,step+1))}); prev.addEventListener('click',()=>show(Math.max(1,step-1))); markers.forEach(m=>m.addEventListener('click',()=>{const n=Number(m.dataset.stepTarget);if(n<step||valid())show(n)}));
    document.querySelectorAll('.js-other-select').forEach(select=>{const other=document.querySelector(`[name="${select.dataset.other}"]`); const toggle=()=>{const visible=select.value==='__otro__';other.classList.toggle('d-none',!visible);other.required=visible};select.addEventListener('change',toggle);toggle()});
    const comparator=document.getElementById('comparador'), range=document.getElementById('metaHastaGroup'); const toggleRange=()=>{const visible=comparator.value==='rango';range.classList.toggle('d-none',!visible);range.querySelector('input').required=visible}; comparator.addEventListener('change',toggleRange);toggleRange(); show(1);
});
</script>
@endpush
