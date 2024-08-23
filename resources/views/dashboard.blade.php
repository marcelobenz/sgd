@extends('layouts.main')

@section('heading')
<style>
    .dashboard-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); /* Ajusta el tamaño de las columnas */
        gap: 60px; /* Espacio entre tarjetas */
        justify-content: center; /* Centra horizontalmente */
        align-items: start; /* Alinea al inicio verticalmente */
        padding: 20px;
        margin-top: 40px; /* Espacio desde el borde superior */
    }
    .card {
        display: flex;
        flex-direction: column;
        height: 100%; /* Asegura que las tarjetas llenen el espacio disponible */
    }
    .card-body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        flex: 1; /* Permite que el cuerpo de la tarjeta se expanda */
    }
</style>
@endsection

@section('contenidoPrincipal')
<br>
<div class="dashboard-container">
    <!-- Total de Documentos por Estado -->
    <div class="card bg-light">
        <div class="card-header">
            <h5 class="card-title">Total de Documentos por Estado</h5>
        </div>
        <ul class="list-group list-group-flush">
            @foreach($totalPorEstado as $estado => $total)
            <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>{{ ucfirst($estado) }}</span>
                <span class="badge badge-primary badge-pill">{{ $total }}</span>
            </li>
           @endforeach
        </ul>
    </div>

    <!-- Últimos 3 Documentos Modificados -->
    <div class="card bg-light">
        <div class="card-header">
            <h5 class="card-title">Últimos 3 Documentos Modificados</h5>
        </div>
        <ul class="list-group list-group-flush">
            @foreach($ultimosDocumentos as $documento)
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="{{ route('documentos.show', $documento->id) }}">
                        {{ $documento->titulo }}
                    </a>
                    <small class="text-muted">{{ $documento->updated_at->format('d/m/Y H:i') }}</small>
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Total de Documentos sin Aprobar -->
    <!-- <div class="card bg-light">
        <div class="card-header">
            <h5 class="card-title">Total de Documentos sin Aprobar</h5>
        </div>
        <div class="card-body">
            <h2 class="text-center">{{ $totalSinAprobar }}</h2>
        </div>
    </div> -->

    <div class="card bg-light">
        <div class="card-header">
            <h5 class="card-title">Documentos pendientes de tu Aprobación</h5>
        </div>
        @if($totalSinAprobar > 0)
        <div class="card-body">
            <h2 class="text-center text-danger">{{ $miTotalSinAprobar }}</h2>
        </div>
        @else
        <div class="card-body">
            <h2 class="text-success">{{ $miTotalSinAprobar }}</h2>
        </div>
        @endif
    </div>

</div>
@endsection

@section('scripting')
@endsection
