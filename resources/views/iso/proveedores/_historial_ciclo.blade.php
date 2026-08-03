<div class="provider-lifecycle-history">
    @forelse($historialCicloVida as $cambio)
        @php
            $eliminado = $cambio->evento === 'eliminado';
            $estadoNuevo = data_get($cambio->valores_nuevos, 'estado');
            $tipo = $eliminado ? 'eliminacion' : ($estadoNuevo === 'inactivo' ? 'baja' : 'reactivacion');
            $labels = ['baja' => 'Baja', 'reactivacion' => 'Reactivación', 'eliminacion' => 'Eliminación definitiva'];
            $proveedorEvento = isset($proveedoresAuditoria) ? $proveedoresAuditoria->get($cambio->entidad_id) : ($proveedor ?? null);
            $codigoEvento = $proveedorEvento?->codigo ?? data_get($cambio->valores_anteriores, 'codigo', 'PR eliminado');
            $nombreEvento = $proveedorEvento?->nombre ?? data_get($cambio->valores_anteriores, 'nombre', 'Proveedor eliminado');
            $motivo = $tipo === 'baja'
                ? data_get($cambio->valores_nuevos, 'motivo_baja')
                : ($tipo === 'reactivacion' ? data_get($cambio->valores_nuevos, 'motivo_reactivacion') : data_get($cambio->valores_nuevos, 'motivo'));
        @endphp
        <article class="provider-history-event is-{{ $tipo }}">
            <div class="provider-history-marker"><i class="fa-solid {{ $tipo==='baja'?'fa-pause':($tipo==='reactivacion'?'fa-rotate-right':'fa-trash') }}"></i></div>
            <div class="provider-history-content">
                <div class="provider-history-heading">
                    <div>
                        <span class="provider-history-type">{{ $labels[$tipo] }}</span>
                        @if($mostrarProveedor ?? false)
                            <strong>@if($proveedorEvento)<a href="{{ route('planificacion.proveedores.show',$proveedorEvento) }}">{{ $codigoEvento }} — {{ $nombreEvento }}</a>@else{{ $codigoEvento }} — {{ $nombreEvento }}@endif</strong>
                        @endif
                    </div>
                    <time datetime="{{ $cambio->created_at?->toIso8601String() }}">{{ $cambio->created_at?->format('d/m/Y H:i') }}</time>
                </div>
                <p>{{ $motivo ?: 'Sin motivo registrado.' }}</p>
                <small>Registrado por {{ $cambio->usuario?->name ?? 'Usuario no disponible' }}</small>
            </div>
        </article>
    @empty
        <div class="iso-empty-card">No se registraron bajas, reactivaciones ni eliminaciones.</div>
    @endforelse
</div>
