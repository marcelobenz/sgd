@extends('layouts.main')

@section('heading', 'Nueva categoría')

@section('contenidoPrincipal')
    @include('categorias._form', [
        'titulo' => 'Nueva categoría',
        'descripcion' => 'Creá una categoría principal o ubicala dentro de la estructura existente.',
        'accion' => route('categorias.store'),
        'metodo' => null,
        'textoBoton' => 'Crear categoría',
        'nombreActual' => '',
    ])
@endsection
