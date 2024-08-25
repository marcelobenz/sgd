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
            <div id="colInfoDocumento" class="col-12 col-md-3">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th colspan=2>Versión Actual</th>
                        </tr>
                    </thead>
                    <tr>
                        <td>Documento:</td><td>{{ $documento->titulo }}</td>
                    </tr>
                    <tr>
                        <td>Versión: </td><td>{{ $documento->version }}
                            <button type="button" class="btn btn-light btn-link p-0" onclick="viewVersion('https://repositorio-sgd.s3.us-west-2.amazonaws.com/{{ $documento->path }}', '{{ pathinfo($documento->path, PATHINFO_EXTENSION) }}')">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td>Categoría: </td><td>{{ $documento->categoria->nombre_categoria }}</td>
                    </tr>
                    <tr>
                        <td>Estado: </td><td>
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
                            <span style="border: 2px solid {{ $estadoColor }}; color: {{ $estadoColor }}; font-weight: bold; padding: 5px; border-radius: 4px; display: inline-block; width: 80%; text-align: center;">
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
                </table>
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th colspan=4>Versiones Anteriores</th>
                        </tr>
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
                                onclick="event.preventDefault(); submitForm(this);" class="btn btn-light p-0">
                                    <i class="fa-solid fa-repeat"></i>
                                </a>
                                </td>
                                <td class="text-center">
                                <button type="button" class="btn btn-light p-0" onclick="viewVersion('https://repositorio-sgd.s3.us-west-2.amazonaws.com/{{ $versionhistorial->path }}', '{{ pathinfo($versionhistorial->path, PATHINFO_EXTENSION) }}')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                </td>
                        </tr>
                    @endforeach
                </table>
            </div>
            
            <!-- Columna B: Botones y visor embebido -->
            <div id="colContenidoDocumento" class="col-12 col-md-9">
                <div class="d-flex justify-content-end">
                    <div class="btn-group w-100" role="group" aria-label="Basic mixed styles example">
                        <a href="{{ route('documentos.download', $documento->id) }}" class="btn btn-secondary"><i class="fa-solid fa-cloud-arrow-down"></i> Descargar</a>
                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#uploadModal"><i class="fa-solid fa-cloud-arrow-up"></i> Subir Nueva Versión</button>
                        <button type="button" class="btn btn-secondary" data-toggle="modal" data-target="#aprobarModal"><i class="fa-regular fa-thumbs-up"></i> Aprobar Documento</button>
                        <a href="{{ route('documentos.index') }}" class="btn btn-secondary"><i class="fa-regular fa-hand-point-left"></i> Volver</a>
                    </div>
                </div>
                
                <!-- Visor embebido -->
                <div id="viewer-container">
                    @if ($fileExtension === 'pdf')
                        <embed id="document-viewer" src="{{ $fileUrl }}" type="application/pdf" width="100%" height="600px" />
                    @elseif (in_array($fileExtension, ['doc', 'docx', 'xls', 'xlsx']))
                        <iframe id="document-viewer" src="https://view.officeapps.live.com/op/view.aspx?src={{ urlencode($fileUrl) }}" width="100%" height="600px"></iframe>
                    @else
                        <p>Formato de archivo no soportado para visualización en el visor embebido.</p>
                    @endif
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
                    <input type="file" name="nuevoArchivo" id="nuevoArchivo" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="uploadBtn">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

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
    </form>

</div>

@endsection

@section('scripting')
<script>
document.getElementById('uploadBtn').addEventListener('click', function() {
    const fileInput = document.getElementById('nuevoArchivo');
    if (fileInput.files.length > 0) {
        const form = document.getElementById('uploadForm');
        form.submit();
    } else {
        alert('Debe seleccionar un archivo para continuar.');
    }
});

// Para el manejo  del revertir version
function submitForm(link) {
    var formId = link.getAttribute('data-form-id');
    console.log('Form ID:', formId);
    console.log('HTML del cuerpo:', document.body.innerHTML);  // Para depuración
    var form = document.getElementById(formId);
    console.log('Form:', form);
    if (form) {
        form.submit();
    } else {
        console.error('Formulario no encontrado:', formId);
    }
}

// Para previsualizar la versión historica
function viewVersion(fileUrl, fileExtension) {
    var viewerContainer = document.getElementById('viewer-container');
    var viewerContent;

    // Limpia cualquier selección previa en todas las filas de la tabla de versiones anteriores
    var rows = document.querySelectorAll('.table-row');
    rows.forEach(function(row) {
        row.classList.remove('selected-row');
    });

    // Aquí puedes añadir el código para resaltar la fila actual si es necesario

    // Configura el visor para mostrar el archivo seleccionado
    if (fileExtension === 'pdf') {
        viewerContent = `<embed id="document-viewer" src="${fileUrl}" type="application/pdf" width="100%" height="600px" />`;
    } else if (fileExtension === 'doc' || fileExtension === 'docx') {
        viewerContent = `<iframe id="document-viewer" src="https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileUrl)}" width="100%" height="600px"></iframe>`;
    } else if (fileExtension === 'xls' || fileExtension === 'xlsx') {
        viewerContent = `<iframe id="document-viewer" src="https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(fileUrl)}" width="100%" height="600px"></iframe>`;
    } else {
        viewerContent = '<p>Formato de archivo no soportado para visualización en el visor embebido.</p>';
    }

    viewerContainer.innerHTML = viewerContent;
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

// Para solicitar confirmación antes del revert version
function submitForm(link) {
    var formId = link.getAttribute('data-form-id');
    var form = document.getElementById(formId);

    if (form) {
        // Usar window.confirm para pedir confirmación
        var confirmAction = window.confirm("¿Estás seguro de que deseas revertir a esta versión?");
        if (confirmAction) {
            form.submit();
        }
    } else {
        console.error('Formulario no encontrado:', formId);
    }
}

</script>

<style>
    .word-container {
        width: 100%;
        height: 800px; /* Ajusta la altura según sea necesario */
        overflow: auto;
        border: 1px solid #ddd; /* Opcional, para agregar un borde */
        padding: 10px; /* Opcional, para agregar algo de espacio interior */
    }
    
    .excel-container {
        width: 100%;
        height: 800px; /* Ajusta la altura según sea necesario */
        overflow: auto;
        border: 1px solid #ddd; /* Opcional, para agregar un borde */
        padding: 10px; /* Opcional, para agregar algo de espacio interior */
    }
</style>

@endsection

</body>
</html>