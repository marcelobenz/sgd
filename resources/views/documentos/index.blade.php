@extends('layouts.main')

@section('heading')

<style>
tr.dtrg-group {
    background-color: #f1f1f1;
    font-weight: bold;
    cursor: pointer; /* Esto añade un cursor de mano para indicar que es interactivo */
}
/* Ajustar el alto de las filas en la DataTable */
</style>

@endsection

@section('contenidoPrincipal')
<div class="container-fluid px-3" style="margin-top: 40px;">
    <div style="margin-top: 40px;"></div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <a href="{{ route('documentos.create') }}" class="btn btn-secondary" style="margin-bottom: 5px; margin-top: 20px;"><i class="fa-regular fa-file-lines"></i> Nuevo Documento</a>
        @if(session('success'))
        <div id="success-alert" class="alert alert-success mb-0 ml-3">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger mb-0 ml-3">
            {{ session('error') }}
            <!-- <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button> -->
        </div>
        @endif    
    </div>    
    <table class="display compact" id="documentosTable" style="margin-top: 1px; width:100%">
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
                <tr data-category="{{ $documento->categoria->nombre_categoria }}">
                    <td>{{ $documento->titulo }} (v{{ $documento->version }}) </td>
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
                        <span style="border: 2px solid {{ $estadoColor }}; color: {{ $estadoColor }}; font-weight: bold; padding: 5px; border-radius: 4px; display: inline-block; width: 80%; text-align: center;">

                            {{ $documento->estado }}
                        </span>
                    </td>
                    <td>{{ $documento->categoria->nombre_categoria }}</td>
                    <td>{{ $documento->created_at }}</td>
                    <td>{{ $documento->updated_at }}</td>
                    <td>{{ $documento->ultimaModificacion->name }}</td>
                    <td>
                        <a href="{{ route('documentos.validaPermiso', ['id' => $documento, 'ruta' => 'documentos.show', 'permiso' => 'puedeLeer']) }}" class="btn btn-light"><i class="fa-solid fa-eye"></i></a>
                        <a href="{{ route('documentos.validaPermiso', ['id' => $documento, 'ruta' => 'documentos.edit', 'permiso' => 'puedeEscribir']) }}" class="btn btn-light"><i class="fa-regular fa-pen-to-square"></i></a>

                        <!-- Botón de eliminación con confirmación -->
                        <!-- <form action="{{ route('documentos.destroy', $documento) }}" method="POST" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este documento?');"> -->
                        <form id="delete-form-{{ $documento->id }}" action="{{ route('documentos.destroy', $documento) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light" onclick="confirmAndRedirect(event,document.getElementById('delete-form-{{ $documento->id }}'));"><i class="fa-regular fa-circle-xmark"></i></button>
                            <!-- <button type="submit" class="btn btn-light"><i class="fa-regular fa-circle-xmark"></i></button> -->
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
// Manejo de datatable
$(document).ready(function() {
    var table = $('#documentosTable').DataTable({
        paging: true,
        ordering: true,
        searching: true,
        pageLength: -1,
        lengthMenu: [
            [8, 20, 50, -1],
            [8, 20, 50, "Todos"]
        ],
        order: [[2, 'asc']], // Ordena por la columna de categoría
        rowGroup: {
            dataSrc: 2 // Agrupa por la columna de categoría (índice 2)
        }
    });

    // Manejador para expandir/cerrar filas al hacer clic en la fila agrupada
    $('#documentosTable tbody').on('click', 'tr.dtrg-group', function() {
        var groupName = $(this).children('td').text(); // Obtener el nombre del grupo (categoría)
        var rows = table.rows().nodes(); // Obtener todas las filas de la tabla

        $(rows).each(function() {
            var data = table.row(this).data();
            if (data && data[2] === groupName) { // Si la fila pertenece a la categoría clicada
                $(this).toggle(); // Alternar la visibilidad de la fila
            }
        });

        $(this).toggleClass('expanded'); // Alternar la clase para indicar que está expandido/colapsado
    });

    // Ocultar todas las filas de documentos inicialmente
    // $('#documentosTable tbody tr').each(function() {
    //     if (!$(this).hasClass('dtrg-group')) {
    //         $(this).hide(); // Oculta todas las filas de documentos
    //     }
    // });

    // Inicialmente ocultar todas las filas agrupadas
    // table.rows().every(function() {
    //     var row = this.node();
    //     if ($(row).hasClass('dtrg-group')) {
    //         return; // No ocultar las filas de grupo
    //     }
    //     $(row).hide();
    // });

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

    // Para el manejo del tiempo del mensaje de alerta
    document.addEventListener("DOMContentLoaded", function() {
        var alert = document.getElementById('success-alert');
        if (alert) {
            setTimeout(function() {
                alert.style.transition = 'opacity 1s ease';
                alert.style.opacity = '0';
                setTimeout(function() {
                    alert.style.display = 'none';
                }, 1000);
            }, 2000);
        }
    });
});

function confirmAndRedirect(event, form) {
    event.preventDefault();
    Swal.fire({
        title: 'Confirmación',
        text: '¿Estás seguro de que deseas eliminar este documento?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

</script>

@endsection
