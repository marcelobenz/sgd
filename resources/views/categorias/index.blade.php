@extends('layouts.main')

@section('heading')
@endsection

@section('contenidoPrincipal')
<div class="container-fluid px-3" style="margin-top: 100px;">
    <div style="margin-top: 40px;"></div>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <a href="{{ route('categorias.create') }}" class="btn btn-primary">Crear Categoría</a>
            @if(session('success'))
                <div id="success-alert" class="alert alert-success mb-0 ml-3">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger mb-0 ml-3">
                    {{ session('error') }}
                </div>
            @endif    
        </div>
        <!-- Tabla para las categorías -->
        <table id="categoriasTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Categoría Padre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categorias as $categoria)
                    <tr>
                        <td>{{ $categoria->id }}</td>
                        <td>{{ $categoria->nombre_categoria }}</td>
                        <td>{{ $categoria->parent ? $categoria->parent->nombre_categoria : 'Sin padre' }}</td>
                        <td>
                            <a href="{{ route('categorias.edit', $categoria->id) }}" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                            <form action="{{ route('categorias.destroy', $categoria->id) }}" method="POST" style="display:inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Eliminar"><i class="fa-regular fa-circle-xmark"></i></button>
                            </form>
                        </td>
                    </tr>
                    @foreach($categoria->subcategorias as $subcategoria)
                        <tr>
                            <td>{{ $subcategoria->id }}</td>
                            <td>{{ $subcategoria->nombre_categoria }}</td>
                            <td>{{ $categoria->nombre_categoria }}</td>
                            <td>
                                <a href="{{ route('categorias.edit', $subcategoria->id) }}" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Editar"><i class="fa-regular fa-pen-to-square"></i></a>
                                <form action="{{ route('categorias.destroy', $subcategoria->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-light" data-toggle="tooltip" data-placement="top" title="Eliminar"><i class="fa-regular fa-circle-xmark"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('#categoriasTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            },
            "order": [[ 0, "asc" ]], // Ordenar por la primera columna (ID)
            "columnDefs": [
                { "orderable": false, "targets": 3 } // Deshabilitar la ordenación en la columna de acciones
            ]
        });
    });
</script>
@endsection
