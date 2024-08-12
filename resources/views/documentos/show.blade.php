@extends('main')

@section('heading')
@endsection

@section('contenidoPrincipal')
<div class="container">
    <table class="table table-bordered w-100">
        <tr>
            <td><strong>Documento:</strong></td>
            <td>{{ $documento->titulo }}</td>
            <td><strong>Versión:</strong></td>
            <td>{{ $documento->version }}</td>
        </tr>
        <tr>
            <td><strong>Categoría:</strong></td>
            <td>{{ $documento->categoria->nombre_categoria }}</td>
            <td><strong>Estado:</strong></td>
            <td>
                @php
                    $estadoColor = '';
                    switch($documento->estado) {
                        case 'en curso':
                            $estadoColor = 'orange';
                            break;
                        case 'pendiente de aprobación':
                            $estadoColor = 'red';
                            break;
                        case 'aprobado':
                            $estadoColor = 'green';
                            break;
                        default:
                            $estadoColor = 'black';
                    }
                @endphp
                <span style="background-color: {{ $estadoColor }}; color: white; font-weight: bold; padding: 5px; border-radius: 4px;">
                    {{ $documento->estado }}
                </span>
            </td>
        </tr>
        <tr>
            <td><strong>Creador:</strong></td>
            <td>{{ $documento->creador->name }}</td>
            <td><strong>Fecha Creación:</strong></td>
            <td>{{ $documento->created_at }}</td>
        </tr>
        <tr>
            <td><strong>Último Editor:</strong></td>
            <td>{{ $documento->ultimaModificacion->name }}</td>
            <td><strong>Fecha Última Edición:</strong></td>
            <td>{{ $documento->updated_at }}</td>
        </tr>
        <tr>
            <td colspan="2">
                <a href="{{ route('documentos.download', $documento->id) }}" class="btn btn-primary">Descargar</a>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#uploadModal">Actualizar Documento</button>
                <a href="{{ route('documentos.index') }}" class="btn btn-secondary">Volver</a>
            </td>
            <td colspan="2">
                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#aprobarModal">Aprobar Documento</button>
            </td>
        </tr>
        @foreach ($documento->historial as $index => $versionhistorial)
            @if ($index == 0)
                <!-- Primer elemento: crea un componente dummy -->
                <tr id="version-history-row">
                    <td colspan="3">
                        <input type="hidden" id="dummy-input" value="dummy">
                    </td>
                </tr>
            @endif
            <tr>
                <td>{{ $versionhistorial->version }}</td>
                <td>{{ $versionhistorial->created_at }}</td>
                <td>
                    <form id="revert-form-{{ $versionhistorial->id }}" action="{{ route('documentos.revert', [$documento->id, $versionhistorial->id]) }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <a href="{{ route('documentos.revert', [$documento->id, $versionhistorial->id]) }}" 
                    data-form-id="revert-form-{{ $versionhistorial->id }}"
                    onclick="event.preventDefault(); submitForm(this);">
                        Revertir a esta versión
                    </a>
                </td>
            </tr>
        @endforeach    
    </table>

    <!-- Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" role="dialog" aria-labelledby="uploadModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="uploadModalLabel">Seleccionar nuevo archivo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="file" name="nuevoArchivo" id="nuevoArchivo" class="form-control">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="uploadBtn">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Mostrar archivo usando un visor embebido -->
    @if ($fileExtension === 'pdf')
        <embed src="{{ $fileUrl }}" type="application/pdf" width="100%" height="600px" />
    @elseif ($fileExtension === 'doc' || $fileExtension === 'docx')
        <iframe src="https://view.officeapps.live.com/op/view.aspx?src={{ urlencode($fileUrl) }}" width="100%" height="600px"></iframe>
    @elseif ($fileExtension === 'xls' || $fileExtension === 'xlsx')
        <iframe src="https://view.officeapps.live.com/op/view.aspx?src={{ urlencode($fileUrl) }}" width="100%" height="600px"></iframe>
    @endif
</div>

@endsection

@section('scripting')
<script>
document.getElementById('uploadBtn').addEventListener('click', function() {
    const fileInput = document.getElementById('nuevoArchivo');
    if (fileInput.files.length > 0) {
        const form = document.getElementById('uploadForm');
        form.submit();
    } else {
        alert('Debe seleccionar un archivo para continuar.');
    }
});

// Para el manejo del revertir version
function submitForm(link) {
    var formId = link.getAttribute('data-form-id');
    var form = document.getElementById(formId);
    if (form) {
        form.submit();
    } else {
        console.error('Formulario no encontrado:', formId);
    }
}
</script>

<style>
.word-container {
    width: 100%;
    height: 800px;
    overflow: auto;
    border: 1px solid #ddd;
    padding: 10px;
}
.excel-container {
    width: 100%;
    height: 800px;
    overflow: auto;
    border: 1px solid #ddd;
    padding: 10px;
}
</style>

@endsection
