@extends('layouts.main')

@section('heading')
    <style>
        /* Reducir tamaño de letra y padding en celdas */
        .table td {
            font-size: 12px;
            padding: 5px;
        }

        /* Ocultar los iconos inicialmente */
        .action-icons {
            visibility: visible;
            text-align: center;
            white-space: nowrap;
            align-items: center;
            align-content: center;
        }

        /* Ajustes opcionales para una mejor presentación */
        .table-row {
            cursor: pointer;
            transition: background-color .15s ease;
        }

        .table-row:hover,
        .table-row:focus {
            background-color: #eef7f9;
            outline: none;
        }

        .btn-link {
            text-decoration: none;
        }

        .selected-row {
            background-color: #d1ecf1;
            font-weight: bold;
        }

        #documentViewer {
            position: relative;
            z-index: 1050;
        }

        #versionInfoPanel {
            width: 100%;
            background-color: #ffffff;
            border: 1px solid #dcdfe3;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
            overflow: hidden;
        }

        #versionDetailRow > td {
            padding: 0;
            border-top: 0;
        }

        #versionInfoPanel .vip-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 12px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        #versionInfoPanel .vip-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1f2d3d;
        }

        #versionInfoPanel .vip-close {
            font-size: 1.4rem;
            line-height: 1;
            background: none;
            border: 0;
            color: #6c757d;
            cursor: pointer;
        }

        #versionInfoPanel .vip-close:hover {
            color: #212529;
        }

        #versionInfoPanel .vip-body {
            padding: 10px 12px;
        }

        #versionInfoPanel .vip-table {
            margin-bottom: 0;
            font-size: 12px;
        }

        #versionInfoPanel .vip-table td {
            vertical-align: top;
            padding: 8px 10px;
        }

        #versionInfoPanel .vip-label {
            width: 50%;
            background-color: #f8f9fa;
            font-weight: 600;
            color: #495057;
            white-space: nowrap;
        }

        #versionInfoPanel .vip-value {
            color: #212529;
        }

        #versionInfoPanel .vip-content {
            white-space: pre-wrap;
            line-height: 1.4;
        }

        #versionInfoPanel #m_estadoBadge {
            display: inline-block;
            padding: 4px 10px;
            border: 2px solid #999;
            border-radius: 6px;
            font-weight: 600;
            min-width: 120px;
            text-align: center;
        }

        #versionInfoPanel .vip-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 10px;
        }

        .tabla-revisiones td,
        .tabla-revisiones th {
            font-size: 11px;
            padding: 6px;
            vertical-align: top;
        }

        .seccion-acordeon {
            margin-bottom: 10px;
        }

        .seccion-acordeon .acordeon-titulo {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            padding: 10px 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #1f2d3d;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .seccion-acordeon .acordeon-titulo:hover {
            background-color: #eef3f8;
            color: #0d6efd;
            text-decoration: none;
        }

        .seccion-acordeon .acordeon-titulo:focus {
            outline: none;
            box-shadow: none;
        }

        .seccion-acordeon .acordeon-body {
            border: 1px solid #dee2e6;
            border-top: 0;
            background-color: #fff;
        }

        .acordeon-titulo-izq {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .acordeon-icono {
            color: #0d6efd;
            width: 18px;
            text-align: center;
        }

        .acordeon-flecha {
            transition: transform 0.2s ease;
            color: #6c757d;
        }

        .acordeon-titulo[aria-expanded="true"] .acordeon-flecha {
            transform: rotate(180deg);
        }

        .tabla-revisiones td,
        .tabla-revisiones th {
            font-size: 11px;
            padding: 6px;
            vertical-align: top;
        }

        .fila-revision strong {
            color: #1f2d3d;
        }

        .revision-programada {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 6px;
            border-radius: 12px;
            background-color: #f8f9fa;
            color: #6c757d;
            font-size: 10px;
        }

        .revision-constancia {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: #6c757d;
            line-height: 1.35;
        }

        .revision-usuario {
            font-weight: 600;
            color: #1f2d3d;
        }

        .revision-vacia {
            padding: 14px;
            color: #6c757d;
            font-size: 12px;
        }
    </style>
@endsection

@section('contenidoPrincipal')

    <div class="container-fluid" style="margin-top: 40px;">
        <br>
        @if ($errors->any())
            <div class="alert alert-danger mt-4">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger mt-4">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <form id="uploadForm" action="{{ route('documentos.addVersion', $documento->id) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row">

                <!-- Columna A: Información del documento -->
                <div id="colInfoDocumento" class="col-12 col-md-3"
                    style="background-color: #f9f9f9; padding: 15px; border-radius: 8px;">

                    <div class="d-flex justify-content-end mt-2">
                        <div class="btn-group w-100" role="group" aria-label="Basic mixed styles example">
                            <a href="{{ route('documentos.download', $documento->id) }}" class="btn btn-custom"
                                data-toggle="tooltip" data-placement="top" title="Descargar versión actual">
                                <i class="fa-solid fa-cloud-arrow-down"></i>
                            </a>
                            <button type="button" id="uploadModalBtn" class="btn btn-custom" data-placement="top"
                                title="Subir una nueva versión" data-toggle="tooltip" data-target="#uploadModal">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </button>
                            <button type="button" id="AprobarModalBtn" class="btn btn-custom" data-toggle="tooltip"
                                data-placement="top" title="Aprobar Documento">
                                <i class="fa-regular fa-thumbs-up"></i>
                            </button>
                            @if ($documento->puedeAprobar(auth()->user()) && $documento->estado === 'pendiente de aprobación')
                                <button type="button" id="RechazarModalBtn" class="btn btn-custom" data-toggle="modal"
                                    data-target="#modalRechazo" title="Rechazar documento">
                                    <i class="fas fa-thumbs-down"></i>
                                </button>
                            @endif

                            <a href="{{ route('documentos.validaPermiso', ['id' => $documento, 'ruta' => 'documentos.edit', 'permiso' => 'puedeEscribir']) }}"
                                class="btn btn-custom" data-toggle="tooltip" data-placement="top"
                                title="Editar Cabecera y Permisos">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>

                            <a href="{{ route('documentos.exportarPdf', $documento) }}" class="btn btn-custom"
                                data-toggle="tooltip" data-placement="top" title="Exportar PDF">
                                <i class="fa-solid fa-file-pdf"></i>
                            </a>

                            <a href="{{ route('documentos.index') }}" class="btn btn-custom" data-toggle="tooltip"
                                data-placement="top" title="Volver">
                                <i class="fa-regular fa-hand-point-left"></i>
                            </a>
                        </div>
                    </div>

                    <div id="bloqueVersionActual" style="position: relative;">
                        <div id="accordionInfoDocumento">

                            <!-- SECCIÓN 1: VERSIÓN ACTUAL -->
                            <div class="seccion-acordeon">
                                <a class="acordeon-titulo" data-toggle="collapse" href="#collapseVersionActual"
                                    role="button" aria-expanded="true" aria-controls="collapseVersionActual">
                                    <span class="acordeon-titulo-izq">
                                        <i class="fa-solid fa-file-lines acordeon-icono"></i>
                                        <span>Versión Actual</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-down acordeon-flecha"></i>
                                </a>

                                <div class="collapse show acordeon-body" id="collapseVersionActual"
                                    data-parent="#accordionInfoDocumento">
                                    <table class="table table-sm table-bordered mb-0 tabla-revisiones">
                                        <tbody>
                                            <tr>
                                                <td>Documento:</td>
                                                <td colspan="3">{{ $documento->titulo }}</td>
                                            </tr>
                                            <tr>
                                                <td>Versión:</td>
                                                <td>{{ $documento->version }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-light btn-link mx-1"
                                                        onclick="viewVersion('{{ sprintf('https://%s.s3.%s.amazonaws.com/%s', config('filesystems.disks.s3.bucket'), config('filesystems.disks.s3.region'), $documento->path) }}', '{{ pathinfo($documento->path, PATHINFO_EXTENSION) }}')"
                                                        data-toggle="tooltip" data-placement="top"
                                                        title="Ver versión actual">
                                                        <i class="fa-solid fa-eye"></i>
                                                    </button>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-light btn-link mx-1"
                                                        onclick="mostrarPopoverContenido(event, `{{ addslashes($documento->contenido ?? 'Sin contenido') }}`)"
                                                        title="Ver detalle versión">
                                                        <i class="fa-solid fa-file-lines"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Categoría:</td>
                                                <td colspan="3">{{ $documento->categoria->nombre_categoria }}</td>
                                            </tr>
                                            <tr>
                                                <td style="vertical-align: middle;">Estado:</td>
                                                <td colspan="3">
                                                    @php
                                                        $estadoColor = '';
                                                        switch ($documento->estado) {
                                                            case 'pendiente de aprobación':
                                                                $estadoColor = 'red';
                                                                break;
                                                            case 'aprobado':
                                                                $estadoColor = 'green';
                                                                break;
                                                            case 'registro':
                                                                $estadoColor = 'blue';
                                                                break;
                                                            default:
                                                                $estadoColor = 'black';
                                                        }
                                                    @endphp
                                                    <span id="estadoDocumentoActual"
                                                        style="border: 2px solid {{ $estadoColor }}; color: {{ $estadoColor }}; padding: 5px; border-radius: 4px; display: inline-block; width: 80%; text-align: center;">
                                                        {{ $documento->estado }}
                                                    </span>
                                                </td>
                                            </tr>

                                            <tr id="aprobadorDocumentoRow"
                                                style="{{ $documento->aprobador && $documento->estado === 'aprobado' ? '' : 'display: none;' }}">
                                                <td>Aprobador:</td>
                                                <td colspan="3" id="aprobadorDocumento">
                                                    {{ optional($documento->aprobador)->name }}
                                                </td>
                                            </tr>
                                            <tr id="fechaAprobacionDocumentoRow"
                                                style="{{ $documento->aprobador && $documento->estado === 'aprobado' ? '' : 'display: none;' }}">
                                                <td>Fecha:</td>
                                                <td colspan="3" id="fechaAprobacionDocumento">
                                                    {{ $documento->fecha_aprobacion }}
                                                </td>
                                            </tr>

                                            <tr>
                                                <td>Creador:</td>
                                                <td colspan="3">{{ $documento->creador->name }}</td>
                                            </tr>
                                            <tr>
                                                <td>Fecha:</td>
                                                <td colspan="3">{{ $documento->created_at }}</td>
                                            </tr>
                                            <tr>
                                                <td>Último Editor:</td>
                                                <td colspan="3">{{ $documento->ultimaModificacion->name }}</td>
                                            </tr>
                                            <tr>
                                                <td>Fecha:</td>
                                                <td colspan="3">{{ $documento->updated_at }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- SECCIÓN 2: REVISIONES PERIÓDICAS -->
                            <div class="seccion-acordeon">
                                <a class="acordeon-titulo" data-toggle="collapse" href="#collapseRevisiones"
                                    role="button" aria-expanded="false" aria-controls="collapseRevisiones">
                                    <span class="acordeon-titulo-izq">
                                        <i class="fa-solid fa-clipboard-check acordeon-icono"></i>
                                        <span>Revisiones periódicas registradas</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-down acordeon-flecha"></i>
                                </a>

                                <div class="collapse acordeon-body" id="collapseRevisiones"
                                    data-parent="#accordionInfoDocumento">
                                    @if (isset($revisionesCumplidas) && $revisionesCumplidas->count())
                                        <div style="max-height: 240px; overflow-y: auto;">
                                            <table class="table table-sm table-bordered mb-0 tabla-revisiones">
                                                <thead style="background-color: #f8f9fa;">
                                                    <tr>
                                                        <th>Recordatorio</th>
                                                        <th>Fecha revisión</th>
                                                        <th>Resultado</th>
                                                        <th>Usuario</th>
                                                        <th>Evidencia</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($revisionesCumplidas as $revision)
                                                        <tr class="fila-revision">
                                                            <td>
                                                                <strong>{{ $revision->recordatorio->nombre ?? '-' }}</strong>

                                                                @if (!empty($revision->observacion_resolucion))
                                                                    <span class="revision-constancia">
                                                                        {{ $revision->observacion_resolucion }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <div>
                                                                    {{ optional($revision->fecha_resolucion)->format('d/m/Y H:i') ?? '-' }}
                                                                </div>

                                                                @if ($revision->fecha_programada)
                                                                    <span class="revision-programada">
                                                                        Programada:
                                                                        {{ optional($revision->fecha_programada)->format('d/m/Y H:i') }}
                                                                    </span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if (isset($revision->revision) && $revision->revision)
                                                                    @if ($revision->revision->resultado === 'conforme')
                                                                        <span class="badge badge-success">
                                                                            Conforme
                                                                        </span>
                                                                    @elseif($revision->revision->resultado === 'requiere_nueva_version')
                                                                        <span class="badge badge-warning">
                                                                            Nueva versión requerida
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-secondary">
                                                                            No aplica
                                                                        </span>
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                <span class="revision-usuario">
                                                                    {{ $revision->resueltoPor->name ?? '-' }}
                                                                </span>
                                                            </td>
                                                            <td>
                                                                @if (isset($revision->revision) && $revision->revision && $revision->revision->archivo_evidencia)
                                                                    <a href="{{ Storage::disk('s3')->url($revision->revision->archivo_evidencia) }}"
                                                                        target="_blank"
                                                                        class="btn btn-sm btn-outline-primary">

                                                                        <i class="fa fa-paperclip"></i>
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="revision-vacia">
                                            No hay revisiones periódicas registradas.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- SECCIÓN 3: VERSIONES ANTERIORES -->
                            <div class="seccion-acordeon">
                                <a class="acordeon-titulo" data-toggle="collapse" href="#collapseVersAnteriores"
                                    role="button" aria-expanded="false" aria-controls="collapseVersAnteriores">
                                    <span class="acordeon-titulo-izq">
                                        <i class="fa-solid fa-clock-rotate-left acordeon-icono"></i>
                                        <span>Versiones Anteriores</span>
                                    </span>
                                    <i class="fa-solid fa-chevron-down acordeon-flecha"></i>
                                </a>

                                <div class="collapse acordeon-body" id="collapseVersAnteriores"
                                    data-parent="#accordionInfoDocumento">
                                    <table class="table table-bordered w-100 mb-0">
                                        <thead>
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody id="versionHistoryBody">
                                            @foreach ($documento->historial as $index => $versionhistorial)
                                                @php
                                                    $histMeta = [
                                                        'titulo' => $versionhistorial->titulo ?? $documento->titulo,
                                                        'version' => $versionhistorial->version,
                                                        'categoria' => $documento->categoria->nombre_categoria,
                                                        'estado' => $versionhistorial->estado,
                                                        'aprobador' =>
                                                            optional($versionhistorial->aprobador)->name ?? null,
                                                        'fecha_aprobacion' =>
                                                            (string) $versionhistorial->fecha_aprobacion,
                                                        'creador' => optional($versionhistorial->creador)->name ?? null,
                                                        'fecha_creacion' => (string) $versionhistorial->created_at,
                                                        'ultimo_editor' =>
                                                            optional($versionhistorial->ultimaModificacion)->name ??
                                                            null,
                                                        'fecha_ultima_modif' => (string) $versionhistorial->updated_at,
                                                        'contenido' => $versionhistorial->contenido,
                                                    ];
                                                    $histUrl = sprintf(
                                                        'https://%s.s3.%s.amazonaws.com/%s',
                                                        config('filesystems.disks.s3.bucket'),
                                                        config('filesystems.disks.s3.region'),
                                                        $versionhistorial->path,
                                                    );
                                                    $histExt = pathinfo($versionhistorial->path, PATHINFO_EXTENSION);
                                                @endphp

                                                <tr class="table-row version-history-row" tabindex="0" role="button"
                                                    data-url="{{ $histUrl }}"
                                                    data-extension="{{ $histExt }}"
                                                    data-meta="{{ json_encode($histMeta, JSON_UNESCAPED_UNICODE) }}"
                                                    data-action="{{ route('documentos.revert', [$documento->id, $versionhistorial->id]) }}"
                                                    aria-label="Ver detalle de la versión {{ $versionhistorial->version }}">
                                                    <td>{{ $versionhistorial->version }}</td>
                                                    <td>{{ $versionhistorial->created_at }}</td>
                                                </tr>
                                            @endforeach
                                            <tr id="versionDetailRow" style="display: none;">
                                                <td colspan="2" id="versionDetailCell"></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Columna B: Botones y visor embebido -->
                <div id="colContenidoDocumento" class="col-12 col-md-9">
                    <div id="visorContainer" style="margin-top: 20px;">
                        <iframe id="documentViewer" src="" style="width: 100%; height: 600px;" frameborder="0"
                            allowfullscreen></iframe>
                    </div>
                </div>

            </div>

            <!-- Modales -->
            <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel"
                aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="uploadModalLabel">Seleccionar nuevo archivo</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="nuevoArchivo">Nuevo Archivo</label>
                                <input type="file" name="nuevoArchivo" id="nuevoArchivo" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="contenidoActualizado">Contenido actualizado</label>
                                <textarea name="contenidoActualizado" id="contenidoActualizado" class="form-control" rows="5"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary" id="uploadBtn">Aceptar</button>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <form id="revert-global-form" method="POST" style="display:none;">
            @csrf
        </form>

        <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmModalLabel">Confirmar Reversión</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        ¿Estás seguro de que deseas revertir a esta versión?
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="confirmButton">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalRechazo" tabindex="-1" role="dialog" aria-labelledby="modalRechazoLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <form id="formRechazo" action="{{ route('documentos.rechazar', $documento->id) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title" id="modalRechazoLabel">Rechazar documento</h5>
                            <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <label for="comentarios">Motivo del rechazo:</label>
                            <textarea name="comentarios" id="comentarios" class="form-control" rows="4" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                            <button type="submit" class="btn btn-danger">Rechazar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="aprobarModal" tabindex="-1" role="dialog" aria-labelledby="aprobarModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="aprobarModalLabel">Confirmar Aprobación</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        ¿Estás seguro de que deseas aprobar este documento?
                        <div class="mt-3">
                            <div class="form-check">
                                <input type="hidden" name="notificar_autor" value="0" form="formAprobarDoc">
                                <input class="form-check-input" type="checkbox" id="notificarAutor"
                                    name="notificar_autor" value="1" form="formAprobarDoc" checked>
                                <label class="form-check-label" for="notificarAutor">
                                    Notificar al autor
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <form id="formAprobarDoc" action="{{ route('documentos.aprobar', $documento->id) }}"
                            method="POST">
                            @csrf
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning">Aprobar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div id="contenidoPopover"
            style="display:none; position:absolute; z-index:1050; max-width:300px; background:#fff; border:1px solid #ccc; border-radius:5px; box-shadow:0 2px 10px rgba(0,0,0,0.2); padding:10px; white-space:pre-wrap;">
        </div>

    @endsection

    <div id="versionInfoPanel" style="display: none;">
        <div class="vip-header">
            <h5 class="vip-title" id="versionInfoLabel">Versión</h5>
            <button type="button" class="vip-close" onclick="cerrarVersionInfoPanel()" aria-label="Cerrar">
                &times;
            </button>
        </div>

        <div class="vip-body">
            <table class="table table-bordered vip-table">
                <tbody>
                    <tr>
                        <td class="vip-label">Documento</td>
                        <td class="vip-value" id="m_titulo"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Versión</td>
                        <td class="vip-value" id="m_version"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Categoría</td>
                        <td class="vip-value" id="m_categoria"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Estado</td>
                        <td class="vip-value"><span id="m_estadoBadge"></span></td>
                    </tr>
                    <tr id="m_aprobadorRow">
                        <td class="vip-label">Aprobador</td>
                        <td class="vip-value" id="m_aprobador"></td>
                    </tr>
                    <tr id="m_fechaAprRow">
                        <td class="vip-label">Fecha aprobación</td>
                        <td class="vip-value" id="m_fecha_aprobacion"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Creador</td>
                        <td class="vip-value" id="m_creador"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Fecha creación</td>
                        <td class="vip-value" id="m_fecha_creacion"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Último editor</td>
                        <td class="vip-value" id="m_ultimo_editor"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Fecha última edición</td>
                        <td class="vip-value" id="m_fecha_ultima"></td>
                    </tr>
                    <tr>
                        <td class="vip-label">Detalle de versión</td>
                        <td class="vip-value vip-content" id="m_contenido"></td>
                    </tr>
                </tbody>
            </table>
            <div class="vip-actions">
                <button type="button" id="revertSelectedVersion" class="btn btn-warning btn-sm">
                    <i class="fa-solid fa-repeat"></i>
                    Revertir a esta versión
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarVersionInfoPanel()">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    @section('scripting')
        <script>
            let popoverTimeout = null;

            $(document).ready(function() {
                $('#AprobarModalBtn').on('click', function() {
                    $('#aprobarModal').modal('show');
                });

                $('#formAprobarDoc').on('submit', async function(event) {
                    event.preventDefault();

                    const form = this;
                    const submitButton = form.querySelector('button[type="submit"]');
                    submitButton.disabled = true;

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            body: new FormData(form),
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.message || 'No se pudo aprobar el documento.');
                        }

                        $('#aprobarModal').modal('hide');

                        const estado = document.getElementById('estadoDocumentoActual');
                        estado.textContent = result.estado;
                        estado.style.borderColor = 'green';
                        estado.style.color = 'green';

                        document.getElementById('aprobadorDocumento').textContent = result.aprobador || '';
                        document.getElementById('fechaAprobacionDocumento').textContent =
                            result.fecha_aprobacion || '';
                        document.getElementById('aprobadorDocumentoRow').style.display = '';
                        document.getElementById('fechaAprobacionDocumentoRow').style.display = '';

                        document.getElementById('AprobarModalBtn').style.display = 'none';
                        const rejectButton = document.getElementById('RechazarModalBtn');
                        if (rejectButton) {
                            rejectButton.style.display = 'none';
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Documento aprobado',
                                text: result.message,
                                confirmButtonText: 'Aceptar'
                            });
                        } else {
                            alert(result.message);
                        }
                    } catch (error) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo aprobar',
                                text: error.message,
                                confirmButtonText: 'Aceptar'
                            });
                        } else {
                            alert(error.message);
                        }
                    } finally {
                        submitButton.disabled = false;
                    }
                });
            });

            $(document).ready(function() {
                $('#uploadModalBtn').on('click', function() {
                    $('#uploadModal').modal('show');
                });
            });

            document.getElementById('uploadBtn').addEventListener('click', function() {
                const fileInput = document.getElementById('nuevoArchivo');
                const contenidoActualizado = document.getElementById('contenidoActualizado').value;

                let valid = true;

                if (fileInput.files.length === 0) {
                    alert('Debe seleccionar un archivo para continuar.');
                    valid = false;
                }

                if (contenidoActualizado.trim() === '') {
                    alert('Debe completar el contenido para continuar.');
                    valid = false;
                }

                if (valid) {
                    const form = document.getElementById('uploadForm');
                    form.submit();
                }
            });

            function confirmRevert(actionOrElement) {
                const action = typeof actionOrElement === 'string'
                    ? actionOrElement
                    : actionOrElement.getAttribute('data-action');
                $('#confirmModal').modal('show');

                document.getElementById('confirmButton').onclick = function() {
                    const form = document.getElementById('revert-global-form');
                    form.setAttribute('action', action);
                    form.submit();
                };
            }

            const estadoColors = {
                'pendiente de aprobación': 'red',
                'aprobado': 'green',
                'registro': 'blue'
            };

            function paintBadge(el, estado) {
                const color = estadoColors[estado] || 'black';
                el.textContent = estado || '';
                el.style.borderColor = color;
                el.style.color = color;
            }

            function viewVersion(url, extension) {
                const iframe = document.getElementById('documentViewer');
                let viewerUrl;
                switch (extension) {
                    case 'pdf':
                        viewerUrl = `https://docs.google.com/viewer?url=${encodeURIComponent(url)}&embedded=true`;
                        break;
                    case 'docx':
                    case 'xlsx':
                    case 'pptx':
                        viewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(url)}`;
                        break;
                    default:
                        alert('Formato no soportado para vista previa');
                        return;
                }
                iframe.src = viewerUrl;
            }

            function fillVersionDetails(meta) {
                document.getElementById('versionInfoLabel').textContent = `Versión ${meta.version ?? ''}`;
                document.getElementById('m_titulo').textContent = meta.titulo || '';
                document.getElementById('m_version').textContent = meta.version || '';
                document.getElementById('m_categoria').textContent = meta.categoria || '';
                paintBadge(document.getElementById('m_estadoBadge'), meta.estado || '');

                const showApr = meta.estado === 'aprobado' && !!meta.aprobador;
                document.getElementById('m_aprobadorRow').style.display = showApr ? '' : 'none';
                document.getElementById('m_fechaAprRow').style.display = showApr ? '' : 'none';

                document.getElementById('m_aprobador').textContent = meta.aprobador || '';
                document.getElementById('m_fecha_aprobacion').textContent = meta.fecha_aprobacion || '';
                document.getElementById('m_creador').textContent = meta.creador || '';
                document.getElementById('m_fecha_creacion').textContent = meta.fecha_creacion || '';
                document.getElementById('m_ultimo_editor').textContent = meta.ultimo_editor || '';
                document.getElementById('m_fecha_ultima').textContent = meta.fecha_ultima_modif || '';
                document.getElementById('m_contenido').textContent = meta.contenido || 'Sin contenido';
            }

            function openHistoricalVersion(row) {
                const detailRow = document.getElementById('versionDetailRow');
                const panel = document.getElementById('versionInfoPanel');
                const detailCell = document.getElementById('versionDetailCell');
                let meta;

                try {
                    meta = JSON.parse(row.dataset.meta);
                } catch (error) {
                    console.error('No se pudo leer el detalle de la versión.', error);
                    return;
                }

                document.querySelectorAll('.version-history-row').forEach(function(historyRow) {
                    historyRow.classList.remove('selected-row');
                    historyRow.setAttribute('aria-expanded', 'false');
                });

                row.classList.add('selected-row');
                row.setAttribute('aria-expanded', 'true');
                row.parentNode.insertBefore(detailRow, row.nextSibling);
                detailCell.appendChild(panel);

                fillVersionDetails(meta);
                document.getElementById('revertSelectedVersion').dataset.action = row.dataset.action;
                detailRow.style.display = 'table-row';
                panel.style.display = 'block';
                viewVersion(row.dataset.url, row.dataset.extension);
            }

            function cerrarVersionInfoPanel() {
                const detailRow = document.getElementById('versionDetailRow');
                const panel = document.getElementById('versionInfoPanel');

                if (detailRow) {
                    detailRow.style.display = 'none';
                }
                if (panel) {
                    panel.style.display = 'none';
                }

                document.querySelectorAll('.version-history-row').forEach(function(row) {
                    row.classList.remove('selected-row');
                    row.setAttribute('aria-expanded', 'false');
                });

                const currentUrl =
                    "{{ sprintf('https://%s.s3.%s.amazonaws.com/%s', config('filesystems.disks.s3.bucket'), config('filesystems.disks.s3.region'), $documento->path) }}";
                const currentExtension = "{{ pathinfo($documento->path, PATHINFO_EXTENSION) }}";

                viewVersion(currentUrl, currentExtension);
            }

            document.querySelectorAll('.version-history-row').forEach(function(row) {
                row.setAttribute('aria-expanded', 'false');

                row.addEventListener('click', function() {
                    openHistoricalVersion(this);
                });

                row.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openHistoricalVersion(this);
                    }
                });
            });

            const revertSelectedVersion = document.getElementById('revertSelectedVersion');
            if (revertSelectedVersion) {
                revertSelectedVersion.addEventListener('click', function(event) {
                    event.stopPropagation();
                    confirmRevert(this.dataset.action);
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                var currentDocumentUrl =
                    "{{ sprintf('https://%s.s3.%s.amazonaws.com/%s', config('filesystems.disks.s3.bucket'), config('filesystems.disks.s3.region'), $documento->path) }}";
                var currentDocumentExtension = "{{ pathinfo($documento->path, PATHINFO_EXTENSION) }}";
                viewVersion(currentDocumentUrl, currentDocumentExtension);
            });

            const toggleBtn = document.getElementById('toggleDetails');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    var colInfo = document.getElementById('colInfoDocumento');
                    var colContent = document.getElementById('colContenidoDocumento');
                    var collapseDetails = document.getElementById('collapseDetalles');

                    if (colInfo.classList.contains('collapsed')) {
                        colInfo.classList.remove('collapsed');
                        colContent.classList.remove('expanded');
                        if (collapseDetails) collapseDetails.style.display = 'block';
                    } else {
                        colInfo.classList.add('collapsed');
                        colContent.classList.add('expanded');
                        if (collapseDetails) collapseDetails.style.display = 'none';
                    }
                });
            }

            function mostrarPopoverContenido(event, contenido) {
                const popover = document.getElementById('contenidoPopover');
                popover.innerText = contenido;
                popover.style.display = 'block';

                const rect = event.currentTarget.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

                popover.style.top = (rect.top + scrollTop) + 'px';
                popover.style.left = (rect.right + scrollLeft + 10) + 'px';

                clearTimeout(popoverTimeout);
                popoverTimeout = setTimeout(() => {
                    popover.style.display = 'none';
                }, 3000);
            }

            document.addEventListener('click', function(e) {
                const popover = document.getElementById('contenidoPopover');
                if (!popover.contains(e.target) && !e.target.closest('.btn-light')) {
                    popover.style.display = 'none';
                    clearTimeout(popoverTimeout);
                }
            });

            document.addEventListener('click', function(event) {
                const detailRow = document.getElementById('versionDetailRow');
                if (!detailRow || detailRow.style.display === 'none') {
                    return;
                }

                if (!event.target.closest('#versionInfoPanel') &&
                    !event.target.closest('.version-history-row')) {
                    cerrarVersionInfoPanel();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const detailRow = document.getElementById('versionDetailRow');
                    if (detailRow && detailRow.style.display !== 'none') {
                        cerrarVersionInfoPanel();
                    }
                }
            });
        </script>

        <style>
            .btn-custom {
                border: 2px solid #333;
                background-color: #e9ecef;
                color: #000;
                transition: background-color 0.3s ease, box-shadow 0.3s ease;
            }

            .btn-custom:hover {
                background-color: #dcdcdc;
                color: #000;
                border-color: #666;
                box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            }

            .mt-5 {
                margin-top: 3rem !important;
            }

            #colInfoDocumento.collapsed {
                transition: margin-right 0.5s ease, width 0.5s ease;
                margin-right: -25%;
                width: 0;
            }

            #colContenidoDocumento.expanded {
                transition: width 0.5s ease;
                width: 100%;
            }

            #overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                text-align: center;
                color: white;
            }

            #overlay div {
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
        </style>
    @endsection

    </body>

    </html>
