@extends('layouts.main')

@section('heading', 'Editar categoría')

@section('contenidoPrincipal')
    @include('categorias._form', [
        'titulo' => 'Editar categoría',
        'descripcion' => 'Actualizá el nombre o cambiá su ubicación dentro de la jerarquía.',
        'accion' => route('categorias.update', $categoria),
        'metodo' => 'PUT',
        'textoBoton' => 'Guardar cambios',
        'nombreActual' => $categoria->nombre_categoria,
    ])
@endsection
