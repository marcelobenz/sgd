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

                    <div class="modal-header bg-light">
                        <h5 class="modal-title" id="modalEventoRecordatorioLabel">
                            <i class="fa fa-bell mr-2"></i>Detalle del recordatorio
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1">Documento</label>
                                <div class="font-weight-bold" id="modal_documento_titulo">-</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1">Recordatorio</label>
                                <div class="font-weight-bold" id="modal_recordatorio_nombre">-</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1">Fecha</label>
                                <div id="modal_fecha">-</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="text-muted mb-1">Estado</label>
                                <div id="modal_estado">-</div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="text-muted mb-1">Mensaje</label>
                                <div id="modal_mensaje">-</div>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="text-muted mb-1">Observación actual</label>
                                <div id="modal_observacion">-</div>
                            </div>
                        </div>

                        <hr>

                        <div class="alert alert-light border mb-3">
                            <strong>Postergar recordatorio</strong>
                            <div class="small text-muted">Podés definir una nueva fecha y dejar una observación.</div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-5">
                                <label for="postergado_hasta">Postergar hasta</label>
                                <input type="date" name="postergado_hasta" id="postergado_hasta" class="form-control">
                            </div>

                            <div class="form-group col-md-7">
                                <label for="observacion">Observación</label>
                                <textarea name="observacion" id="observacion" class="form-control" rows="3"
                                    placeholder="Motivo de la postergación"></textarea>
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
                            <button type="submit" class="btn btn-warning">
                                <i class="fa fa-clock mr-1"></i> Postergar
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </form>

                <form id="formResolverDesdeCalendario" method="POST" style="display:none;">
                    @csrf
                    @method('PATCH')
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
            const formResolver = document.getElementById('formResolverDesdeCalendario');
            const btnResolver = document.getElementById('btnResolver');
            const btnVerDocumento = document.getElementById('btnVerDocumento');

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

                    document.getElementById('modal_documento_titulo').textContent = props
                        .documento_titulo || '-';
                    document.getElementById('modal_recordatorio_nombre').textContent = props
                        .recordatorio_nombre || '-';
                    document.getElementById('modal_fecha').textContent = props.fecha || '-';
                    document.getElementById('modal_estado').textContent = props.estado || '-';
                    document.getElementById('modal_mensaje').textContent = props.mensaje || '-';
                    document.getElementById('modal_observacion').textContent = props.observacion || '-';

                    formPostergar.action = props.postergar_url;
                    formResolver.action = props.resolver_url;
                    btnVerDocumento.href = props.url_documento || '#';

                    btnResolver.onclick = function() {
                        formResolver.submit();
                    };

                    modal.modal('show');
                }
            });

            calendar.render();
        });
    </script>
@endsection
