@extends('layouts.main')

@section('heading')

<style>
    /* Estilo para las filas agrupadas */
    tr.dtrg-group {
        background-color: #f1f1f1;
        font-weight: normal;
        cursor: pointer; /* Añade un cursor de mano para indicar que es interactivo */
    }

    /* General para todas las columnas */
    #documentosTable th,
    #documentosTable td {
        white-space: nowrap; /* Evita que el texto se ajuste a varias líneas */
        overflow: hidden; /* Oculta contenido desbordado */
        text-overflow: ellipsis; /* Muestra puntos suspensivos para texto largo */
    }

    /* Mantener el diseño fijo */
    #documentosTable {
        table-layout: fixed; /* Controlar las proporciones de las columnas */
        width: 100%; /* Usar el ancho total disponible */
        /*border-collapse: collapse;*/ /* Ajusta las líneas de las celdas */
    }

    /* Columna Título */
    #documentosTable th:nth-child(1),
    #documentosTable td:nth-child(1) {
        width: 30%; /* Título */
    }

    /* Columna Estado */
    #documentosTable th:nth-child(2),
    #documentosTable td:nth-child(2) {
        width: 10%; /* Estado */
        text-align: center; /* Centrar texto */
    }

    /* Columna Categoría */
    #documentosTable th:nth-child(3),
    #documentosTable td:nth-child(3),
    #documentosTable th:nth-child(4),
    #documentosTable td:nth-child(4)
    {
        width: 15%; /* Categoría */
    }

    /* Columnas Fecha */
    #documentosTable th:nth-child(5),
    #documentosTable td:nth-child(5) {
        width: 10%; /* Fechas */
        text-align: center; /* Centrar texto */
    }

    /* Columna Usuario */
    #documentosTable th:nth-child(6),
    #documentosTable td:nth-child(6) {
        width: 10%; /* Usuario */
    }

    /* Columna Acciones */
    #documentosTable th:nth-child(7),
    #documentosTable td:nth-child(7) {
        width: 10%; /* Acciones */
        text-align: center; /* Centrar botones */
    }

    /* Ajustes de estilo para la tabla en pantallas pequeñas */
    @media (max-width: 768px) {
        #documentosTable th, #documentosTable td {
            font-size: 12px;
            padding: 5px;
        }

        .btn {
            font-size: 10px; /* Botones más pequeños */
            padding: 2px 4px;
        }

        /* Ocultar columnas específicas en pantallas pequeñas */
        #documentosTable th:nth-child(1), /* Título */
        #documentosTable td:nth-child(1),
        #documentosTable th:nth-child(3), /* Categoría */
        #documentosTable td:nth-child(3),
        #documentosTable th:nth-child(6), /* Usuario */
        #documentosTable td:nth-child(6) {
            display: none; /* Ocultar estas columnas en pantallas pequeñas */
        }
    }

    /* Estilos para los estados */
    .estado {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 4px;
        font-size: 12px;
        color: white;
        text-align: center;
    }

    .estado-en-curso {
        background-color: orange;
    }

    .estado-pendiente {
        background-color: red;
    }

    .estado-aprobado {
        background-color: green;
    }

    .estado-registro {
        background-color: blue;
    }

    /* Estilo para el switch */
    .switch {
        position: relative;
        display: inline-block;
        width: 34px;
        height: 20px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
        border-radius: 20px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 14px;
        width: 14px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(14px);
    }
</style>


@endsection

