<div class="iso-panel mb-4">
    <div class="iso-panel-header"><h2 class="iso-section-title mb-0">Contexto y cambio clim&aacute;tico</h2></div>
    <div class="iso-panel-body">
        <p><strong>Cambio clim&aacute;tico relevante para el SGC:</strong> @if(is_null($periodo->cambio_climatico_relevante)) Pendiente @elseif($periodo->cambio_climatico_relevante) S&iacute; @else No @endif</p>
        <p>{{ $periodo->fundamento_cambio_climatico ?: 'Sin fundamento registrado.' }}</p>
        <div class="table-responsive"><table class="table iso-table mb-0"><thead><tr><th>C&oacute;digo</th><th>Tipo</th><th>Elemento</th><th>Decisi&oacute;n</th><th>Tratamiento derivado</th></tr></thead><tbody>
        @forelse($periodo->contextos as $contexto)<tr><td>{{ $contexto->codigo }}</td><td>{{ ucfirst($contexto->tipo) }}</td><td><strong>{{ $contexto->titulo }}</strong><div>{{ $contexto->descripcion }}</div></td><td>{{ ucfirst(str_replace('_',' ',$contexto->decision)) }}</td><td>@forelse($contexto->riesgos as $riesgo)<a href="{{ route('planificacion.riesgos.show',$riesgo) }}">{{ $riesgo->codigo }}</a> &middot; {{ ucfirst(str_replace('_',' ',$riesgo->estado)) }}@if(!$loop->last)<br>@endif @empty Sin tratamiento derivado @endforelse</td></tr>@empty<tr><td colspan="5">Sin elementos FODA.</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>

<div class="iso-panel mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Riesgos y oportunidades</h2></div><div class="table-responsive"><table class="table iso-table mb-0"><thead><tr><th>Registro</th><th>Proceso / responsable</th><th>Valoraci&oacute;n</th><th>Eficacia</th><th>Estado / pr&oacute;xima revisi&oacute;n</th></tr></thead><tbody>
@forelse($periodo->riesgos as $riesgo)
    <tr><td><a href="{{ route('planificacion.riesgos.show',$riesgo) }}"><strong>{{ $riesgo->codigo }}</strong></a> &middot; {{ ucfirst($riesgo->tipo) }}<div>{{ $riesgo->identificacion }}</div></td><td>{{ $riesgo->proceso }}<div>{{ $riesgo->responsable?->name ?? 'Sin asignar' }}</div></td><td>Inicial: {{ $riesgo->impacto_inicial }} &times; {{ $riesgo->probabilidad_inicial }} = {{ $riesgo->indice_inicial }}@if($riesgo->indice_final)<div>Actual/final: {{ $riesgo->impacto_final }} &times; {{ $riesgo->probabilidad_final }} = {{ $riesgo->indice_final }}</div>@endif</td><td>{{ ucfirst($riesgo->eficacia) }}<div>{{ $riesgo->conclusion_eficacia }}</div></td><td>{{ ucfirst(str_replace('_',' ',$riesgo->estado)) }}<div>@if($riesgo->fecha_verificacion_prevista){{ $riesgo->fecha_verificacion_prevista->format('d/m/Y') }}@else Sin pr&oacute;xima revisi&oacute;n @endif</div></td></tr>
@empty<tr><td colspan="5">Sin riesgos u oportunidades.</td></tr>@endforelse
</tbody></table></div></div>

@include('iso.informes._objetivos_resumidos')

<div class="iso-panel mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Partes interesadas</h2></div><div class="table-responsive"><table class="table iso-table mb-0"><thead><tr><th>Parte</th><th>Necesidades / requisitos</th><th>Evaluaci&oacute;n del per&iacute;odo</th><th>Pr&oacute;xima revisi&oacute;n</th></tr></thead><tbody>
@forelse($partes as $parte)@php($evaluacion=$parte->evaluaciones->first())<tr><td><strong>{{ $parte->nombre }}</strong><div>{{ $parte->area_responsable ?: 'Sin area definida' }}</div></td><td>{{ $parte->necesidades_requisitos }}</td><td>@if($evaluacion)<strong>{{ $evaluacion->fecha_evaluacion->format('d/m/Y') }} &middot; {{ ucfirst(str_replace('_',' ',$evaluacion->resultado)) }}</strong><div>{{ $evaluacion->observaciones }}</div>@else<span class="report-status warn">Pendiente en {{ $periodo->anio }}</span>@endif</td><td>@if($evaluacion?->proxima_revision){{ $evaluacion->proxima_revision->format('d/m/Y') }}@else Sin programar @endif</td></tr>@empty<tr><td colspan="4">Sin partes interesadas aplicables.</td></tr>@endforelse
</tbody></table></div></div>

<div class="iso-panel mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Proveedores externos</h2></div><div class="table-responsive"><table class="table iso-table mb-0"><thead><tr><th>Proveedor / prestaci&oacute;n</th><th>Criticidad</th><th>Evaluaci&oacute;n del per&iacute;odo</th><th>Decisi&oacute;n</th><th>Pr&oacute;xima evaluaci&oacute;n</th></tr></thead><tbody>
@forelse($proveedores as $proveedor)
    @php($evaluacion=$proveedor->evaluaciones->first())
    <tr><td><strong>{{ $proveedor->nombre }}</strong><div>{{ $proveedor->producto_servicio }}</div></td><td>@if($proveedor->criticidad==='critico') Cr&iacute;tico @else No cr&iacute;tico @endif</td><td>@if($evaluacion)<strong>{{ $evaluacion->fecha_evaluacion->format('d/m/Y') }} &middot; {{ ucfirst(str_replace('_',' ',$evaluacion->resultado)) }}</strong><div>Puntaje {{ number_format($evaluacion->puntaje,2,',','.') }}</div>@else<span class="report-status warn">Pendiente en {{ $periodo->anio }}</span>@endif</td><td>@if($evaluacion){{ ucfirst(str_replace('_',' ',$evaluacion->decision)) }}@else &mdash; @endif</td><td>@if($evaluacion?->proxima_evaluacion){{ $evaluacion->proxima_evaluacion->format('d/m/Y') }}@else Sin programar @endif</td></tr>
@empty<tr><td colspan="5">Sin proveedores aplicables.</td></tr>@endforelse
</tbody></table></div></div>
