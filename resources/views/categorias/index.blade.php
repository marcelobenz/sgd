@extends('layouts.main')

@section('heading')
<style>

/* Estilo para las categorías padre */
.categoria-parent {
    background-color: #f1f1f1; /* Mismo color que el grupo en la vista de documentos */
    font-weight: bold;
}

/* Estilo para las subcategorías con identación */
.subcategoria td:first-child {
    /*background-color: #e9ecef;*/ /* Color más claro para subcategorías */
    padding-left: 30px; /* Sangría para subcategorías */
}

/* Alinear las acciones sin indentación */
.categoria-parent td:last-child,
.subcategoria td:last-child {
    padding-left: 0; /* Sin sangría para las acciones */
    text-align: left; /* Alinear las acciones */
}

/* Icono de carpeta */
.fa-folder, .fa-folder-open {
    margin-right: 8px;
    color: #6c757d; /* Color gris similar a la vista de documentos */
}

</style>

@endsection

@section('contenidoPrincipal')
<div class="container-fluid px-3" style="margin-top: 40px;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <a href="{{ route('categorias.create') }}" class="btn btn-custom" style="margin-bottom: 5px; margin-top: 40px;">
            <i class="fa-regular fa-file-lines"></i> Nueva Categoría
        </a>
        @if(session('success'))
            <div id="success-alert" class="alert alert-success mt-4 mb-0 ml-3">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger mb-0 ml-3 mt-4">
                {{ session('error') }}
            </div>
        @endif    
    </div>
    <!-- Tabla para las categorías -->
    <table id="categoriasTable" class="table table-bordered table-striped responsive">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Categoría Padre</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($categorias as $categoria)
                <!-- Categoría padre -->
                <tr class="categoria-parent" data-id="{{ $categoria->id }}">
                    <td>
                        <i class="fa fa-folder"></i> {{ $categoria->nombre_categoria }}
                    </td>
                    <td>{{ $categoria->parent ? $categoria->parent->nombre_categoria : 'Sin padre' }}</td>
                    <td>
                        <a href="{{ route('categorias.edit', $categoria->id) }}" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Editar">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </a>
                        <form action="{{ route('categorias.destroy', $categoria->id) }}" method="POST" style="display:inline;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Eliminar">
                                <i class="fa-regular fa-circle-xmark"></i>
                            </button>
                        </form>
                    </td>
                </tr>

                <!-- Subcategorías -->
                @foreach($categoria->subcategorias as $subcategoria)
                    <tr class="subcategoria" data-parent-id="{{ $categoria->id }}">
                        <td>
                            <i class="fa fa-folder-open"></i> {{ $subcategoria->nombre_categoria }}
                        </td>
                        <td>{{ $categoria->nombre_categoria }}</td>
                        <td>
                            <a href="{{ route('categorias.edit', $subcategoria->id) }}" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Editar">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </a>
                            <form action="{{ route('categorias.destroy', $subcategoria->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Eliminar">
                                    <i class="fa-regular fa-circle-xmark"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@section('scripting')
<script>
$(document).ready(function() {
    // Inicializar DataTables con agrupación por la columna Categoría Padre
    var table = $('#categoriasTable').DataTable({
        "order": [[1, "asc"], [0, "asc"]], // Ordenar por Categoría Padre primero, luego por Nombre
        "columnDefs": [
            { "orderable": false, "targets": 2 } // Deshabilitar la ordenación en la columna de acciones
        ],
        paging: true,
        ordering: true,
        searching: true,
        pageLength: 8,
        lengthMenu: [
            [8, 20, 50, -1],
            [8, 20, 50, "Todos"]
        ],
        rowGroup: {
            dataSrc: function(row) {
                // Si la categoría padre es "Sin padre", no agrupar
                return row[1] !== 'Sin padre' && row[1] !== null ? row[1] : '';
            },
            emptyDataGroup: null // Esto asegura que no se muestre "No group"
        },
        "drawCallback": function(settings) {
            // Ocultar las filas que no tienen categoría padre (las categorías principales)
            $('#categoriasTable tbody tr').each(function() {
                var parentCategory = $(this).find('td').eq(1).text(); // Obtener la categoría padre
                if (parentCategory === 'Sin padre') {
                    $(this).hide(); // Ocultar filas sin categoría padre
                }
            });
        }
    });
});

</script>
@endsection
