@extends('layouts.main')

@section('contenidoPrincipal')
<div class="container" style="margin-top: 80px;">

    <h1>Crear Categoría</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('categorias.store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="nombre_categoria">Nombre de la categoría</label>
            <input type="text" name="nombre_categoria" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="parent_id">Categoría Padre (opcional)</label>
            <select name="parent_id" class="form-control">
                <option value="">Sin padre</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->nombre_categoria }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Guardar</button>
    </form>
</div>
@endsection
