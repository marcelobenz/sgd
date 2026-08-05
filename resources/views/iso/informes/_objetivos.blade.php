<div class="iso-panel mb-4">
    <div class="iso-panel-header"><h2 class="iso-section-title mb-0">Objetivos de calidad, mediciones y seguimiento</h2></div>
    <div class="table-responsive"><table class="table iso-table mb-0">
        <thead><tr><th>Objetivo</th><th>Indicador y meta</th><th>Mediciones / resultado</th><th>Acciones y evidencias</th><th>Evaluaciones y revisiones</th></tr></thead>
        <tbody>
        @forelse($objetivos as $objetivo)
            @php
                $indicadorObjetivo=$objetivo->indicadorPrincipal;
                $simboloObjetivo=match($indicadorObjetivo?->comparador){'mayor_igual'=>'≥','mayor'=>'>','menor_igual'=>'≤','menor'=>'<','igual'=>'=','rango'=>'entre',default=>''};
                $formatoObjetivo=fn($valor)=>$valor===null?'Sin datos':rtrim(rtrim(number_format((float)$valor,4,',','.'),'0'),',');
            @endphp
            <tr>
                <td><a href="{{ route('planificacion.objetivos.show',$objetivo) }}"><strong>{{ $objetivo->codigo }} — {{ $objetivo->titulo }}</strong></a><div>{{ $objetivo->proceso }}</div><small>{{ $objetivo->area_responsable }} · {{ $objetivo->responsable?->name ?? 'Sin asignar' }} · {{ ucfirst($objetivo->estado) }}</small><div class="mt-1">{{ $objetivo->fecha_inicio->format('d/m/Y') }} al {{ $objetivo->fecha_objetivo->format('d/m/Y') }}</div></td>
                <td><strong>{{ $indicadorObjetivo?->nombre ?? 'Sin indicador' }}</strong>@if($indicadorObjetivo)<div>{{ $indicadorObjetivo->metodo_calculo }}</div><div class="mt-1"><strong>Meta:</strong> {{ $simboloObjetivo }} {{ $formatoObjetivo($indicadorObjetivo->meta) }}@if($indicadorObjetivo->comparador==='rango') y {{ $formatoObjetivo($indicadorObjetivo->meta_hasta) }}@endif {{ $indicadorObjetivo->unidad }}</div><small>Fuente: {{ $indicadorObjetivo->fuente }} · {{ $indicadorObjetivo->frecuencia }}</small>@endif</td>
                <td>
                    @if($indicadorObjetivo)
                        @forelse($indicadorObjetivo->mediciones->sortBy('fecha_medicion') as $medicion)
                            <div><strong>{{ $medicion->fecha_medicion->format('d/m/Y') }}:</strong> {{ $formatoObjetivo($medicion->valor) }} {{ $indicadorObjetivo->unidad }}@if($medicion->periodo_referencia) · {{ $medicion->periodo_referencia }}@endif</div>
                            @if($medicion->observaciones)<small>{{ $medicion->observaciones }}</small>@endif
                            @if($medicion->documento)<div><small>@if($documentosAccesibles->contains($medicion->documento_id))<a href="{{ route('documentos.validaPermiso',['id'=>$medicion->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">Doc. SGD: {{ $medicion->documento->titulo }}</a>@else Documento interno vinculado — sin permiso @endif</small></div>@endif
                            @if($medicion->enlace_externo)<div><small><a href="{{ $medicion->enlace_externo }}" target="_blank" rel="noopener">Evidencia externa</a></small></div>@endif
                        @empty<span class="text-muted">Sin mediciones</span>@endforelse
                        <div class="mt-2"><strong>Resultado actual:</strong> {{ $formatoObjetivo($objetivo->resultado_actual) }} {{ $objetivo->resultado_actual!==null?$indicadorObjetivo->unidad:'' }} · {{ ucfirst($objetivo->cumplimiento_actual) }}</div>
                    @endif
                </td>
                <td>
                    @forelse($objetivo->acciones as $accion)
                        <div class="mb-2"><strong>{{ $accion->descripcion }}</strong> — {{ ucfirst(str_replace('_',' ',$accion->estado)) }}<br><small>{{ $accion->area_responsable }} · {{ $accion->responsable?->name ?? 'Sin asignar' }} · Objetivo {{ $accion->fecha_objetivo->format('d/m/Y') }}</small>@if($accion->resultado)<div>Resultado: {{ $accion->resultado }}</div>@endif
                        @if($accion->documento)<div><small>@if($documentosAccesibles->contains($accion->documento_id))<a href="{{ route('documentos.validaPermiso',['id'=>$accion->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">Doc. SGD: {{ $accion->documento->titulo }}</a>@else Documento interno vinculado — sin permiso @endif</small></div>@endif
                        @foreach($accion->seguimientos as $seguimiento)<div><small>{{ $seguimiento->fecha->format('d/m/Y') }}: {{ $seguimiento->detalle }}@if($seguimiento->documento) · @if($documentosAccesibles->contains($seguimiento->documento_id))<a href="{{ route('documentos.validaPermiso',['id'=>$seguimiento->documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">{{ $seguimiento->documento->titulo }}</a>@else Documento sin permiso @endif @endif</small></div>@endforeach</div>
                    @empty<span class="text-muted">Sin acciones</span>@endforelse
                </td>
                <td>
                    @forelse($objetivo->evaluaciones as $evaluacion)<div class="mb-2"><strong>{{ $evaluacion->fecha_evaluacion->format('d/m/Y') }} — {{ ucfirst($evaluacion->cumplimiento) }}</strong><div>Resultado {{ $formatoObjetivo($evaluacion->resultado) }} · Decisión: {{ ucfirst(str_replace('_',' ',$evaluacion->decision)) }}</div><div>{{ $evaluacion->conclusion }}</div>@if($evaluacion->justificacion)<small>Justificación: {{ $evaluacion->justificacion }}</small>@endif</div>@empty<span class="text-muted">Sin evaluaciones</span>@endforelse
                    @if($objetivo->revisiones->isNotEmpty())<hr><strong>Cambios de planificación</strong>@foreach($objetivo->revisiones as $revision)<div><small>{{ $revision->fecha_vigencia->format('d/m/Y') }} · {{ $revision->tipo==='correccion'?'Corrección':'Revisión' }} · {{ $revision->motivo }} · {{ $revision->realizadaPor?->name ?? 'Usuario no disponible' }}</small></div>@endforeach @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No hay objetivos de calidad registrados para este período.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
