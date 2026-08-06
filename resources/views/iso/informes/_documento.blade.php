@if($documento)
    @if($documentosAccesibles->contains($documento->id))
        <a href="{{ route('documentos.validaPermiso',['id'=>$documento->id,'ruta'=>'documentos.show','permiso'=>'puedeLeer']) }}">Doc. SGD: {{ $documento->titulo }}</a>
    @else
        <span class="text-muted">Documento interno vinculado &mdash; sin permiso de acceso</span>
    @endif
@endif
