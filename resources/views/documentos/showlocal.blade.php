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
    }

    .btn-link {
        text-decoration: none;
    }
    .selected-row {
    background-color: #d1ecf1; /* Cambia el color según tus preferencias */
    font-weight: bold; /* Opcional: para resaltar más el texto */
}
</style>

@endsection

@section('contenidoPrincipal')

<div class="container-fluid" style="margin-top: 20px;">
    <br>
    @if ($errors->any())
        <div class="alert alert-danger">
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
    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif    
    <form id="uploadForm" action="{{ route('documentos.addVersion', $documento->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="row">

            <!-- Columna A: Información del documento -->
            <div id="colInfoDocumento" class="col-12 col-md-3" style="background-color: #f9f9f9; padding: 15px; border-radius: 8px;">
                <div class="d-flex justify-content-end mt-2">
                    <div class="btn-group w-100" role="group" aria-label="Basic mixed styles example">
                        <a href="{{ route('documentos.download', $documento->id) }}" class="btn btn-custom" data-toggle="tooltip" data-placement="top" title="Descargar versión actual">
                            <i class="fa-solid fa-cloud-arrow-down"></i> 
                        </a>
                        <button type="button" id="uploadModalBtn" class="btn btn-custom" data-placement="top" title="Subir una nueva versión" data-toggle="tooltip" data-target="#uploadModal">
                            <i class="fa-solid fa-cloud-arrow-up"></i> 
                        </button>
                        <button type="button" id="AprobarModalBtn" class="btn btn-custom" data-toggle="tooltip" data-placement="top" title="Aprobar Documento">
                            <i class="fa-regular fa-thumbs-up"></i> 
                        </button>
                        <a href="{{ route('documentos.exportarPdf', $documento) }}" class="btn btn-custom" data-toggle="tooltip" data-placement="top" title="Exportar PDF">
                            <i class="fa-solid fa-file-pdf"></i> 
                        </a>
                        <a href="{{ route('documentos.index') }}" class="btn btn-custom" data-toggle="tooltip" data-placement="top" title="Volver">
                            <i class="fa-regular fa-hand-point-left"></i> 
                        </a>
                    </div>
                </div>

                <table class="table table-bordered w-100">
                    <thead>
                        <th colspan="2">Versión Actual</th>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Documento:</td><td>{{ $documento->titulo }}</td>
                        </tr>
                        <tr>
                            <td>Versión: </td><td>{{ $documento->version }}
                            <button type="button" class="btn btn-light btn-link p-0" onclick="viewVersion('{{ sprintf('https://%s.s3.%s.amazonaws.com/%s', env('AWS_BUCKET'), env('AWS_DEFAULT_REGION'), $documento->path) }}', '{{ pathinfo($documento->path, PATHINFO_EXTENSION) }}')" data-toggle="tooltip" data-placement="top" title="Ver version actual">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td>Categoría: </td><td>{{ $documento->categoria->nombre_categoria }}</td>
                        </tr>
                        <tr>
                            <td style="vertical-align: middle;">Estado: </td><td>
                                @php
                                    $estadoColor = '';
                                    switch($documento->estado) {
                                        case 'en curso':
                                            $estadoColor = 'orange';
                                            break;
                                        case 'pendiente de aprobación':
                                            $estadoColor = 'red';
                                            break;
                                        case 'aprobado':
                                            $estadoColor = 'green';
                                            break;
                                        default:
                                            $estadoColor = 'black';
                                    }
                                @endphp
                                <span style="border: 2px solid {{ $estadoColor }}; color: {{ $estadoColor }}; padding: 5px; border-radius: 4px; display: inline-block; width: 80%; text-align: center;">
                                    {{ $documento->estado }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Creador: </td><td> {{ $documento->creador->name }}</td>
                        </tr>
                        <tr>
                            <td>Fecha: </td><td> {{ $documento->created_at }}</td>
                        </tr>
                        <tr>
                            <td>Último Editor:  </td><td>{{ $documento->ultimaModificacion->name }}</td>
                        </tr>
                        <tr>
                            <td>Fecha:  </td><td>{{ $documento->updated_at }}</td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <a href="#" data-toggle="collapse" data-target="#collapseVersAnteriores" aria-expanded="false" aria-controls="collapseVersAnteriores">
                                    Versiones Anteriores
                                </a>
                            </th>
                        </tr>
                    </tbody>
                </table>

                <div class="collapse" id="collapseVersAnteriores">
                    <table class="table table-bordered w-100">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Fecha</th>
                                <th scope="col" colspan=2>Acciones</th>
                            </tr>
                        </thead>
                        @foreach ($documento->historial as $index => $versionhistorial)
                            <tr class="table-row">
                                <td>{{ $versionhistorial->version }}</td>
                                <td>{{ $versionhistorial->created_at }}</td>

                                    <form id="revert-form-{{ $versionhistorial->id }}" action="{{ route('documentos.revert', [$documento->id, $versionhistorial->id]) }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                    <td class="text-center">
                                    <a href="{{ route('documentos.revert', [$documento->id, $versionhistorial->id]) }}" 
                                    data-form-id="revert-form-{{ $versionhistorial->id }}"
                                    onclick="event.preventDefault(); submitRevertForm(this);" class="btn btn-light p-0"
                                    data-toggle="tooltip" data-placement="top" title="Revertir a esta versión">
                                        <i class="fa-solid fa-repeat"></i>
                                    </a>
                                    </td>
                                    <td class="text-center">
                                    <button type="button" class="btn btn-light p-0" onclick="viewVersion('{{ sprintf('https://%s.s3.%s.amazonaws.com/%s', env('AWS_BUCKET'), env('AWS_DEFAULT_REGION'), $versionhistorial->path) }}', '{{ pathinfo($versionhistorial->path, PATHINFO_EXTENSION) }}')"
                                    data-toggle="tooltip" data-placement="top" title="Ver esta version">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    </td>
                            </tr>
                        @endforeach
                    </table>
                </div>

            </div>

            <!-- Columna B: Botones y visor embebido -->
            <div id="colContenidoDocumento" class="col-12 col-md-9">
                <!-- Visor embebido -->
                <div id="visorContainer" style="margin-top: 20px;">
                    <iframe id="documentViewer" src="" style="width: 100%; height: 600px;" frameborder="0" allowfullscreen></iframe>
                </div>
            </div>
            
        </div>

    <!-- Modales -->
    <!-- Modal para actualizar documento -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Seleccionar nuevo archivo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <!-- Campo de archivo -->
                    <div class="form-group">
                        <label for="nuevoArchivo">Nuevo Archivo</label>
                        <input type="file" name="nuevoArchivo" id="nuevoArchivo" class="form-control">
                    </div>
                    <!-- Campo de contenido actualizado -->
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

    <!-- Modal de Confirmación para Aprobar documento -->
    <div class="modal fade" id="aprobarModal" tabindex="-1" role="dialog" aria-labelledby="aprobarModalLabel" aria-hidden="true">
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
                </div>
                <div class="modal-footer">
                    <form action="{{ route('documentos.aprobar', $documento->id) }}" method="POST">
                        @csrf
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Aprobar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para revertir versión-->
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="confirmModalLabel" aria-hidden="true">
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

</div>

@endsection

@section('scripting')
<script>

//Maneja la apertura de la modal para aprobar
$(document).ready(
function(){
    $('#AprobarModalBtn').on('click', function(){
        $('#aprobarModal').modal('show');
    });
});

//Maneja la apertura de la modal para descargar
$(document).ready(function(){
    $('#uploadModalBtn').on('click', function(){
        $('#uploadModal').modal('show');
    });
});

//Controla la accion en la modal de upload
document.getElementById('uploadBtn').addEventListener('click', function() {
    const fileInput = document.getElementById('nuevoArchivo');
    const contenidoActualizado = document.getElementById('contenidoActualizado').value;

    if (fileInput.files.length > 0 && contenidoActualizado.trim() !== '') {
        const form = document.getElementById('uploadForm');
        form.submit();
    } else {
        alert('Debe seleccionar un archivo para continuar.');
    }
});

//Controla la accion de revert
function submitRevertForm(link) {
    var formId = link.getAttribute('data-form-id');
    var form = document.getElementById(formId);

    if (form) {
        // Mostrar el modal de confirmación
        $('#confirmModal').modal('show');

        // Agregar un evento click al botón de confirmación
        document.getElementById('confirmButton').onclick = function() {
            form.submit();
        };
    } else {
        console.error('Formulario no encontrado:', formId);
    }
}

// Para previsualizar la versión historica
function viewVersion(url, extension) {
        var iframe = document.getElementById('documentViewer');
        var viewerUrl;

        switch (extension) {
            case 'pdf':
                viewerUrl = `https://docs.google.com/viewer?url=${url}&embedded=true`;
                break;
            case 'docx':
            case 'xlsx':
            case 'pptx':
                viewerUrl = `https://view.officeapps.live.com/op/embed.aspx?src=${url}`;
                break;
            default:
                alert('Formato no soportado para vista previa');
                return;
        }
        
        iframe.src = viewerUrl;
    }

// Para la tabla de arriba (versión actual)
document.querySelector('.btn-link').addEventListener('click', function() {
    // Desselecciona todas las filas en la tabla de versiones anteriores
    var rows = document.querySelectorAll('.table-row');
    rows.forEach(function(row) {
        row.classList.remove('selected-row');
    });
});

// Asigna el evento click a todas las filas de la tabla de versiones anteriores
document.querySelectorAll('.table-row').forEach(function(row) {
    row.addEventListener('click', function() {
        // Elimina la clase 'selected-row' de todas las filas
        document.querySelectorAll('.table-row').forEach(function(r) {
            r.classList.remove('selected-row');
        });

        // Añade la clase 'selected-row' a la fila actual
        this.classList.add('selected-row');
    });
});

//Para cargar el documento en el viewer apenas carga la vista 
document.addEventListener('DOMContentLoaded', function() {
    // Llame a viewVersion con la URL del documento actual y su extensión
    var currentDocumentUrl = '{{ sprintf('https://%s.s3.%s.amazonaws.com/%s', env('AWS_BUCKET'), env('AWS_DEFAULT_REGION'), $documento->path) }}';
    var currentDocumentExtension = '{{ pathinfo($documento->path, PATHINFO_EXTENSION) }}';
    viewVersion(currentDocumentUrl, currentDocumentExtension);
});

//Para manejar el colapso de la columna izquierda
document.getElementById('toggleDetails').addEventListener('click', function() {
    var colInfo = document.getElementById('colInfoDocumento');
    var colContent = document.getElementById('colContenidoDocumento');
    var collapseDetails = document.getElementById('collapseDetalles');

    if (colInfo.classList.contains('collapsed')) {
        // Expande la columna de información del documento
        colInfo.classList.remove('collapsed');
        colContent.classList.remove('expanded');
        collapseDetails.style.display = 'block';
    } else {
        // Colapsa la columna de información del documento
        colInfo.classList.add('collapsed');
        colContent.classList.add('expanded');
        collapseDetails.style.display = 'none';
    }
});

</script>

<style>
    .btn-custom {
        border: 2px solid #333;
        background-color: #e9ecef;
        color: #000;
        transition: background-color 0.3s ease, box-shadow 0.3s ease; /* Efecto de transición suave */
    }

    .btn-custom:hover {
        background-color: #dcdcdc; /* Color de fondo al pasar el mouse */
        color: #000;
        border-color: #666;
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); /* Añadir sombra al pasar el mouse */
    }

    .mt-5 {
        margin-top: 3rem !important; /* Añade margen superior para que el grupo de botones no quede tapado */
    }

    /* Estilos para animar el colapso de derecha a izquierda */
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
        display: none; /* Oculto por defecto */
        position: fixed; /* Posición fija para cubrir toda la pantalla */
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5); /* Fondo semi-transparente */
        z-index: 9999; /* Asegura que esté por encima de todos los elementos */
        text-align: center;
        color: white;
    }

    #overlay div {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%); /* Centrar el texto */
    }

</style>

@endsection

</body>
</html>