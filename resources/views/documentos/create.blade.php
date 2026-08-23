@extends('layouts.main')

@section('heading')
@endsection

@section('contenidoPrincipal')
    <div class="container" style="margin-top: 80px;">
        <div class="w-100" style="background-color: #f8f9fa;">
            <h2 class="text-center">Nuevo Documento</h2>
        </div>

        <div class="container" style="margin-top: 80px;">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form action="{{ route('documentos.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="_documento_form" value="1">

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titulo">Título</label>
                                    <input type="text" name="titulo" id="titulo" class="form-control" value="{{ old('titulo') }}" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="archivo">Archivo</label>
                                <input type="file" name="archivo" id="archivo" class="form-control" required
                                    accept=".pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx">
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_categoria">Categoría</label>
                                    <select name="id_categoria" id="id_categoria" class="form-control" required>
                                        <option value="">Seleccioná una categoría</option>
                                        @include('documentos._categoria-options', [
                                            'categoriaSeleccionada' => old('id_categoria', $categoriaPreseleccionada),
                                        ])
                                    </select>
                                    <small class="form-text text-muted">Las subcategorías aparecen debajo de su categoría principal.</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="contenido">Contenido</label>
                        <textarea name="contenido" id="contenido" class="form-control" required>{{ old('contenido') }}</textarea>
                        </div>

                        <div class="form-group form-check mt-2">
                            <input type="checkbox" name="sin_aprobacion" id="sin_aprobacion" class="form-check-input" {{ old('sin_aprobacion') ? 'checked' : '' }}>
                            <label for="sin_aprobacion" class="form-check-label">Este documento no requiere aprobación</label>
                        </div>
                        @include('documentos._permisos')

                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-secondary" onclick="confirmAndRedirect();">Volver</button>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripting')
    <script>
        function confirmAndRedirect() {
            Swal.fire({
                title: 'Volver sin guardar',
                text: 'Los datos cargados se perderán ¿Estás seguro de que deseas volver?',
                showCancelButton: true,
                confirmButtonText: 'Sí, volver',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-warning', // Cambia 'btn btn-danger' al color que desees
                    cancelButton: 'btn btn-primary' // Cambia 'btn btn-secondary' al color que desees
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('documentos.index') }}";
                }
            });
        }
    </script>
@endsection
