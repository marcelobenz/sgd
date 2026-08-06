@extends('layouts.main')
@include('iso._styles')
@section('heading','Períodos y parámetros')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Administración</div>
    <h1 class="iso-title">Períodos y parámetros</h1>
    <p class="iso-subtitle">Un solo período puede estar vigente. El cierre y la reapertura requieren controles, motivo y confirmación explícita.</p>

    <div class="row">
        <div class="col-lg-8">
            <div class="iso-panel">
                <div class="iso-panel-header"><h2 class="iso-section-title mb-0">Períodos</h2></div>
                <div class="table-responsive">
                    <table class="table iso-table mb-0">
                        <thead><tr><th>Año</th><th>Estado</th><th>FODA</th><th>Riesgos</th><th>Clima</th><th></th></tr></thead>
                        <tbody>
                        @foreach($periodos as $p)
                            @php($puedeCerrar = $p->contextos_pendientes_count===0 && $p->riesgos_abiertos_count===0 && $p->permanentes_sin_control_count===0 && $p->acciones_abiertas_count===0 && $p->objetivos_sin_cierre_count===0 && $p->objetivo_acciones_abiertas_count===0 && !is_null($p->cambio_climatico_relevante) && filled($p->fundamento_cambio_climatico))
                            <tr>
                                <td><strong>{{ $p->anio }}</strong></td>
                                <td>{{ ucfirst($p->estado) }}</td>
                                <td>{{ $p->contextos_count }} @if($p->contextos_pendientes_count)<span class="badge badge-warning">{{ $p->contextos_pendientes_count }} pendientes</span>@endif</td>
                                <td>{{ $p->riesgos_count }}</td>
                                <td>{{ is_null($p->cambio_climatico_relevante)?'Pendiente':($p->cambio_climatico_relevante?'Relevante':'No relevante') }}</td>
                                <td class="text-right text-nowrap">
                                    @if($p->estado==='borrador')
                                        <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#activarPeriodo{{ $p->id }}">Activar</button>
                                    @endif
                                    @if($p->estado!=='cerrado')
                                        <button class="btn btn-sm btn-outline-danger" data-toggle="modal" data-target="#cerrarPeriodo{{ $p->id }}" @disabled(!$puedeCerrar)>Cerrar</button>
                                    @else
                                        <button class="btn btn-sm btn-outline-primary" data-toggle="modal" data-target="#reabrirPeriodo{{ $p->id }}">Reabrir</button>
                                    @endif
                                </td>
                            </tr>
                            @if($p->estado!=='cerrado' && !$puedeCerrar)
                                <tr><td colspan="6" class="pt-0 border-top-0"><small class="text-danger"><strong>No puede cerrarse:</strong>
                                    @php($motivos = collect())
                                    @if($p->contextos_pendientes_count) @php($motivos->push($p->contextos_pendientes_count.' FODA pendientes')) @endif
                                    @if($p->riesgos_abiertos_count) @php($motivos->push($p->riesgos_abiertos_count.' riesgos pendientes o en proceso')) @endif
                                    @if($p->permanentes_sin_control_count) @php($motivos->push($p->permanentes_sin_control_count.' permanentes sin evaluación vigente o próxima revisión')) @endif
                                    @if($p->acciones_abiertas_count) @php($motivos->push($p->acciones_abiertas_count.' acciones abiertas')) @endif
                                    @if($p->objetivos_sin_cierre_count) @php($motivos->push($p->objetivos_sin_cierre_count.' objetivos sin evaluación de cierre')) @endif
                                    @if($p->objetivo_acciones_abiertas_count) @php($motivos->push($p->objetivo_acciones_abiertas_count.' acciones de objetivos abiertas')) @endif
                                    @if(is_null($p->cambio_climatico_relevante) || blank($p->fundamento_cambio_climatico)) @php($motivos->push('evaluación climática incompleta')) @endif
                                    {{ $motivos->implode(', ') }}.
                                </small></td></tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="iso-panel mt-4">
                <div class="iso-panel-header"><h2 class="iso-section-title mb-0">Historial de cierres y reaperturas</h2></div>
                <div class="table-responsive"><table class="table iso-table mb-0"><thead><tr><th>Fecha</th><th>Período</th><th>Acción</th><th>Responsable</th><th>Motivo</th></tr></thead><tbody>
                    @forelse($periodos->flatMap->transiciones->sortByDesc('created_at') as $transicion)
                        <tr><td>{{ $transicion->created_at->format('d/m/Y H:i') }}</td><td>{{ $transicion->periodo?->anio ?? $periodos->firstWhere('id',$transicion->periodo_id)?->anio }}</td><td>{{ ucfirst($transicion->accion) }}</td><td>{{ $transicion->realizadoPor?->name }}</td><td>{{ $transicion->motivo }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Todavía no hay cierres o reaperturas auditados.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="iso-panel mb-4"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Nuevo período</h2></div><form method="POST" action="{{ route('planificacion.periodos.store') }}" class="iso-panel-body">@csrf<div class="form-group"><label>Año</label><input type="number" name="anio" value="{{ now()->year }}" class="form-control" required></div><div class="form-group"><label>Nombre</label><input name="nombre" class="form-control" placeholder="Planificación anual"></div><button class="btn btn-primary">Crear</button></form></div>
            @if($vigente=$periodos->firstWhere('estado','vigente'))
                <div class="iso-panel"><div class="iso-panel-header"><h2 class="iso-section-title mb-0">Cambio climático {{ $vigente->anio }}</h2></div><form method="POST" action="{{ route('planificacion.periodos.clima',$vigente) }}" class="iso-panel-body">@csrf @method('PATCH')<div class="form-group"><label>¿Es relevante para el SGC?</label><select name="cambio_climatico_relevante" class="form-control" required><option value="1" @selected($vigente->cambio_climatico_relevante===true)>Sí</option><option value="0" @selected($vigente->cambio_climatico_relevante===false)>No</option></select></div><div class="form-group"><label>Fundamento</label><textarea name="fundamento_cambio_climatico" class="form-control" rows="5" required>{{ $vigente->fundamento_cambio_climatico }}</textarea></div><button class="btn btn-primary">Guardar evaluación</button></form></div>
            @endif
        </div>
    </div>
</div>

@foreach($periodos as $p)
    @php($puedeCerrar = $p->contextos_pendientes_count===0 && $p->riesgos_abiertos_count===0 && $p->permanentes_sin_control_count===0 && $p->acciones_abiertas_count===0 && $p->objetivos_sin_cierre_count===0 && $p->objetivo_acciones_abiertas_count===0 && !is_null($p->cambio_climatico_relevante) && filled($p->fundamento_cambio_climatico))
    @if($p->estado==='borrador')
    <div class="modal fade" id="activarPeriodo{{ $p->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><form method="POST" action="{{ route('planificacion.periodos.activar',$p) }}" class="modal-content">@csrf @method('PATCH')
        <div class="modal-header bg-light"><div><h5 class="modal-title">Activar período {{ $p->anio }}</h5><small>El período pasará a ser el ejercicio operativo predeterminado.</small></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <div class="alert alert-warning"><strong>Impacto:</strong> el período vigente actual, si existe, pasará a Borrador. Las nuevas pantallas seleccionarán {{ $p->anio }} por defecto. Para una corrección histórica puntual no es necesario activarlo.</div>
            <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="trasladarPermanentes{{ $p->id }}" name="trasladar_permanentes" value="1" checked><label class="custom-control-label" for="trasladarPermanentes{{ $p->id }}"><strong>Dar continuidad a los riesgos permanentes controlados.</strong> Se crearán registros vinculados en {{ $p->anio }}, sin duplicar acciones anteriores y conservando la trazabilidad con el riesgo de origen.</label></div>
            <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="trasladarObjetivos{{ $p->id }}" name="trasladar_objetivos" value="1" checked><label class="custom-control-label" for="trasladarObjetivos{{ $p->id }}"><strong>Continuar los objetivos evaluados con decisión de continuar o reformular.</strong> Se copiará su planificación e indicador, pero no las mediciones, acciones ni evaluaciones del período anterior.</label></div>
            <div class="form-group"><label>Motivo de la activación *</label><textarea name="motivo" class="form-control" rows="3" required minlength="10"></textarea></div>
            <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="comprendeActivacion{{ $p->id }}" name="comprende_impacto" value="1" required><label class="custom-control-label" for="comprendeActivacion{{ $p->id }}">Comprendo que se modificará el período operativo predeterminado.</label></div>
            <div class="form-group"><label>Escribí <strong>ACTIVAR {{ $p->anio }}</strong> para confirmar *</label><input name="confirmacion" class="form-control" required autocomplete="off"></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Confirmar activación</button></div>
    </form></div></div>
    @endif
    @if($p->estado!=='cerrado')
    <div class="modal fade" id="cerrarPeriodo{{ $p->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><form method="POST" action="{{ route('planificacion.periodos.cerrar',$p) }}" class="modal-content">@csrf @method('PATCH')
        <div class="modal-header bg-light"><div><h5 class="modal-title text-danger"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Cerrar período {{ $p->anio }}</h5><small>Acción crítica con impacto sobre la edición de la planificación.</small></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <div class="alert alert-warning"><strong>Al cerrar el período:</strong> toda la planificación anual quedará disponible solamente para consulta. No podrán agregarse acciones, seguimientos, evidencias, mediciones ni evaluaciones. Para corregirla deberá reabrirse expresamente como Borrador.</div>
            <h6>Control previo</h6><div class="row text-center mb-3"><div class="col"><strong>{{ $p->contextos_pendientes_count }}</strong><br><small>FODA pendientes</small></div><div class="col"><strong>{{ $p->riesgos_abiertos_count }}</strong><br><small>Riesgos pendientes/en proceso</small></div><div class="col"><strong>{{ $p->permanentes_sin_control_count }}</strong><br><small>Permanentes sin control vigente</small></div><div class="col"><strong>{{ $p->acciones_abiertas_count }}</strong><br><small>Acciones de riesgos abiertas</small></div><div class="col"><strong>{{ $p->objetivos_sin_cierre_count }}</strong><br><small>Objetivos sin evaluación de cierre</small></div><div class="col"><strong>{{ $p->objetivo_acciones_abiertas_count }}</strong><br><small>Acciones de objetivos abiertas</small></div></div>
            @if(!$puedeCerrar)<p class="alert alert-danger"><strong>El cierre está bloqueado.</strong> Resolvé los pendientes indicados y completá la evaluación climática antes de continuar.</p>@endif
            <div class="form-group"><label>Motivo del cierre *</label><textarea name="motivo" class="form-control" rows="3" required minlength="10" placeholder="Explicá por qué se considera concluida la planificación del período"></textarea></div>
            <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="comprendeCierre{{ $p->id }}" name="comprende_impacto" value="1" required><label class="custom-control-label" for="comprendeCierre{{ $p->id }}">Comprendo el impacto y confirmé que la información del período está lista para su cierre.</label></div>
            <div class="form-group"><label>Escribí <strong>CERRAR {{ $p->anio }}</strong> para confirmar *</label><input name="confirmacion" class="form-control" required autocomplete="off"></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-danger">Confirmar cierre</button></div>
    </form></div></div>
    @else
    <div class="modal fade" id="reabrirPeriodo{{ $p->id }}" tabindex="-1" role="dialog" aria-hidden="true"><div class="modal-dialog modal-lg" role="document"><form method="POST" action="{{ route('planificacion.periodos.reabrir',$p) }}" class="modal-content">@csrf @method('PATCH')
        <div class="modal-header bg-light"><div><h5 class="modal-title"><i class="fa-solid fa-lock-open mr-2"></i>Reabrir período {{ $p->anio }}</h5><small>La reapertura modifica el estado formal de una planificación cerrada.</small></div><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <div class="modal-body">
            <div class="alert alert-info"><strong>Al reabrir el período:</strong> toda su planificación volverá a ser editable y quedará como <strong>Borrador</strong>. No reemplazará al período vigente ni será elegido por defecto. Seleccionalo expresamente para realizar la corrección y volvelo a cerrar al finalizar.</div>
            <div class="form-group"><label>Motivo de la reapertura *</label><textarea name="motivo" class="form-control" rows="3" required minlength="10" placeholder="Explicá qué corrección, actualización o tarea requiere reabrir el período"></textarea></div>
            <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="comprendeReapertura{{ $p->id }}" name="comprende_impacto" value="1" required><label class="custom-control-label" for="comprendeReapertura{{ $p->id }}">Comprendo que se reabrirá una planificación formalmente cerrada y que el cambio quedará auditado.</label></div>
            <div class="form-group"><label>Escribí <strong>REABRIR {{ $p->anio }}</strong> para confirmar *</label><input name="confirmacion" class="form-control" required autocomplete="off"></div>
        </div><div class="modal-footer"><button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button><button class="btn btn-primary">Reabrir como borrador</button></div>
    </form></div></div>
    @endif
@endforeach
@endsection
