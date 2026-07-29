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

        <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded"
            style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
            <div>
                <h3 class="mb-1">Recordatorios</h3>
                <small class="text-muted">Vista calendario de tus tareas pendientes y resueltas</small>
            </div>

            <div class="btn-group">
                <a href="{{ route('recordatorios.mis') }}"
                    class="btn btn-sm {{ request()->routeIs('recordatorios.mis') ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fa fa-list"></i> Lista
                </a>

                <a href="{{ route('recordatorios.calendario') }}"
                    class="btn btn-sm {{ request()->routeIs('recordatorios.calendario') ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fa fa-calendar"></i> Calendario
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-body py-2 px-3">
                <div class="d-flex flex-wrap align-items-center" style="gap: 8px;">
                    <span class="mb-0 mr-2"><strong>Referencia:</strong></span>

                    <span class="badge badge-pill px-2 py-1" style="background-color: #dc3545; color: white;">Vencido</span>

                    <span class="badge badge-pill px-2 py-1" style="background-color: #ffc107; color: #212529;">Hoy</span>

                    <span class="badge badge-pill px-2 py-1"
                        style="background-color: #007bff; color: white;">Pendiente</span>

                    <span class="badge badge-pill px-2 py-1"
                        style="background-color: #17a2b8; color: white;">Postergado</span>

                    <span class="badge badge-pill px-2 py-1"
                        style="background-color: #28a745; color: white;">Resuelto</span>
                    <span class="badge badge-pill px-2 py-1"
                        style="background-color: #6c757d; color: white;">Programado</span>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-3 p-md-4">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEventoRecordatorio" tabindex="-1" role="dialog"
        aria-labelledby="modalEventoRecordatorioLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content border-0 shadow">
                <form id="formPostergarDesdeCalendario" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="modal-header bg-light align-items-center">
                        <div>
                            <h5 class="modal-title" id="modalEventoRecordatorioLabel">
                                <i class="fa fa-bell text-primary mr-2"></i>Detalle del recordatorio
                            </h5>
                            <small class="text-muted ml-4">Información y acciones disponibles</small>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="recordatorio-detail-card p-3 p-md-4 mb-3">
                            <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="recordatorio-detail-label">Documento</div>
                                <div class="font-weight-bold" id="modal_documento_titulo">-</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="recordatorio-detail-label">Recordatorio</div>
                                <div class="font-weight-bold" id="modal_recordatorio_nombre">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="recordatorio-detail-label" id="modal_fecha_label">Fecha</div>
                                <div id="modal_fecha">-</div>
                            </div>

                            <div class="col-md-6">
                                <div class="recordatorio-detail-label">Estado</div>
                                <span id="modal_estado" class="badge badge-pill px-3 py-2">-</span>
                            </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="recordatorio-detail-label">Mensaje</div>
                                <div id="modal_mensaje">-</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="recordatorio-detail-label">Observación actual</div>
                                <div id="modal_observacion">-</div>
                            </div>
                        </div>

                        <div id="alertaRecordatorioProgramado" class="alert alert-secondary border-0 mb-0">
                            <div class="d-flex">
                                <i class="fa fa-calendar-alt mt-1 mr-3"></i>
                                <div>
                                    <strong>Próxima ejecución programada</strong>
                                    <div class="small mt-1">
                                        Todavía no existe una tarea para resolver o postergar. Las acciones estarán
                                        disponibles cuando el sistema genere la ejecución.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="contenedorPostergacion" class="recordatorio-action-panel p-3 mt-2"
                            style="display: none;">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <strong><i class="fa fa-clock text-warning mr-2"></i>Postergar tarea</strong>
                                    <div class="small text-muted">Definí una nueva fecha y, si querés, dejá el motivo.</div>
                                </div>
                                <button type="button" class="close" id="btnCerrarPostergacion" aria-label="Cerrar">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>

                            <div class="form-row">
                                <div class="form-group col-md-5 mb-md-0">
                                    <label for="postergado_hasta">Nueva fecha</label>
                                    <input type="date" name="postergado_hasta" id="postergado_hasta"
                                        class="form-control">
                                </div>

                                <div class="form-group col-md-7 mb-0">
                                    <label for="observacion">Observación</label>
                                    <textarea name="observacion" id="observacion" class="form-control" rows="2"
                                        placeholder="Motivo de la postergación (opcional)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer d-flex justify-content-between flex-wrap">
                        <div class="mb-2 mb-md-0">
                            <a href="#" id="btnVerDocumento" class="btn btn-outline-primary" target="_self">
                                <i class="fa fa-eye mr-1"></i> Ver documento
                            </a>
                        </div>

                        <div>
                            <button type="button" id="btnResolver" class="btn btn-success">
                                <i class="fa fa-check mr-1"></i> Resolver
                            </button>
                            <button type="button" id="btnMostrarPostergacion" class="btn btn-outline-warning">
                                <i class="fa fa-clock mr-1"></i> Postergar
                            </button>
                            <button type="submit" id="btnConfirmarPostergacion" class="btn btn-warning"
                                style="display: none;">
                                <i class="fa fa-save mr-1"></i> Confirmar postergación
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="btnEliminarRecordatorio">
                                <i class="fa fa-trash"></i>
                                Eliminar
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    // Modal para confirmar eliminación recordatorio
    <div class="modal fade" id="modalEliminarRecordatorio" tabindex="-1" role="dialog">

        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fa fa-trash text-danger mr-2"></i>
                        Eliminar recordatorio
                    </h5>

                    <button type="button" class="close" data-dismiss="modal">

                        <span>&times;</span>
                    </button>
                </div>

                <div class="modal-body">

                    <p>
                        ¿Qué querés eliminar?
                    </p>

                    <div class="alert alert-warning" style="white-space: normal; word-break: break-word;">

                        Si eliminás todos los futuros,
                        el recordatorio recurrente quedará desactivado.
                    </div>

                </div>

                <div class="modal-footer d-flex flex-wrap gap-2">

                    <form id="formEliminarActualCalendario" method="POST" class="w-100 mb-2">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-outline-danger btn-block">

                            Eliminar solo este evento
                        </button>
                    </form>

                    <form id="formEliminarFuturosCalendario" method="POST" class="w-100">

                        @csrf
                        @method('DELETE')

                        <button type="submit" class="btn btn-danger btn-block">

                            Eliminar todos los futuros
                        </button>
                    </form>

                </div>

            </div>
        </div>
    </div>

    // Modal para resolver con revisión desde calendario
    <div class="modal fade" id="modalResolverConRevisionCalendario" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content border-0 shadow">
                <form id="formResolverConRevisionCalendario" method="POST" enctype="multipart/form-data">
                    @csrf

                    <div class="modal-header bg-light">
                        <h5 class="modal-title">
                            <i class="fa fa-check mr-2"></i>Registrar revisión
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-2">
                            <strong>Documento:</strong>
                            <span id="resolver_documento_titulo">-</span>
                        </p>

                        <p class="mb-3">
                            <strong>Recordatorio:</strong>
                            <span id="resolver_recordatorio_nombre">-</span>
                        </p>

                        <div class="form-group">
                            <label>Resultado de la revisión</label>
                            <select name="resultado" class="form-control" required>
                                <option value="conforme">Conforme - no requiere cambios</option>
                                <option value="requiere_nueva_version">Requiere nueva versión</option>
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
                            <input type="file" name="archivo_evidencia" class="form-control-file">
                            <small class="text-muted">
                                Podés adjuntar un PDF, imagen u otro archivo como evidencia de la revisión.
                            </small>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            Registrar revisión
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripting')
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <style>
        #calendar {
            min-height: 700px;
        }

        .fc {
            font-family: inherit;
        }

        .fc .fc-toolbar {
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 1rem !important;
        }

        .fc .fc-toolbar-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2d3d;
            text-transform: capitalize;
        }

        .fc .fc-button {
            background-color: #223a5e;
            border-color: #223a5e;
            box-shadow: none !important;
            padding: 0.45rem 0.85rem;
            font-weight: 500;
        }

        .fc .fc-button:hover {
            background-color: #2f4c79;
            border-color: #2f4c79;
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #546899;
            border-color: #546899;
        }

        .fc .fc-daygrid-day-top {
            padding: 4px 6px 0 6px;
        }

        .fc .fc-col-header-cell-cushion {
            color: #0d6efd;
            font-weight: 700;
            text-transform: lowercase;
            padding: 8px 4px;
        }

        .fc .fc-daygrid-day-number {
            color: #0d6efd;
            font-weight: 500;
            text-decoration: none;
        }

        .fc .fc-day-today {
            background: #fff8db !important;
        }

        .fc .fc-daygrid-day-frame {
            min-height: 110px;
        }

        .fc-theme-standard td,
        .fc-theme-standard th {
            border-color: #dee2e6;
        }

        .fc .fc-scrollgrid {
            border-radius: 8px;
            overflow: hidden;
        }

        .fc-event {
            border: 0 !important;
            border-radius: 6px !important;
            padding: 2px 6px !important;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
        }

        .fc-daygrid-event-dot {
            display: none;
        }

        .fc-h-event .fc-event-title {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .fc .fc-list-event:hover td {
            background-color: #f8f9fa;
        }

        .fc .fc-popover {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .fc .fc-popover-header {
            background: #f8f9fa;
        }

        .recordatorio-detail-card {
            background: #f8fafc;
            border: 1px solid #e5e9ef;
            border-radius: 10px;
        }

        .recordatorio-detail-label {
            color: #6c757d;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            margin-bottom: 0.35rem;
            text-transform: uppercase;
        }

        .recordatorio-action-panel {
            background: #fffaf0;
            border: 1px solid #ffe0a3;
            border-radius: 10px;
        }

        @media (max-width: 768px) {
            .fc .fc-toolbar-title {
                font-size: 1.4rem;
            }

            .fc .fc-toolbar {
                align-items: flex-start;
            }

            .fc .fc-toolbar-chunk {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const modal = $('#modalEventoRecordatorio');

            const formPostergar = document.getElementById('formPostergarDesdeCalendario');
            const formResolverRevision = document.getElementById('formResolverConRevisionCalendario');
            const btnResolver = document.getElementById('btnResolver');
            const btnEliminarRecordatorio = document.getElementById('btnEliminarRecordatorio');
            const btnVerDocumento = document.getElementById('btnVerDocumento');
            const btnMostrarPostergacion = document.getElementById('btnMostrarPostergacion');
            const btnConfirmarPostergacion = document.getElementById('btnConfirmarPostergacion');
            const btnCerrarPostergacion = document.getElementById('btnCerrarPostergacion');
            const contenedorPostergacion = document.getElementById('contenedorPostergacion');
            const alertaProgramado = document.getElementById('alertaRecordatorioProgramado');
            const inputPostergadoHasta = document.getElementById('postergado_hasta');
            const modalEstado = document.getElementById('modal_estado');

            function cerrarPostergacion() {
                contenedorPostergacion.style.display = 'none';
                btnConfirmarPostergacion.style.display = 'none';
                btnMostrarPostergacion.style.display = 'inline-block';
                inputPostergadoHasta.required = false;
            }

            btnMostrarPostergacion.addEventListener('click', function() {
                contenedorPostergacion.style.display = 'block';
                btnConfirmarPostergacion.style.display = 'inline-block';
                btnMostrarPostergacion.style.display = 'none';
                inputPostergadoHasta.required = true;
                inputPostergadoHasta.focus();
            });

            btnCerrarPostergacion.addEventListener('click', cerrarPostergacion);

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'es',
                height: 'auto',
                dayMaxEvents: 3,
                eventDisplay: 'block',
                displayEventTime: false,
                moreLinkText: function(n) {
                    return `+${n} más`;
                },
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
                },
                buttonText: {
                    today: 'Hoy',
                    month: 'Mes',
                    week: 'Semana',
                    day: 'Día',
                    list: 'Lista'
                },
                events: '{{ route('recordatorios.eventos') }}',
                eventClick: function(info) {
                    const e = info.event;
                    const props = e.extendedProps;
                    $('#btnEliminarRecordatorio').off('click').on('click', function() {
                        if (!props.eliminar_actual_url || !props.eliminar_futuros_url) {
                            return;
                        }

                        $('#formEliminarActualCalendario').attr('action', props
                            .eliminar_actual_url);
                        $('#formEliminarFuturosCalendario').attr('action', props
                            .eliminar_futuros_url);

                        $('#modalEliminarRecordatorio').modal('show');
                    });

                    document.getElementById('modal_documento_titulo').textContent = props
                        .documento_titulo || '-';
                    document.getElementById('modal_recordatorio_nombre').textContent = props
                        .recordatorio_nombre || '-';
                    document.getElementById('modal_fecha').textContent = props.fecha || '-';
                    const estado = props.estado || '-';
                    const estadoClases = {
                        programado: 'badge-secondary',
                        pendiente: 'badge-primary',
                        postergado: 'badge-info',
                        resuelto: 'badge-success',
                        vencido: 'badge-danger'
                    };

                    modalEstado.textContent = estado.charAt(0).toUpperCase() + estado.slice(1);
                    modalEstado.className = 'badge badge-pill px-3 py-2 ' +
                        (estadoClases[estado] || 'badge-secondary');
                    document.getElementById('modal_mensaje').textContent = props.mensaje || '-';
                    document.getElementById('modal_observacion').textContent = props.observacion || '-';
                    document.getElementById('modal_fecha_label').textContent =
                        props.tipo === 'programado' ? 'Próxima ejecución' : 'Fecha de la tarea';

                    formPostergar.action = props.postergar_url || '';
                    formResolverRevision.action = props.resolver_url || '';
                    btnVerDocumento.href = props.url_documento || '#';
                    inputPostergadoHasta.value = '';
                    document.getElementById('observacion').value = '';
                    cerrarPostergacion();

                    if (props.tipo === 'programado') {
                        alertaProgramado.style.display = 'block';
                        btnResolver.style.display = 'none';
                        btnEliminarRecordatorio.style.display = 'none';
                        btnMostrarPostergacion.style.display = 'none';
                        btnConfirmarPostergacion.style.display = 'none';
                    } else {
                        alertaProgramado.style.display = 'none';
                        btnResolver.style.display = 'inline-block';
                        btnEliminarRecordatorio.style.display = 'inline-block';
                        btnMostrarPostergacion.style.display = 'inline-block';

                        btnResolver.onclick = function() {
                            if (!props.resolver_url) {
                                return;
                            }

                            document.getElementById('resolver_documento_titulo').textContent =
                                props.documento_titulo || '-';

                            document.getElementById('resolver_recordatorio_nombre').textContent =
                                props.recordatorio_nombre || '-';

                            modal.modal('hide');
                            $('#modalResolverConRevisionCalendario').modal('show');
                        };
                    }

                    modal.modal('show');
                }
            });

            calendar.render();
        });
    </script>
@endsection
