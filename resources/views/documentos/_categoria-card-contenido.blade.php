@php
    $totalTarjeta = max($grupoTarjeta['total'], 1);
    $porcentajeAprobados = ($grupoTarjeta['aprobados'] / $totalTarjeta) * 100;
    $porcentajePendientes = ($grupoTarjeta['pendientes'] / $totalTarjeta) * 100;
    $porcentajeRegistros = ($grupoTarjeta['registros'] / $totalTarjeta) * 100;
@endphp

<div class="category-card-header">
    <span class="category-icon">
        <i class="fa-regular fa-folder-open"></i>
    </span>
    <div>
        <div class="category-type">{{ $tipoTarjeta }}</div>
        <h2 class="category-name">{{ $grupoTarjeta['categoria']->nombre_categoria }}</h2>
    </div>
</div>

<div class="category-total">
    <strong>{{ $grupoTarjeta['total'] }}</strong>
    <span>{{ Illuminate\Support\Str::plural('documento', $grupoTarjeta['total']) }}</span>
</div>

<div class="category-stats">
    <div class="category-stat approved">
        <strong>{{ $grupoTarjeta['aprobados'] }}</strong>
        <span>Aprobados</span>
    </div>
    <div class="category-stat pending">
        <strong>{{ $grupoTarjeta['pendientes'] }}</strong>
        <span>Pendientes</span>
    </div>
    <div class="category-stat register">
        <strong>{{ $grupoTarjeta['registros'] }}</strong>
        <span>Registros</span>
    </div>
</div>

<div class="status-distribution" aria-hidden="true">
    <span class="approved" style="width: {{ $porcentajeAprobados }}%"></span>
    <span class="pending" style="width: {{ $porcentajePendientes }}%"></span>
    <span class="register" style="width: {{ $porcentajeRegistros }}%"></span>
</div>

<div class="category-footer">
    <span>
        <i class="fa-regular fa-clock mr-1"></i>
        {{ optional($grupoTarjeta['ultima_modificacion'])->format('d/m/Y') }}
    </span>
    <span class="open-category">
        Abrir <i class="fa-solid fa-arrow-right ml-1"></i>
    </span>
</div>
