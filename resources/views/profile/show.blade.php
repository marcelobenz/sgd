@extends('layouts.main')

@section('heading')
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
@endsection

@section('contenidoPrincipal')
<div class="container mt-4">
    <h1>Mi Perfil</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Nombre</label>
            <input type="text" id="name" name="name" class="form-control" value="{{ $user->name }}" required>
        </div>
        <div class="form-group">
            <label for="email">Correo Electrónico</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ $user->email }}" required>
        </div>
        <div class="form-group">
            <label for="password">Contraseña (dejar en blanco si no desea cambiarla)</label>
            <input type="password" id="password" name="password" class="form-control">
        </div>
        <div class="form-group">
            <label for="password_confirmation">Confirmar Contraseña</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control">
        </div>
        <button type="submit" class="btn btn-primary">Actualizar</button>
    </form>
</div>
@endsection
