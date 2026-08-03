@extends('layouts.main')
@include('iso._styles')
@section('heading','Proveedores')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <a href="{{ route('planificacion.index') }}">← Volver a Planificación</a>
    <div class="iso-eyebrow mt-3">Control de provisión externa</div>
    <h1 class="iso-title">Proveedores</h1>
    <p class="iso-subtitle">Ficha permanente, selección inicial, evaluaciones periódicas, acciones y riesgos relacionados.</p>

    <div class="iso-toolbar">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="estado" class="form-select" onchange="this.form.submit()">
                <option value="activo" @selected(request('estado','activo')==='activo')>Activos</option>
                <option value="inactivo" @selected(request('estado')==='inactivo')>Inactivos</option>
                <option value="todos" @selected(request('estado')==='todos')>Todos</option>
            </select>
            <select name="resultado" class="form-select" onchange="this.form.submit()">
                <option value="">Todos los resultados</option>
                <option value="aprobado" @selected(request('resultado')==='aprobado')>Aprobado</option>
                <option value="condicional" @selected(request('resultado')==='condicional')>Condicional</option>
                <option value="no_aprobado" @selected(request('resultado')==='no_aprobado')>No aprobado</option>
            </select>
            <label class="btn btn-outline-warning mb-0"><input type="checkbox" name="vencidos" value="1" class="me-1" @checked(request('vencidos')) onchange="this.form.submit()"> Solo vencidos</label>
        </form>
        @if(auth()->user()->puedeGestionarPlanificacion())<button class="btn btn-primary" data-toggle="collapse" data-target="#nuevoProveedor"><i class="fa-solid fa-plus mr-1"></i> Nuevo proveedor</button>@endif
    </div>

    @if(auth()->user()->puedeGestionarPlanificacion())
    <div id="nuevoProveedor" class="collapse iso-panel mb-4 @if($errors->any()) show @endif">
        <div class="iso-panel-header"><div><strong>Alta de proveedor o prestación</strong><small class="d-block text-muted">Creá la ficha permanente. La selección y evaluación se registran luego desde el proveedor.</small></div></div>
        <form method="POST" action="{{ route('planificacion.proveedores.store') }}" class="iso-panel-body provider-form">@csrf
            <section class="provider-form-section">
                <div class="provider-form-section-title"><span>1</span><div><strong>Identificación</strong><small>Datos que permiten reconocer al proveedor y la prestación.</small></div></div>
                <div class="row g-3"><div class="col-lg-5"><label class="form-label">Proveedor *</label><input name="nombre" value="{{ old('nombre') }}" class="form-control" required></div><div class="col-lg-7"><label class="form-label">Producto o servicio *</label><input name="producto_servicio" value="{{ old('producto_servicio') }}" class="form-control" required></div></div>
            </section>
            <section class="provider-form-section">
                <div class="provider-form-section-title"><span>2</span><div><strong>Responsabilidad y control</strong><small>Define quién lo administra y con qué frecuencia se revisa.</small></div></div>
                <div class="row g-3">
                    <div class="col-lg-4"><label class="form-label">Área responsable *</label><select name="area_responsable" id="providerAreaSelect" class="form-select" required><option value="">Seleccionar</option>@foreach($areas as $area)<option @selected(old('area_responsable')===$area)>{{ $area }}</option>@endforeach<option value="__otro__" @selected(old('area_responsable')==='__otro__')>Otra área…</option></select></div>
                    <div class="col-lg-4 d-none" id="providerOtherArea"><label class="form-label">Especificar otra área *</label><input name="area_responsable_otro" value="{{ old('area_responsable_otro') }}" class="form-control"></div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Fecha de alta</label><input type="date" name="fecha_alta" value="{{ old('fecha_alta') }}" class="form-control"></div>
                    <div class="col-sm-6 col-lg-2"><label class="form-label">Criticidad *</label><select name="criticidad" class="form-select"><option value="no_critico" @selected(old('criticidad')==='no_critico')>No crítico</option><option value="critico" @selected(old('criticidad')==='critico')>Crítico</option></select></div>
                    <div class="col-sm-6 col-lg-3"><label class="form-label">Evaluar cada *</label><div class="input-group"><input type="number" min="1" max="60" name="periodicidad_meses" value="{{ old('periodicidad_meses',12) }}" class="form-control" required><div class="input-group-append"><span class="input-group-text">meses</span></div></div></div>
                </div>
            </section>
            <section class="provider-form-section provider-form-section-muted"><div class="provider-form-section-title"><span><i class="fa-regular fa-note-sticky"></i></span><div><strong>Información complementaria</strong><small>Opcional.</small></div></div><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="2" placeholder="Antecedentes, alcance u otra información útil">{{ old('observaciones') }}</textarea></section>
            <div class="provider-form-actions"><button type="button" class="btn btn-light" data-toggle="collapse" data-target="#nuevoProveedor">Cancelar</button><button class="btn btn-primary">Crear proveedor</button></div>
        </form>
    </div>
    @endif

    <div class="iso-panel overflow-hidden">
        <div class="table-responsive"><table class="table iso-table mb-0">
            <thead><tr><th>Código</th><th>Proveedor</th><th>Producto / servicio</th><th>Criticidad</th><th>Última evaluación</th><th>Próxima evaluación</th><th>Acciones abiertas</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @forelse($proveedores as $proveedor)
                @php($ultima=$proveedor->ultimaEvaluacion)
                @php($abiertas=$proveedor->evaluaciones->flatMap(fn($evaluacion)=>$evaluacion->acciones)->whereNotIn('estado',['completada','cancelada'])->count())
                <tr>
                    <td class="iso-code">{{ $proveedor->codigo }}</td><td><strong>{{ $proveedor->nombre }}</strong></td><td>{{ $proveedor->producto_servicio }}</td>
                    <td><span class="iso-status {{ $proveedor->criticidad==='critico'?'danger':'' }}">{{ $proveedor->criticidad==='critico'?'Crítico':'No crítico' }}</span></td>
                    <td>@if($ultima)<span class="iso-status {{ $ultima->resultado==='aprobado'?'ok':($ultima->resultado==='condicional'?'warn':'danger') }}">{{ str($ultima->resultado)->replace('_',' ')->title() }}</span><small class="d-block text-muted mt-1">{{ $ultima->fecha_evaluacion->format('d/m/Y') }} · {{ number_format($ultima->puntaje,2,',','.') }}</small>@if($ultima->estado_ciclo!=='cerrada')<small class="d-block font-weight-bold mt-1 text-warning">{{ $ultima->estado_ciclo==='en_tratamiento'?'En tratamiento':'Pendiente de reevaluación' }}</small>@endif @else<span class="text-muted">Sin evaluar</span>@endif</td>
                    <td>@if($ultima?->proxima_evaluacion)<span class="{{ $ultima->proxima_evaluacion->isPast()?'text-danger fw-bold':'' }}">{{ $ultima->proxima_evaluacion->format('d/m/Y') }}</span>@else<span class="text-muted">Sin programar</span>@endif</td>
                    <td>{{ $abiertas }}</td><td>{{ ucfirst($proveedor->estado) }}</td><td><a class="btn btn-outline-primary btn-sm" href="{{ route('planificacion.proveedores.show',$proveedor) }}">Ver</a></td>
                </tr>
            @empty<tr><td colspan="9" class="text-center text-muted py-5">No hay proveedores registrados con estos filtros.</td></tr>@endforelse
            </tbody>
        </table></div>
    </div>

    <div class="iso-panel mt-4 provider-audit-panel">
        <div class="iso-panel-header provider-audit-header"><div><strong>Historial de bajas, reactivaciones y eliminaciones</strong><small class="d-block text-muted">Registro general independiente de los filtros de la tabla. Incluye fichas eliminadas.</small></div><button class="btn btn-outline-primary" data-toggle="collapse" data-target="#historialProveedores"><i class="fa-solid fa-clock-rotate-left mr-1"></i> Ver historial <span class="badge badge-light ml-1">{{ $historialCicloVida->count() }}</span></button></div>
        <div id="historialProveedores" class="collapse iso-panel-body"><p class="provider-audit-explanation">Este historial permite demostrar quién realizó cada cambio de estado, cuándo ocurrió y cuál fue el motivo informado.</p>@include('iso.proveedores._historial_ciclo',['mostrarProveedor'=>true])</div>
    </div>
