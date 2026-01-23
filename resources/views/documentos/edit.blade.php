@extends('layouts.main')

@section('heading')
    Editar Documento
@endsection

@section('contenidoPrincipal')
    <div class="container" style="margin-top: 80px;">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="w-100" style="background-color: #f8f9fa;">
            <h2 class="text-center">Editar Documento</h2>
        </div>

        <div class="container" style="margin-top: 80px;">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <form action="{{ route('documentos.update', $documento->id) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="titulo">Título</label>
                                    <input type="text" name="titulo" id="titulo" class="form-control"
                                        value="{{ $documento->titulo }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_categoria">Categoría</label>
                                    <select name="id_categoria" id="id_categoria" class="form-control" required>
                                        @foreach ($categorias as $categoria)
                                            <option value="{{ $categoria->id }}"
                                                {{ $documento->id_categoria == $categoria->id ? 'selected' : '' }}>
                                                {{ $categoria->nombre_categoria }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group form-check mt-2">
                            <input type="checkbox" name="sin_aprobacion" id="sin_aprobacion" class="form-check-input"
                                {{ $documento->estado === 'registro' ? 'checked' : '' }}>
                            <label for="sin_aprobacion" class="form-check-label">No requiere aprobación</label>
                        </div>

                        <button class="btn btn-primary" type="button" data-toggle="collapse"
                            data-target="#collapsePermisos" aria-expanded="false" aria-controls="collapsePermisos">
                            <i class="fa-solid fa-user-shield"></i> Asignar Permisos
                        </button>

                        <div class="collapse" id="collapsePermisos">
                            <div class="form-group mt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Usuario (Correo)</th>

                                            <th class="text-center">
                                                Leer<br>
                                                <input type="checkbox" id="checkAllLeer">
                                            </th>

                                            <th class="text-center">
                                                Escribir<br>
                                                <input type="checkbox" id="checkAllEscribir">
                                            </th>

                                            <th class="text-center">
                                                Aprobar<br>
                                                <input type="checkbox" id="checkAllAprobar">
                                            </th>

                                            <th class="text-center">
                                                Eliminar<br>
                                                <input type="checkbox" id="checkAllEliminar">
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($usuarios as $usuario)
                                            @php
                                                $permisoActual = $documento->permisos
                                                    ->where('user_id', $usuario->id)
                                                    ->first();
                                            @endphp
                                            <tr>
                                                <td>{{ $usuario->email }}</td>

                                                <td class="text-center">
                                                    <input type="checkbox" class="perm-leer"
                                                        data-user-id="{{ $usuario->id }}"
                                                        name="permisos[{{ $usuario->id }}][puede_leer]"
                                                        {{ $permisoActual && $permisoActual->puede_leer ? 'checked' : '' }}>
                                                </td>

                                                <td class="text-center">
                                                    <input type="checkbox" class="perm-escribir"
                                                        data-user-id="{{ $usuario->id }}"
                                                        name="permisos[{{ $usuario->id }}][puede_escribir]"
                                                        {{ $permisoActual && $permisoActual->puede_escribir ? 'checked' : '' }}>
                                                </td>

                                                <td class="text-center">
                                                    <input type="checkbox" class="perm-aprobar"
                                                        data-user-id="{{ $usuario->id }}"
                                                        name="permisos[{{ $usuario->id }}][puede_aprobar]"
                                                        {{ $permisoActual && $permisoActual->puede_aprobar ? 'checked' : '' }}>
                                                </td>

                                                <td class="text-center">
                                                    <input type="checkbox" class="perm-eliminar"
                                                        data-user-id="{{ $usuario->id }}"
                                                        name="permisos[{{ $usuario->id }}][puede_eliminar]"
                                                        {{ $permisoActual && $permisoActual->puede_eliminar ? 'checked' : '' }}>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                        </button>

                        <button type="button" class="btn btn-primary" data-toggle="tooltip" data-placement="top"
                            title="Ver"
                            onclick="window.location.href='{{ route('documentos.validaPermiso', ['id' => $documento, 'ruta' => 'documentos.show', 'permiso' => 'puedeLeer']) }}'">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>

                        <button type="button" class="btn btn-secondary" onclick="confirmAndRedirect();">
                            <i class="fa-solid fa-arrow-left"></i> Volver
                        </button>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripting')
    <script>
        // -----------------------------
        // SweetAlert: volver sin guardar
        // -----------------------------
        function confirmAndRedirect() {
            Swal.fire({
                title: 'Volver sin guardar',
                text: 'Los datos cargados se perderán ¿Estás seguro de que deseas volver?',
                showCancelButton: true,
                confirmButtonText: 'Sí, volver',
                cancelButtonText: 'Cancelar',
                customClass: {
                    confirmButton: 'btn btn-warning',
                    cancelButton: 'btn btn-primary'
                },
                buttonsStyling: false
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('documentos.index') }}";
                }
            });
        }

        // -----------------------------
        // Helpers
        // -----------------------------
        function setAll(selector, checked) {
            document.querySelectorAll(selector).forEach(cb => cb.checked = checked);
        }

        function updateHeaderCheck(headerId, itemSelector) {
            const header = document.getElementById(headerId);
            const items = Array.from(document.querySelectorAll(itemSelector));

            if (!header) return;

            const checkedCount = items.filter(x => x.checked).length;

            header.checked = (items.length > 0 && checkedCount === items.length);
            header.indeterminate = (checkedCount > 0 && checkedCount < items.length);
        }

        function findRowCheckbox(userId, className) {
            return document.querySelector(`.${className}[data-user-id="${userId}"]`);
        }

        // Reglas de consistencia:
        // - Escribir/Aprobar/Eliminar => obliga Leer
        // - Si se destilda Leer => destilda los otros 3
        function enforceRowRulesFromAction(changedCheckbox) {
            const userId = changedCheckbox.getAttribute('data-user-id');
            if (!userId) return;

            const leer = findRowCheckbox(userId, 'perm-leer');
            const escribir = findRowCheckbox(userId, 'perm-escribir');
            const aprobar = findRowCheckbox(userId, 'perm-aprobar');
            const eliminar = findRowCheckbox(userId, 'perm-eliminar');

            // Si se tilda cualquiera de los "fuertes", forzar leer
            if (changedCheckbox.classList.contains('perm-escribir') ||
                changedCheckbox.classList.contains('perm-aprobar') ||
                changedCheckbox.classList.contains('perm-eliminar')) {

                if (changedCheckbox.checked && leer) {
                    leer.checked = true;
                }
            }

            // Si se destilda leer, bajar los otros
            if (changedCheckbox.classList.contains('perm-leer')) {
                if (!changedCheckbox.checked) {
                    if (escribir) escribir.checked = false;
                    if (aprobar) aprobar.checked = false;
                    if (eliminar) eliminar.checked = false;
                }
            }
        }

        function enforceAllRowsRules() {
            document.querySelectorAll('.perm-escribir, .perm-aprobar, .perm-eliminar, .perm-leer')
                .forEach(cb => enforceRowRulesFromAction(cb));
        }

        function refreshAllHeaders() {
            updateHeaderCheck('checkAllLeer', '.perm-leer');
            updateHeaderCheck('checkAllEscribir', '.perm-escribir');
            updateHeaderCheck('checkAllAprobar', '.perm-aprobar');
            updateHeaderCheck('checkAllEliminar', '.perm-eliminar');
        }

        // -----------------------------
        // Toggle masivo por columna
        // -----------------------------
        document.getElementById('checkAllLeer')?.addEventListener('change', function() {
            setAll('.perm-leer', this.checked);

            // Si se destilda leer masivo, también destildar los otros 3 masivo
            if (!this.checked) {
                setAll('.perm-escribir', false);
                setAll('.perm-aprobar', false);
                setAll('.perm-eliminar', false);
            }

            refreshAllHeaders();
        });

        document.getElementById('checkAllEscribir')?.addEventListener('change', function() {
            setAll('.perm-escribir', this.checked);

            // Escribir implica leer
            if (this.checked) {
                setAll('.perm-leer', true);
            }

            refreshAllHeaders();
        });

        document.getElementById('checkAllAprobar')?.addEventListener('change', function() {
            setAll('.perm-aprobar', this.checked);

            // Aprobar implica leer
            if (this.checked) {
                setAll('.perm-leer', true);
            }

            refreshAllHeaders();
        });

        document.getElementById('checkAllEliminar')?.addEventListener('change', function() {
            setAll('.perm-eliminar', this.checked);

            // Eliminar implica leer
            if (this.checked) {
                setAll('.perm-leer', true);
            }

            refreshAllHeaders();
        });

        // -----------------------------
        // Cambios individuales: aplicar reglas + actualizar headers
        // -----------------------------
        document.addEventListener('change', function(e) {
            const t = e.target;
            if (!t) return;

            if (t.matches('.perm-leer, .perm-escribir, .perm-aprobar, .perm-eliminar')) {
                enforceRowRulesFromAction(t);
                refreshAllHeaders();
            }
        });

        // -----------------------------
        // Inicializar al cargar (por checks ya guardados)
        // -----------------------------
        document.addEventListener('DOMContentLoaded', function() {
            // Asegura consistencia si en DB vinieran cosas raras
            enforceAllRowsRules();
            refreshAllHeaders();
        });
    </script>
@endsection
