@extends('layouts.main')

@section('contenidoPrincipal')
<div class="container" style="margin-top: 80px;">
    <div class="mb-4">
        <h2 class="mb-1">Vacaciones</h2>
        <p class="text-muted">Seleccioná la función que necesitás.</p>
    </div>
    <div class="row">
        <div class="col-md-4 mb-3">
            <a href="{{ route('vacaciones.mis') }}" class="text-decoration-none">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <i class="fas fa-calendar-check fa-2x text-primary mb-3"></i>
                    <h4>Solicitud / estado de licencias</h4>
                    <p class="text-muted mb-0">Consultá tus días, solicitá un período o cancelá una solicitud pendiente.</p>
                </div></div>
            </a>
        </div>
        @if(auth()->user()->puedeGestionarVacaciones())
            <div class="col-md-4 mb-3">
                <a href="{{ route('vacaciones.admin') }}" class="text-decoration-none">
                    <div class="card h-100 shadow-sm"><div class="card-body">
                        <i class="fas fa-users-cog fa-2x text-success mb-3"></i>
                        <h4>Admin de licencias</h4>
                        <p class="text-muted mb-0">Revisá solicitudes y consultá el estado de tu personal a cargo.</p>
                    </div></div>
                </a>
            </div>
        @endif
        @if(auth()->user()->isAdmin())
            <div class="col-md-4 mb-3">
                <a href="{{ route('vacaciones.configuracion') }}" class="text-decoration-none">
                    <div class="card h-100 shadow-sm"><div class="card-body">
                        <i class="fas fa-user-cog fa-2x text-warning mb-3"></i>
                        <h4>Configuración laboral</h4>
                        <p class="text-muted mb-0">Asigná fecha de ingreso, área y jefe de cada usuario.</p>
                    </div></div>
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
