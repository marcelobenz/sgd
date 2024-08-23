@extends('layouts.main')

@section('heading')
@endsection

@section('contenidoPrincipal')
<div class="container" style="margin-top: 80px;">
    <div class="w-100" style="background-color: #f8f9fa;">
        <h2 class="text-center">Nuevo Documento</h2>
    </div>

    <form action="{{ route('documentos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group">
            <label for="titulo">Título</label>
            <input type="text" name="titulo" id="titulo" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="archivo">Archivo</label>
            <input type="file" name="archivo" id="archivo" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="id_categoria">Categoría</label>
            <select name="id_categoria" id="id_categoria" class="form-control" required>
                @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->nombre_categoria }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="contenido">Contenido</label>
            <textarea name="contenido" id="contenido" class="form-control"></textarea>
        </div>

        <div class="form-group">
            <label for="permisos">Asignar Permisos</label>
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
                        <tr>
                            <td>{{ $usuario->email }}</td>
                            <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_leer]" ></td>
                            <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_escribir]"></td>
                            <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_aprobar]"></td>
                            <td><input type="checkbox" name="permisos[{{ $usuario->id }}][puede_eliminar]"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
        <button type="button" class="btn btn-secondary" onclick="confirmAndRedirect();">Volver</button>

    </form>
</div>
@endsection

@section('scripting')
<script>
    function confirmAndRedirect() {
        Swal.fire({
            title: 'Confirmación',
            text: 'Los datos cargados se perderán ¿Estás seguro de que deseas volver?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, volver',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "{{ route('documentos.index') }}";
            }
        });
    }
</script>
@endsection