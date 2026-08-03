@extends('layouts.main')
@include('iso._styles')
@section('heading','Nuevo riesgo u oportunidad')
@section('contenidoPrincipal')
@php
    $erroresEvaluacion = $errors->hasAny(['criterio_eficacia','impacto_inicial','probabilidad_inicial','fecha_verificacion_prevista']);
    $erroresTratamiento = $errors->hasAny(['accion_descripcion','accion_responsable_id','accion_fecha_objetivo']);
    $pasoInicial = $erroresTratamiento ? 3 : ($erroresEvaluacion ? 2 : 1);
@endphp
<div class="iso-shell">
    <a href="{{ url()->previous() }}">← Volver</a>
    <div class="iso-eyebrow mt-3">{{ $contexto?->codigo ?? 'Registro independiente' }}</div>
    <h1 class="iso-title">Nuevo riesgo u oportunidad</h1>
    @include('iso._alerts')

    <form method="POST" action="{{ route('planificacion.riesgos.store') }}" class="iso-panel iso-wizard" id="riesgoWizard" data-initial-step="{{ $pasoInicial }}" novalidate>
        @csrf
        <input type="hidden" name="periodo_id" value="{{ $periodo->id }}">
        <input type="hidden" name="contexto_id" value="{{ $contexto?->id }}">

        <div class="iso-wizard-steps" aria-label="Progreso del formulario">
            <button type="button" class="iso-wizard-step" data-step-target="1"><span>1</span><div><strong>Identificación</strong><small>Evento, proceso y alcance</small></div></button>
            <div class="iso-wizard-line"></div>
            <button type="button" class="iso-wizard-step" data-step-target="2"><span>2</span><div><strong>Evaluación</strong><small>Valoración y eficacia</small></div></button>
            <div class="iso-wizard-line"></div>
            <button type="button" class="iso-wizard-step" data-step-target="3"><span>3</span><div><strong>Tratamiento opcional</strong><small>Primera acción</small></div></button>
        </div>

        <div class="iso-panel-body">
            <section class="iso-wizard-pane" data-step="1">
                <div class="iso-wizard-heading"><span>1</span><div><h2>Identificación</h2><p>Describí qué puede ocurrir, dónde impacta y quién será responsable.</p></div></div>
                @if($contexto)<div class="alert alert-info"><strong>Origen:</strong> {{ ucfirst($contexto->tipo) }} — {{ $contexto->titulo }}</div>@endif
                @php($procesoActual=old('proceso',$contexto?->proceso))
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Tipo *</label><select name="tipo" class="form-control" required><option value="riesgo" @selected(old('tipo',in_array($contexto?->tipo,['debilidad','amenaza'])?'riesgo':'oportunidad')==='riesgo')>Riesgo</option><option value="oportunidad" @selected(old('tipo',in_array($contexto?->tipo,['debilidad','amenaza'])?'riesgo':'oportunidad')==='oportunidad')>Oportunidad</option></select><small class="form-text text-muted">Las amenazas y debilidades suelen originar riesgos; las fortalezas y oportunidades pueden originar oportunidades.</small></div>
                    <div class="form-group col-md-5"><label>Proceso *</label><select name="proceso" class="form-control" required data-other-target="riesgoProcesoOtro"><option value="">Seleccionar proceso</option>@foreach($procesos as $proceso)<option value="{{ $proceso }}" @selected($procesoActual===$proceso)>{{ $proceso }}</option>@endforeach<option value="__otro__" @selected($procesoActual && !$procesos->contains($procesoActual))>Otro</option></select><div id="riesgoProcesoOtro" class="mt-2 d-none"><input name="proceso_otro" class="form-control" value="{{ $procesoActual && !$procesos->contains($procesoActual) ? $procesoActual : old('proceso_otro') }}" placeholder="Escribí el nombre del proceso"></div><small class="form-text text-muted">Elegí el proceso que podría sufrir el efecto o que gestionará la oportunidad.</small></div>
                    <div class="form-group col-md-4"><label>Responsable</label><select name="responsable_id" class="form-control"><option value="">Sin asignar</option>@foreach($usuarios as $u)<option value="{{ $u->id }}" @selected(old('responsable_id')==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
                </div>
                <div class="form-group"><label>Identificación *</label><textarea name="identificacion" class="form-control" rows="3" required placeholder="Ej.: Posible interrupción del servicio por dependencia de un único proveedor">{{ old('identificacion',$contexto?->descripcion) }}</textarea><small class="form-text text-muted">Describí el evento incierto y su causa, sin incluir todavía la acción que se realizará.</small></div>
                <div class="form-row">
                    <div class="form-group col-md-6"><label>Partes afectadas</label><select name="partes_interesadas_seleccion[]" class="form-control" multiple size="6" data-other-target="riesgoPartesOtro">@foreach($partesCatalogo as $parte)<option value="{{ $parte }}" @selected(in_array($parte,old('partes_interesadas_seleccion',[])))>{{ $parte }}</option>@endforeach<option value="__otro__" @selected(in_array('__otro__',old('partes_interesadas_seleccion',[])))>Otra</option></select><div id="riesgoPartesOtro" class="mt-2 d-none"><input name="partes_interesadas_otro" class="form-control" value="{{ old('partes_interesadas_otro') }}" placeholder="Escribí otra parte afectada"></div><small class="form-text text-muted">Podés seleccionar varias manteniendo presionada la tecla Ctrl. Indicá quién podría verse beneficiado o perjudicado.</small></div>
                    <div class="form-group col-md-6"><label>Efecto potencial *</label><textarea name="efecto_potencial" class="form-control" rows="3" required placeholder="Ej.: Indisponibilidad del servicio para clientes y demora en la atención">{{ old('efecto_potencial') }}</textarea><small class="form-text text-muted">Explicá la consecuencia posible sobre clientes, calidad, plazos, costos o resultados.</small></div>
                </div>
            </section>

            <section class="iso-wizard-pane" data-step="2" hidden>
                <div class="iso-wizard-heading"><span>2</span><div><h2>Evaluación</h2><p>Valorá el evento y definí cómo se comprobará la eficacia del tratamiento.</p></div></div>
                <div class="form-group"><label>Resultado esperado / criterio de eficacia *</label><textarea name="criterio_eficacia" class="form-control" rows="3" required placeholder="Ej.: Restablecer los servicios críticos en menos de dos horas utilizando el proveedor alternativo">{{ old('criterio_eficacia') }}</textarea><small class="form-text text-muted">Definí cómo se determinará objetivamente si el tratamiento fue eficaz.</small></div>
                <div class="form-row">
                    <div class="form-group col-md-3"><label>Gravedad / beneficio *</label><select name="impacto_inicial" class="form-control" required>@foreach([1=>'1 — Bajo',2=>'2 — Medio',3=>'3 — Alto'] as $n=>$etiqueta)<option value="{{ $n }}" @selected(old('impacto_inicial')==$n)>{{ $etiqueta }}</option>@endforeach</select><small class="form-text text-muted">Magnitud del perjuicio o beneficio esperado.</small></div>
                    <div class="form-group col-md-3"><label>Probabilidad *</label><select name="probabilidad_inicial" class="form-control" required>@foreach([1=>'1 — Poco probable',2=>'2 — Posible',3=>'3 — Probable'] as $n=>$etiqueta)<option value="{{ $n }}" @selected(old('probabilidad_inicial')==$n)>{{ $etiqueta }}</option>@endforeach</select><small class="form-text text-muted">Posibilidad de que el evento ocurra.</small></div>
                    <div class="form-group col-md-6"><label>Fecha prevista para evaluar la eficacia</label><input type="date" name="fecha_verificacion_prevista" value="{{ old('fecha_verificacion_prevista') }}" class="form-control"><small class="form-text text-muted">Fecha en la que se evaluará si el tratamiento produjo el resultado esperado. No es la fecha de finalización de una acción.</small></div>
                </div>
            </section>

            <section class="iso-wizard-pane" data-step="3" hidden>
                <div class="iso-wizard-heading"><span>3</span><div><h2>Tratamiento opcional</h2><p>Podés crear la primera acción ahora o guardar el registro y agregarla después.</p></div></div>
                <div class="iso-optional-note"><i class="fa-regular fa-lightbulb"></i><div><strong>Este paso es opcional</strong><span>Si todavía no está definido el tratamiento, dejá estos campos vacíos.</span></div></div>
                <div class="form-group"><label>Acción / tratamiento</label><textarea name="accion_descripcion" class="form-control" rows="3">{{ old('accion_descripcion') }}</textarea></div>
                <div class="form-row">
                    <div class="form-group col-md-8"><label>Responsable de la acción</label><select name="accion_responsable_id" class="form-control"><option value="">Seleccionar</option>@foreach($usuarios as $u)<option value="{{ $u->id }}" @selected(old('accion_responsable_id')==$u->id)>{{ $u->name }}</option>@endforeach</select></div>
                    <div class="form-group col-md-4"><label>Fecha objetivo</label><input type="date" name="accion_fecha_objetivo" value="{{ old('accion_fecha_objetivo') }}" class="form-control"></div>
                </div>
            </section>
        </div>

        <div class="iso-wizard-footer">
            <button type="button" class="btn btn-outline-secondary" id="wizardAnterior">← Anterior</button>
            <span class="iso-wizard-counter" aria-live="polite"></span>
            <div>
                <button type="button" class="btn btn-primary" id="wizardSiguiente">Siguiente →</button>
                <button type="submit" class="btn btn-primary" id="wizardGuardar">Crear registro</button>
            </div>
        </div>
    </form>
</div>
@include('iso._guided_fields')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('riesgoWizard');
    if (!form) return;
    const panes = Array.from(form.querySelectorAll('.iso-wizard-pane'));
    const indicators = Array.from(form.querySelectorAll('.iso-wizard-step'));
    const previous = document.getElementById('wizardAnterior');
    const next = document.getElementById('wizardSiguiente');
    const save = document.getElementById('wizardGuardar');
    const counter = form.querySelector('.iso-wizard-counter');
    let current = Number(form.dataset.initialStep || 1);

    function showStep(step) {
        current = Math.max(1, Math.min(3, step));
        panes.forEach(pane => pane.hidden = Number(pane.dataset.step) !== current);
        indicators.forEach(indicator => {
            const number = Number(indicator.dataset.stepTarget);
            indicator.classList.toggle('active', number === current);
            indicator.classList.toggle('complete', number < current);
            indicator.setAttribute('aria-current', number === current ? 'step' : 'false');
        });
        previous.style.visibility = current === 1 ? 'hidden' : 'visible';
        next.hidden = current === 3;
        save.hidden = current !== 3;
        counter.textContent = 'Paso ' + current + ' de 3';
        form.scrollIntoView({behavior:'smooth', block:'start'});
    }

    function validateStep() {
        const pane = panes.find(item => Number(item.dataset.step) === current);
        const fields = Array.from(pane.querySelectorAll('input, select, textarea'));
        const invalid = fields.find(field => !field.checkValidity());
        if (invalid) {
            invalid.reportValidity();
            invalid.focus();
            return false;
        }
        return true;
    }

    next.addEventListener('click', () => { if (validateStep()) showStep(current + 1); });
    previous.addEventListener('click', () => showStep(current - 1));
    indicators.forEach(indicator => indicator.addEventListener('click', () => {
        const target = Number(indicator.dataset.stepTarget);
        if (target < current) showStep(target);
        if (target === current + 1 && validateStep()) showStep(target);
    }));
    form.addEventListener('submit', function (event) {
        for (const pane of panes) {
            const invalid = Array.from(pane.querySelectorAll('input, select, textarea')).find(field => !field.checkValidity());
            if (invalid) {
                event.preventDefault();
                showStep(Number(pane.dataset.step));
                invalid.reportValidity();
                invalid.focus();
                return;
            }
        }
    });
    showStep(current);
});
</script>
@endpush
