<div class="report-summary-grid mb-3">
    <div class="report-kpi"><strong>{{ $resumen['foda'] }}</strong><small>Elementos FODA</small></div>
    <div class="report-kpi"><strong>{{ $resumen['riesgos'] }} / {{ $resumen['oportunidades'] }}</strong><small>Riesgos / oportunidades</small></div>
    <div class="report-kpi"><strong>{{ $resumen['acciones_abiertas'] }}</strong><small>Acciones abiertas</small></div>
    <div class="report-kpi"><strong>{{ $resumen['objetivos'] }}</strong><small>Objetivos de calidad</small></div>
    <div class="report-kpi"><strong>{{ $resumen['partes'] }}</strong><small>Partes interesadas</small></div>
    <div class="report-kpi"><strong>{{ $resumen['proveedores'] }}</strong><small>Proveedores aplicables</small></div>
</div>
<div class="report-alert {{ $resumen['pendientes']->isEmpty() ? 'report-ok' : '' }} mb-4">
    <strong>{{ $resumen['pendientes']->isEmpty() ? 'Sin asuntos pendientes detectados' : 'Asuntos pendientes al generar el informe' }}</strong>
    @if($resumen['pendientes']->isNotEmpty())<ul class="mb-0 mt-2">@foreach($resumen['pendientes'] as $pendiente)<li>{!! $pendiente !!}</li>@endforeach</ul>@else<div>Los controles incluidos en este informe no registran pendientes para el per&iacute;odo.</div>@endif
</div>
