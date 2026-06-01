@extends('layouts.main')

@section('heading')
    Recordatorios
@endsection

@section('contenidoPrincipal')
    <div class="container" style="margin-top: 80px;">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="d-flex justify-content-between align-items-center mb-3 p-3 rounded"
            style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
            <div>
                <h3 class="mb-1">Recordatorios</h3>
                <small class="text-muted">Vista operativa de tus tareas pendientes y resueltas</small>
            </div>

            <div class="btn-group">
                <a href="{{ route('recordatorios.mis') }}"
                    class="btn btn-sm {{ request()->routeIs('recordatorios.mis') ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fa fa-list mr-1"></i> Lista
                </a>

                <a href="{{ route('recordatorios.calendario') }}"
                    class="btn btn-sm {{ request()->routeIs('recordatorios.calendario') ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fa fa-calendar mr-1"></i> Calendario
                </a>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small mb-1">Vencidos</div>
                        <div class="font-weight-bold" style="font-size: 2rem; color: #dc3545;">
                            {{ $cantidadVencidos }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small mb-1">Para hoy</div>
                        <div class="font-weight-bold" style="font-size: 2rem; color: #ffc107;">
                            {{ $cantidadHoy }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small mb-1">Próximos 7 días</div>
                        <div class="font-weight-bold" style="font-size: 2rem; color: #17a2b8;">
                            {{ $cantidadProximos7 }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th style="min-width: 190px;">Documento</th>
                                <th style="min-width: 220px;">Recordatorio</th>
                                <th style="min-width: 150px;">Fecha</th>
                                <th style="min-width: 120px;">Estado</th>
                                <th style="min-width: 180px;">Observación</th>
                                <th style="width: 1%; white-space: nowrap;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ejecuciones as $ejecucion)
                                @php
                                    $fechaReferencia = $ejecucion->postergado_hasta ?? $ejecucion->fecha_programada;
                                    $estaVencido = $fechaReferencia < now();
                                    $esHoy = $fechaReferencia->isSameDay(now());
                                @endphp

                                <tr
                                    style="
                                        {{ $estaVencido ? 'background-color: #fff5f5;' : '' }}
                                        {{ !$estaVencido && $esHoy ? 'background-color: #fffdf0;' : '' }}
                                    ">
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-dark">
                                            {{ $ejecucion->documento->titulo ?? 'Sin documento' }}
                                        </div>
                                    </td>

                                    <td class="align-middle">
                                        <div class="font-weight-500 text-dark">
                                            {{ $ejecucion->recordatorio->nombre ?? '-' }}
                                        </div>

                                        @if (!empty($ejecucion->recordatorio?->mensaje))
                                            <small class="text-muted d-block mt-1">
                                                {{ $ejecucion->recordatorio->mensaje }}
                                            </small>
                                        @endif
                                    </td>

                                    <td class="align-middle">
                                        <div>{{ $fechaReferencia->format('d/m/Y H:i') }}</div>

                                        @if ($ejecucion->postergado_hasta)
                                            <small class="text-muted d-block mt-1">Postergado</small>
                                        @endif
                                    </td>

                                    <td class="align-middle">
                                        @if ($ejecucion->estado === 'pendiente')
                                            <span class="badge badge-pill px-3 py-2"
                                                style="background-color: #007bff; color: white;">Pendiente</span>
                                        @elseif ($ejecucion->estado === 'postergado')
                                            <span class="badge badge-pill px-3 py-2"
                                                style="background-color: #17a2b8; color: white;">Postergado</span>
                                        @elseif ($ejecucion->estado === 'resuelto')
                                            <span class="badge badge-pill px-3 py-2"
                                                style="background-color: #28a745; color: white;">Resuelto</span>
                                        @elseif ($ejecucion->estado === 'vencido')
                                            <span class="badge badge-pill px-3 py-2"
                                                style="background-color: #dc3545; color: white;">Vencido</span>
                                        @else
                                            <span class="badge badge-pill px-3 py-2 badge-secondary">
                                                {{ $ejecucion->estado }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="align-middle">
                                        <span class="text-muted">
                                            {{ $ejecucion->observacion ?: '-' }}
                                        </span>
                                    </td>

                                    <td class="align-middle text-nowrap">
                                        <div class="d-inline-flex align-items-center" style="gap: 6px;">
                                            @if ($ejecucion->documento)
                                                <a href="{{ route('documentos.validaPermiso', [
                                                    'id' => $ejecucion->documento->id,
                                                    'ruta' => 'documentos.show',
                                                    'permiso' => 'puedeLeer',
                                                ]) }}"
                                                    class="btn btn-sm btn-outline-primary" data-toggle="tooltip"
                                                    title="Ver">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                            @endif

                                            <button type="button" class="btn btn-sm btn-outline-success"
                                                data-toggle="modal" data-target="#modalResolver{{ $ejecucion->id }}"
                                                title="Resolver">
                                                <i class="fa fa-check"></i>
                                            </button>

                                            <button type="button" class="btn btn-sm btn-outline-warning"
                                                data-toggle="modal" data-target="#modalPostergar{{ $ejecucion->id }}"
                                                title="Postergar">
                                                <i class="fa fa-clock"></i>
                                            </button>

                                            <button type="button" class="btn btn-sm btn-outline-danger" data-toggle="modal"
                                                data-target="#modalEliminar{{ $ejecucion->id }}" title="Eliminar">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>

                                        <div class="modal fade" id="modalPostergar{{ $ejecucion->id }}" tabindex="-1"
                                            role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content border-0 shadow">
                                                    <form
                                                        action="{{ route('recordatorios.ejecuciones.postergar', $ejecucion->id) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PATCH')

                                                        <div class="modal-header bg-light">
                                                            <h5 class="modal-title">
                                                                <i class="fa fa-clock mr-2"></i>Postergar recordatorio
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Cerrar">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <div class="form-group">
                                                                <label>Nueva fecha</label>
                                                                <input type="date" name="postergado_hasta"
                                                                    class="form-control" required>
                                                            </div>

                                                            <div class="form-group mb-0">
                                                                <label>Observación</label>
                                                                <textarea name="observacion" class="form-control" rows="3" placeholder="Motivo de la postergación (opcional)"></textarea>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-warning">
                                                                Guardar
                                                            </button>
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">
                                                                Cancelar
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="modalResolver{{ $ejecucion->id }}" tabindex="-1"
                                            role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content border-0 shadow">
                                                    <form
                                                        action="{{ route('recordatorioEjecuciones.resolverConRevision', $ejecucion->id) }}"
                                                        method="POST" enctype="multipart/form-data">
                                                        @csrf

                                                        <div class="modal-header bg-light">
                                                            <h5 class="modal-title">
                                                                <i class="fa fa-check mr-2"></i>Registrar revisión
                                                            </h5>
                                                            <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Cerrar">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>

                                                        <div class="modal-body">
                                                            <p class="mb-2">
                                                                <strong>Documento:</strong>
                                                                {{ $ejecucion->documento->titulo ?? 'Sin documento' }}
                                                            </p>

                                                            <p class="mb-3">
                                                                <strong>Recordatorio:</strong>
                                                                {{ $ejecucion->recordatorio->nombre ?? '-' }}
                                                            </p>

                                                            <div class="form-group">
                                                                <label>Resultado de la revisión</label>
                                                                <select name="resultado" class="form-control" required>
                                                                    <option value="conforme">Conforme - no requiere cambios
                                                                    </option>
                                                                    <option value="requiere_nueva_version">Requiere nueva
                                                                        versión</option>
                                                                    <option value="no_aplica">No aplica</option>
                                                                </select>
                                                            </div>

                                                            <div class="form-group">
                                                                <label>Constancia de revisión</label>
                                                                <textarea name="observacion_resolucion" class="form-control" rows="4" required
                                                                    placeholder="Ej: Se revisó el documento, continúa vigente y no requiere cambios."></textarea>
                                                            </div>

                                                            <div class="form-group mb-0">
                                                                <label>Evidencia adjunta opcional</label>
                                                                <input type="file" name="archivo_evidencia"
                                                                    class="form-control-file">
                                                                <small class="text-muted">
                                                                    Podés adjuntar un PDF, imagen u otro archivo como
                                                                    evidencia de la revisión.
                                                                </small>
                                                            </div>
                                                        </div>

                                                        <div class="modal-footer">
                                                            <button type="submit" class="btn btn-success">Registrar
                                                                revisión</button>
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Cancelar</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="modalEliminar{{ $ejecucion->id }}" tabindex="-1"
                                            role="dialog">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content border-0 shadow">

                                                    <div class="modal-header bg-light">
                                                        <h5 class="modal-title">
                                                            <i class="fa fa-trash mr-2 text-danger"></i>Eliminar
                                                            recordatorio
                                                        </h5>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Cerrar">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>

                                                    <div class="modal-body">
                                                        <p>
                                                            ¿Qué querés eliminar?
                                                        </p>

                                                        <p class="mb-1">
                                                            <strong>Documento:</strong>
                                                            {{ $ejecucion->documento->titulo ?? 'Sin documento' }}
                                                        </p>

                                                        <p>
                                                            <strong>Recordatorio:</strong>
                                                            {{ $ejecucion->recordatorio->nombre ?? '-' }}
                                                        </p>

                                                        <div class="alert alert-warning mb-0 text-wrap">
                                                            Si eliminás todos los futuros, el recordatorio recurrente
                                                            quedará desactivado.
                                                        </div>
                                                    </div>

                                                    <div class="modal-footer d-flex justify-content-between">
                                                        <form
                                                            action="{{ route('recordatorioEjecuciones.eliminarActual', $ejecucion->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit" class="btn btn-outline-danger">
                                                                Eliminar solo este evento
                                                            </button>
                                                        </form>

                                                        <form
                                                            action="{{ route('recordatorioEjecuciones.eliminarFuturos', $ejecucion->id) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit" class="btn btn-danger">
                                                                Eliminar todos los futuros
                                                            </button>
                                                        </form>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        No tenés recordatorios pendientes.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
