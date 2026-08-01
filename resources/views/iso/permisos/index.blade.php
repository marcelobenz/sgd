@extends('layouts.main')
@include('iso._styles')
@section('heading','Permisos de Planificación')
@section('contenidoPrincipal')
<div class="iso-shell">
    @include('iso._alerts')
    <div class="iso-eyebrow">Administración</div>
    <h1 class="iso-title">Permisos de Planificación</h1>
    <p class="iso-subtitle">Asigná un nivel simple por usuario. Los administradores del SGD siempre tienen acceso completo.</p>
    <div class="iso-panel">
        <div class="table-responsive">
            <table class="table iso-table mb-0">
                <thead><tr><th>Usuario</th><th>Correo</th><th>Nivel</th><th></th></tr></thead>
                <tbody>
                @foreach($usuarios as $usuario)
                    @php
                        $nivel = $usuario->isAdmin() ? 'administracion' : ($usuario->permisoIso?->puede_administrar ? 'administracion' : ($usuario->permisoIso?->puede_gestionar ? 'gestion' : ($usuario->permisoIso?->puede_ver ? 'consulta' : 'sin_acceso')));
                    @endphp
                    <tr>
                        <td><strong>{{ $usuario->name }}</strong></td><td>{{ $usuario->email }}</td>
                        <td>
                            @if($usuario->isAdmin())
                                Administración <small class="text-muted">(por rol SGD)</small>
                            @else
                                <form method="POST" action="{{ route('planificacion.permisos.update',$usuario) }}" class="form-inline justify-content-end justify-content-md-start">
                                    @csrf @method('PUT')
                                    <select name="nivel" class="form-control mr-2">
                                        <option value="sin_acceso" @selected($nivel==='sin_acceso')>Sin acceso</option>
                                        <option value="consulta" @selected($nivel==='consulta')>Solo consulta</option>
                                        <option value="gestion" @selected($nivel==='gestion')>Gestión</option>
                                        <option value="administracion" @selected($nivel==='administracion')>Administración</option>
                                    </select>
                                    <button class="btn btn-outline-primary">Guardar</button>
                                </form>
                            @endif
                        </td><td></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