</div>
@endsection
@push('styles')
<style>
.provider-form{background:#fbfcfe}.provider-form-section{padding:20px;border:1px solid #dde5ef;border-radius:12px;background:#fff;margin-bottom:16px}.provider-form-section-muted{background:#f7f9fc}.provider-form-section-title{display:flex;align-items:center;gap:12px;margin-bottom:18px}.provider-form-section-title>span{display:flex;align-items:center;justify-content:center;width:32px;height:32px;flex:0 0 32px;border-radius:9px;background:#eaf1ff;color:#2457e6;font-weight:800}.provider-form-section-title strong,.provider-form-section-title small{display:block}.provider-form-section-title small{color:#667085;margin-top:2px}.provider-form .form-label{display:block;width:100%;font-weight:700;color:#344054;margin:0 0 7px;line-height:1.35}.provider-form .form-control,.provider-form .form-select{display:block;width:100%;min-height:42px;border:1px solid #cbd5e1;border-radius:7px;background-color:#fff}.provider-form select.form-select{height:42px;padding:7px 34px 7px 12px}.provider-form .row>[class*="col-"]{margin-bottom:16px}.provider-form-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:2px}
.provider-audit-header{display:flex;align-items:center;justify-content:space-between;gap:18px}.provider-audit-explanation{padding:12px 14px;border-radius:8px;background:#eef4ff;color:#475467}.provider-lifecycle-history{display:flex;flex-direction:column}.provider-history-event{display:grid;grid-template-columns:38px minmax(0,1fr);gap:12px;position:relative;padding:14px 0}.provider-history-event:not(:last-child):before{content:'';position:absolute;left:18px;top:48px;bottom:-8px;width:2px;background:#dce4ee}.provider-history-marker{display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;background:#f1f5f9;color:#667085;z-index:1}.provider-history-event.is-baja .provider-history-marker{background:#fff4d6;color:#8a6500}.provider-history-event.is-reactivacion .provider-history-marker{background:#dcfce7;color:#166534}.provider-history-event.is-eliminacion .provider-history-marker{background:#fee2e2;color:#991b1b}.provider-history-content{padding:1px 0}.provider-history-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:16px}.provider-history-heading strong,.provider-history-type{display:block}.provider-history-heading time{white-space:nowrap;color:#667085;font-size:.84rem}.provider-history-type{text-transform:uppercase;letter-spacing:.05em;font-size:.68rem;font-weight:800;color:#667085;margin-bottom:2px}.provider-history-content p{margin:7px 0 2px;color:#344054}.provider-history-content small{color:#667085}@media(max-width:767px){.provider-audit-header,.provider-history-heading{align-items:flex-start;flex-direction:column}.provider-audit-header .btn{width:100%}}
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',function(){const area=document.getElementById('providerAreaSelect'),other=document.getElementById('providerOtherArea');if(!area||!other)return;const input=other.querySelector('input');const render=()=>{const visible=area.value==='__otro__';other.classList.toggle('d-none',!visible);input.required=visible;if(!visible)input.value='';};area.addEventListener('change',render);render();});
</script>
@endpush
