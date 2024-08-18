@extends('layouts.main')

@section('heading')
@endsection

@section('contenidoPrincipal')
<div class="container">
    <h1>Crear Documento</h1>

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
            @foreach($usuarios as $usuario)
                <div class="form-check">
                    <input type="checkbox" name="permisos[{{ $usuario->id }}][leer]" class="form-check-input" id="permiso_leer_{{ $usuario->id }}">
                    <label class="form-check-label" for="permiso_leer_{{ $usuario->id }}">Leer ({{ $usuario->name }})</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="permisos[{{ $usuario->id }}][escribir]" class="form-check-input" id="permiso_escribir_{{ $usuario->id }}">
                    <label class="form-check-label" for="permiso_escribir_{{ $usuario->id }}">Escribir ({{ $usuario->name }})</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="permisos[{{ $usuario->id }}][aprobar]" class="form-check-input" id="permiso_aprobar_{{ $usuario->id }}">
                    <label class="form-check-label" for="permiso_aprobar_{{ $usuario->id }}">Aprobar ({{ $usuario->name }})</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="permisos[{{ $usuario->id }}][eliminar]" class="form-check-input" id="permiso_eliminar_{{ $usuario->id }}">
                    <label class="form-check-label" for="permiso_eliminar_{{ $usuario->id }}">Eliminar ({{ $usuario->name }})</label>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
@endsection
