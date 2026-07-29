<div class="documents-list">
    @forelse ($documentosLista as $documento)
        @php
            $estadoKey = match ($documento->estado) {
                'aprobado' => 'approved',
                'pendiente de aprobación' => 'pending',
                'registro' => 'register',
                default => 'other',
            };
            $detalleUrl = route('documentos.validaPermiso', [
                'id' => $documento,
                'ruta' => 'documentos.show',
                'permiso' => 'puedeLeer',
            ]);
        @endphp

        <article class="document-row"
            tabindex="0"
            role="link"
            aria-label="Ver detalle de {{ $documento->titulo }}"
            data-detail-url="{{ $detalleUrl }}"
            data-search="{{ Illuminate\Support\Str::lower($documento->titulo . ' ' . ($documento->ultimaModificacion?->name ?? '')) }}"
            data-status="{{ $estadoKey }}">
            <div class="document-main">
                <span class="document-icon">
                    <i class="fa-regular fa-file-lines"></i>
                </span>

                <div class="document-info">
                    <h4 class="document-title">
                        {{ $documento->titulo }}
                        <span>v{{ $documento->version }}</span>
                    </h4>
                    <div class="document-meta">
                        Modificado {{ optional($documento->updated_at)->format('d/m/Y H:i') }}
                        @if ($documento->ultimaModificacion)
                            por {{ $documento->ultimaModificacion->name }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="document-state">
                <span class="status-badge status-{{ $estadoKey }}">
                    {{ ucfirst($documento->estado) }}
                </span>
            </div>

            <div class="document-actions" aria-label="Acciones de {{ $documento->titulo }}">
                <a href="{{ $detalleUrl }}" class="btn btn-light" data-toggle="tooltip" title="Ver detalle">
                    <i class="fa-solid fa-eye"></i>
                </a>

                <a href="{{ route('documentos.validaPermiso', [
                    'id' => $documento,
                    'ruta' => 'documentos.edit',
                    'permiso' => 'puedeEscribir',
                ]) }}" class="btn btn-light" data-toggle="tooltip"
                    title="Editar datos, permisos y recordatorios">
                    <i class="fa-regular fa-pen-to-square"></i>
                </a>

                <form id="delete-form-{{ $contextoId }}-{{ $documento->id }}"
                    action="{{ route('documentos.destroy', $documento) }}"
                    method="POST"
                    class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-light delete-document"
                        data-form-id="delete-form-{{ $contextoId }}-{{ $documento->id }}"
                        data-toggle="tooltip" title="Eliminar">
                        <i class="fa-regular fa-circle-xmark"></i>
                    </button>
                </form>
            </div>
        </article>
    @empty
        <div class="empty-state compact">
            <i class="fa-regular fa-folder-open"></i>
            <p class="mb-0">No hay documentos en esta categoría.</p>
        </div>
    @endforelse
</div>

<div class="document-filter-empty empty-state compact d-none">
    <i class="fa-solid fa-magnifying-glass"></i>
    <p class="mb-0">No encontramos documentos con esos filtros.</p>
</div>
