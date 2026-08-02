@extends('layouts.main')
@include('iso._styles')
@section('heading','Riesgos y oportunidades')
@section('contenidoPrincipal')
@php
    $ordenActual = request('orden');
    $direccionActual = request('direccion', 'asc');
    $ordenUrl = function ($campo) use ($ordenActual, $direccionActual) {
        $direccion = $ordenActual === $campo && $direccionActual === 'asc' ? 'desc' : 'asc';
        return request()->fullUrlWithQuery(['orden' => $campo, 'direccion' => $direccion]);
    };
    $ordenIcono = fn ($campo) => $ordenActual === $campo ? ($direccionActual === 'asc' ? ' ↑' : ' ↓') : '';
@endphp
<div class="iso-shell">
@include('iso._alerts')
<div class="iso-eyebrow">Planificación y tratamiento</div><h1 class="iso-title">Riesgos y oportunidades</h1><p class="iso-subtitle">Priorizá, tratá y verificá la eficacia de cada registro.</p>
<div class="iso-toolbar"><form method="GET" class="form-inline"><select name="periodo" class="form-control mr-2" onchange="this.form.submit()">@foreach($periodos as $p)<option value="{{ $p->id }}" @selected($periodo?->id===$p->id)>{{ $p->anio }}</option>@endforeach</select><select name="tipo" class="form-control mr-2" onchange="this.form.submit()"><option value="">Riesgos y oportunidades</option><option value="riesgo" @selected(request('tipo')==='riesgo')>Riesgos</option><option value="oportunidad" @selected(request('tipo')==='oportunidad')>Oportunidades</option></select><select name="estado" class="form-control mr-2" onchange="this.form.submit()"><option value="">Todos los estados</option>@foreach(['pendiente','en_proceso','permanente','finalizado','anulado'] as $e)<option value="{{ $e }}" @selected(request('estado')===$e)>{{ ucfirst(str_replace('_',' ',$e)) }}</option>@endforeach</select>@if(request()->boolean('verificacion_vencida'))<input type="hidden" name="verificacion_vencida" value="1"><a href="{{ route('planificacion.riesgos.index',['periodo'=>$periodo?->id]) }}" class="btn btn-light">Quitar filtro de vencidas</a>@endif</form>@if($periodo && auth()->user()->puedeGestionarPlanificacion())<a class="btn btn-primary" href="{{ route('planificacion.riesgos.create') }}"><i class="fa-solid fa-plus mr-1"></i> Nuevo registro</a>@endif</div>
<div class="iso-panel"><div class="table-responsive"><table class="table iso-table mb-0"><thead><tr>
@foreach(['codigo'=>'Código','tipo'=>'Tipo','identificacion'=>'Identificación','proceso'=>'Proceso','indice'=>'Índice','acciones'=>'Acciones','verificacion'=>'Evaluación de eficacia','estado'=>'Estado'] as $campo=>$titulo)<th><a href="{{ $ordenUrl($campo) }}" class="text-reset text-decoration-none">{{ $titulo }}{{ $ordenIcono($campo) }}</a></th>@endforeach<th></th>
</tr></thead><tbody>
@forelse($riesgos as $r)
@php
    $registroFinalizado = $r->estado === 'finalizado';
    $evaluacionVencida = !$registroFinalizado && $r->fecha_verificacion_prevista?->lte(today());
    $evaluacionProxima = !$registroFinalizado && !$evaluacionVencida && $r->fecha_verificacion_prevista?->lte(today()->addDays(30));
    $resultadoEficacia = match($r->eficacia) {
        'si' => ['Eficaz', 'ok'],
        'parcial' => ['Parcialmente eficaz', 'warn'],
        'no' => ['No eficaz', 'danger'],
        default => ['Pendiente de evaluación', ''],
    };
@endphp
<tr><td class="iso-code">{{ $r->codigo }}</td><td>{{ ucfirst($r->tipo) }}</td><td>{{ Str::limit($r->identificacion,120) }}@if($r->contexto)<small class="d-block text-muted">{{ $r->contexto->codigo }} · {{ $r->contexto->titulo }}</small>@endif</td><td>{{ $r->proceso }}</td><td><span class="iso-status {{ $r->indice_inicial>6?'danger':($r->indice_inicial>=3?'warn':'ok') }}">{{ $r->indice_inicial }}</span></td><td>{{ $r->acciones_abiertas_count }} abiertas</td><td><div>@if($registroFinalizado)<span class="iso-status ok">Finalizado</span>@elseif(!$r->fecha_verificacion_prevista)<span class="text-muted">Sin programar</span>@else{{ $r->fecha_verificacion_prevista->format('d/m/Y') }} <span class="iso-status {{ $evaluacionVencida?'danger':($evaluacionProxima?'warn':'') }}">{{ $evaluacionVencida?'Vencida':($evaluacionProxima?'Próxima':'Programada') }}</span>@endif</div><div class="mt-1"><span class="iso-status {{ $resultadoEficacia[1] }}">{{ $resultadoEficacia[0] }}</span></div></td><td>{{ ucfirst(str_replace('_',' ',$r->estado)) }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('planificacion.riesgos.show',$r) }}">Ver</a></td></tr>
@empty<tr><td colspan="9" class="text-center text-muted py-4">No hay registros para los filtros seleccionados.</td></tr>@endforelse
</tbody></table></div></div>
</div>
@endsection
