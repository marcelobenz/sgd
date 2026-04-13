@extends('layouts.main')

@section('contenidoPrincipal')
<div class="container">
    <h1>Editar Categoría</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('categorias.update', $categoria->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="nombre_categoria">Nombre de la categoría</label>
            <input type="text" name="nombre_categoria" class="form-control" value="{{ $categoria->nombre_categoria }}" required>
        </div>
        <div class="form-group">
            <label for="parent_id">Categoría Padre (opcional)</label>
            <select name="parent_id" class="form-control">
                <option value="">Sin padre</option>
                @foreach ($categorias as $cat)
                    <option value="{{ $cat->id }}" {{ $cat->id == $categoria->parent_id ? 'selected' : '' }}>
                        {{ $cat->nombre_categoria }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