@section('contenidoPrincipal')
<div class="container-fluid px-3" style="margin-top: 40px;">
    <div style="margin-top: 40px;"></div>
    <div class="d-flex justify-content-between align-items-center mb-2">
    <label class="switch" style="margin-top: 30px;">
        <input type="checkbox" id="toggleExpandAll">
        <span class="slider round"></span>
    </label>

    <a href="{{ route('documentos.create') }}" class="btn btn-custom" style="margin-bottom: 5px; margin-top: 40px;"><i class="fa-regular fa-file-lines"></i> Nuevo Documento</a>

        @if(session('success'))
        <div id="success-alert" class="alert alert-success" style="margin-top: 40px; margin-left: 15px;">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="alert alert-danger" style="margin-top: 40px; margin-left: 15px;">
            {{ session('error') }}
        </div>
        @endif    
    </div>    
    <table class="table table-bordered table-striped responsive compact" id="documentosTable" style="margin-top: 1px; width:100%">
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
                <tr data-category="{{ $documento->categoria->parent ? $documento->categoria->parent->nombre_categoria : 'Sin categoría padre' }}/{{ $documento->categoria->nombre_categoria }}">
                    <td>{{ $documento->titulo }} [v:{{ $documento->version }}] </td>
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
                                case 'registro':
                                    $estadoColor = 'blue';
                                    break;
                                default:
                                    $estadoColor = 'black';
                            }
                        @endphp
                        <span class="estado estado-{{ strtolower(str_replace(' ', '-', $documento->estado)) }}">
                            {{ $documento->estado }}
                        </span>
                    </td>
                    <td>{{ $documento->categoria->parent ? $documento->categoria->parent->nombre_categoria : '' }}/{{ $documento->categoria->nombre_categoria }}</td>
                    <td>{{ $documento->created_at }}</td>
                    <td>{{ $documento->updated_at }}</td>
                    <td>{{ $documento->ultimaModificacion->name }}</td>
                    <td>
                        <a href="{{ route('documentos.validaPermiso', ['id' => $documento, 'ruta' => 'documentos.show', 'permiso' => 'puedeLeer']) }}" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Ver"><i class="fa-solid fa-eye"></i></a>
                        <a href="{{ route('documentos.validaPermiso', ['id' => $documento, 'ruta' => 'documentos.edit', 'permiso' => 'puedeEscribir']) }}" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>

                        <form id="delete-form-{{ $documento->id }}" action="{{ route('documentos.destroy', $documento) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light" onclick="confirmAndRedirect(event,document.getElementById('delete-form-{{ $documento->id }}'));" data-toggle="tooltip" data-placement="top" title="Eliminar"><i class="fa-regular fa-circle-xmark"></i></button>
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
        },
        responsive: false,
        autoWidth: false, // Desactiva el ajuste automático del ancho
        columnDefs: [
            { width: "30%", targets: 0 }, // Título
            { width: "10%", targets: 1 }, // Estado
            { width: "15%", targets: 2 }, // Categoría
            { width: "10%", targets: 3 }, // Creado
            { width: "15%", targets: 4 }, // Modificado
            { width: "10%", targets: 5 }, // Usuario
            { width: "10%", targets: 6 }  // Acciones
        ]
    });

    // Expansión y contracción de categorías al hacer clic en la fila de agrupación
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

    // Funcionalidad de Expandir/Contraer todo
    $('#toggleExpandAll').on('change', function () {
        var isChecked = $(this).is(':checked'); // Verifica si el switch está activado
        var rows = table.rows().nodes(); // Obtiene todas las filas de la tabla

        $(rows).each(function () {
            var row = $(this);
            if (!row.hasClass('dtrg-group')) {
                if (isChecked) {
                    row.show(); // Expande todas las filas
                } else {
                    row.hide(); // Contrae todas las filas
                }
            }
        });

        // Cambia la clase de las categorías agrupadas
        if (isChecked) {
            $('tr.dtrg-group').addClass('expanded');
        } else {
            $('tr.dtrg-group').removeClass('expanded');
        }
    });

    // Ocultar todas las filas inicialmente (excepto las filas de agrupación)
    table.rows().every(function() {
        var row = this.node();
        if (!$(row).hasClass('dtrg-group')) {
            $(row).hide(); // Oculta todas las filas que no son de agrupación
        }
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
</script>
@endsection
