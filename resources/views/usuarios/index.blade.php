@extends('layouts.main')

@section('heading')
@endsection

@section('contenidoPrincipal')
    <div class="container" style="margin-top: 80px;">
        <div class="w-100 mb-4" style="background-color: #f8f9fa;">
            <h2 class="text-center">Gestión de Usuarios</h2>
        </div>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('usuarios.index') }}" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <input type="text" name="buscar" class="form-control" placeholder="Buscar por nombre o email"
                        value="{{ request('buscar') }}">
                </div>

                <div class="col-md-3">
                    <select name="estado" class="form-control">
                        <option value="">Todos</option>
                        <option value="habilitados" {{ request('estado') == 'habilitados' ? 'selected' : '' }}>
                            Habilitados
                        </option>
                        <option value="deshabilitados" {{ request('estado') == 'deshabilitados' ? 'selected' : '' }}>
                            Deshabilitados
                        </option>
                    </select>
                </div>

                <div class="col-md-5">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <a href="{{ route('usuarios.index') }}" class="btn btn-secondary">Limpiar</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Estado</th>
                        <th>Fecha registro</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>{{ $usuario->name }}</td>
                            <td>{{ $usuario->email }}</td>
                            <td>
                                @if ($usuario->habilitado)
                                    <span class="badge badge-success">Habilitado</span>
                                @else
                                    <span class="badge badge-danger">Deshabilitado</span>
                                @endif
                            </td>
                            <td>{{ optional($usuario->created_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-center">
                                @if (auth()->id() != $usuario->id)
                                    <form action="{{ route('usuarios.toggleHabilitado', $usuario->id) }}" method="POST"
                                        style="display:inline;">
                                        @csrf
                                        <button type="submit"
                                            class="btn btn-sm {{ $usuario->habilitado ? 'btn-warning' : 'btn-success' }}">
                                            {{ $usuario->habilitado ? 'Deshabilitar' : 'Habilitar' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="text-muted">Usuario actual</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
