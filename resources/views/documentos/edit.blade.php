@extends('layouts.main')

@section('heading')
    Editar Documento
@endsection

@section('contenidoPrincipal')
<div class="container">
    <h1>Editar Documento</h1>

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

        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
    </form>
</div>
@endsection
