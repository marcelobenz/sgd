@extends('main')

@section('heading')
<!-- Incluir DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
@endsection

@section('contenidoPrincipal')
<div class="container-fluid px-3" style="margin-top: 20px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('documentos.create') }}" class="btn btn-primary" style="margin-bottom: 20px; margin-top: 20px;">Crear Documento</a>
        @if(session('success'))
        <div id="success-alert" class="alert alert-success mb-0 ml-3">
            {{ session('success') }}
        </div>
        @endif
    </div>    
    <table class="table" id="documentosTable" style="margin-top: 20px;">
        <thead>
            <tr>
                <th>Título</th>
                <th>Estado</th>
                <th>Categoria</th>
                <th>Creado</th>
                <th>Modificado</th>
                <th>Usuario</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documentos as $documento)
                <tr>
                    <td>{{ $documento->titulo }}</td>
                    <td>
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
                        <span style="background-color: {{ $estadoColor }}; color: white; font-weight: bold; padding: 5px; border-radius: 4px;">
                            {{ $documento->estado }}
                        </span>
                    </td>
                    <td>{{ $documento->categoria->nombre_categoria }}</td>
                    <td>{{ $documento->created_at }}</td>
                    <td>{{ $documento->updated_at }}</td>
                    <td>{{ $documento->ultimaModificacion->name }}</td>
                    <td>
                        <a href="{{ route('documentos.show', $documento) }}" class="btn btn-info">Ver</a>
                        <a href="{{ route('documentos.edit', $documento) }}" class="btn btn-warning">Editar</a>
                        <!-- Botón de eliminación con confirmación -->
                        <form action="{{ route('documentos.destroy', $documento) }}" method="POST" style="display:inline;" onsubmit="event.preventDefault(); confirmDelete(this);">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Modal de confirmación para borrado de documentos-->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" role="dialog" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas eliminar este documento?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripting')
<script>
//Manejo de datatable
$(document).ready(function() {
    $('#documentosTable').DataTable({
        paging: true, // Activa el paginado
        ordering: true, // Activa el ordenamiento
        searching: true, // Activa la búsqueda
        pageLength: 8, // Número de filas por página
        lengthMenu: [
            [8, 20, 50, -1], // Opciones de número de entradas
            [8, 20, 50, "Todos"] // Texto que se mostrará en el menú
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' // Traducción al español
        },
        order: [[0, 'asc']], // Ordena por la primera columna (Título) de forma ascendente por defecto
    });
});

// Manejo de borrado de documentos
let deleteForm;
function confirmDelete(form) {
    deleteForm = form; // Guarda el formulario que se va a enviar
    $('#confirmDeleteModal').modal('show'); // Muestra el modal de confirmación
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteForm) {
        deleteForm.submit(); // Envía el formulario cuando se confirme la acción
    }
});

// function confirmDelete() {
//     return confirm('¿Estás seguro de que deseas eliminar este documento? Esta acción no se puede deshacer.');
// }

// Para el manejo del tiempo del mensaje de alerta
// Espera a que el DOM se haya cargado completamente
document.addEventListener("DOMContentLoaded", function() {
    // Selecciona el div de alerta
    var alert = document.getElementById('success-alert');
    if (alert) {
        // Configura un temporizador para ocultar el mensaje después de 5 segundos (5000 milisegundos)
        setTimeout(function() {
            alert.style.transition = 'opacity 1s ease';
            alert.style.opacity = '0';
            // Después de la transición (1 segundo), oculta el elemento
            setTimeout(function() {
                alert.style.display = 'none';
            }, 1000);
        }, 2000);
    }
});

</script>
<!-- Incluir jQuery y DataTables JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

@endsection
