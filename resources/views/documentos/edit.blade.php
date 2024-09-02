@extends('layouts.main')

@section('heading')
    Editar Documento
@endsection

@section('contenidoPrincipal')
<div class="container">
    <div class="w-100" style="background-color: #f8f9fa;">
        <h2 class="text-center">Editar Documento</h2>
    </div>

    <form action="{{ route('documentos.update', $documento->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" name="titulo" id="titulo" class="form-control" value="{{ $documento->titulo }}" required>
        </div>

        <div class="form-group">
            <label for="id_categoria">Categoría</label>
            <select name="id_categoria" id="id_categoria" class="form-control" required>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" {{ $documento->id_categoria == $categoria->id ? 'selected' : '' }}>
                        {{ $categoria->nombre_categoria }}
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#collapsePermisos" aria-expanded="false" aria-controls="collapsePermisos">
            Asignar Permisos
        </button>

        <div class="collapse" id="collapsePermisos">
            <div class="form-group">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Usuario (Correo)</th>
                            <th>Leer</th>
                            <th>Escribir</th>
                            <th>Aprobar</th>
                            <th>Eliminar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usuarios as $usuario)
                            @php
                                $permisoActual = $documento->permisos->where('user_id', $usuario->id)->first();
                            @endphp
                            <tr>
                                <td>{{ $usuario->email }}</td>
                                <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_leer]" {{ $permisoActual && $permisoActual->puede_leer ? 'checked' : '' }}></td>
                                <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_escribir]" {{ $permisoActual && $permisoActual->puede_escribir ? 'checked' : '' }}></td>
                                <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_aprobar]" {{ $permisoActual && $permisoActual->puede_aprobar ? 'checked' : '' }}></td>
                                <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_eliminar]" {{ $permisoActual && $permisoActual->puede_eliminar ? 'checked' : '' }}></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        <button type="button" class="btn btn-secondary" onclick="confirmAndRedirect();">Volver</button>
    </form>
</div>
@endsection
@section('scripting')
<script>
    function confirmAndRedirect() {
        Swal.fire({
            title: 'Volver sin guardar',
            text: 'Los datos cargados se perderán ¿Estás seguro de que deseas volver?',
            showCancelButton: true,
            confirmButtonText: 'Sí, volver',
            cancelButtonText: 'Cancelar',
            customClass: {
            confirmButton: 'btn btn-warning', // Cambia 'btn btn-danger' al color que desees
            cancelButton: 'btn btn-primary' // Cambia 'btn btn-secondary' al color que desees
        },
        buttonsStyling: false         
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('documentos.index') }}";
            }
        });
    }
</script>
@endsection