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
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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

                        <button type="button" class="btn btn-info" data-toggle="modal" data-target="#modalRecordatorios">
                            Recordatorios
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

    <!-- Modal de recordatorios -->
    <div class="modal fade" id="modalRecordatorios" tabindex="-1" role="dialog"
        aria-labelledby="modalRecordatoriosLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="modalRecordatoriosLabel">
                        {{ $recordatorioEnEdicion ? 'Editar recordatorio' : 'Nuevo recordatorio' }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <form id="formRecordatorio"
                        action="{{ $recordatorioEnEdicion
                            ? route('documentos.recordatorios.update', $recordatorioEnEdicion->id)
                            : route('documentos.recordatorios.store', ['documento' => $documento->id]) }}"
                        method="POST">
                        @csrf

                        @if ($recordatorioEnEdicion)
                            @method('PUT')
                        @endif

                        <input type="hidden" name="documento_id" value="{{ $documento->id }}">

                        <div class="form-group">
                            <label for="recordatorio_nombre">Nombre del recordatorio</label>
                            <input type="text" class="form-control" id="recordatorio_nombre" name="nombre"
                                value="{{ old('nombre', $recordatorioEnEdicion->nombre ?? '') }}"
                                placeholder="Ej: Revisión mensual del documento">
                        </div>

                        <div class="form-group">
                            <label for="recordatorio_mensaje">Mensaje</label>
                            <textarea class="form-control" id="recordatorio_mensaje" name="mensaje" rows="3"
                                placeholder="Mensaje opcional para la notificación">{{ old('mensaje', $recordatorioEnEdicion->mensaje ?? '') }}</textarea>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-12">
                                <label for="recordatorio_fecha_inicio">Fecha del recordatorio</label>
                                <input type="date" class="form-control" id="recordatorio_fecha_inicio"
                                    name="fecha_inicio"
                                    value="{{ old('fecha_inicio', isset($recordatorioEnEdicion) && $recordatorioEnEdicion->fecha_inicio ? $recordatorioEnEdicion->fecha_inicio->format('Y-m-d') : '') }}">
                                <small class="form-text text-muted">
                                    El sistema enviará el recordatorio en el horario predeterminado.
                                </small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="recordatorio_frecuencia">Repetición</label>
                                <select class="form-control" id="recordatorio_frecuencia" name="frecuencia">
                                    <option value="no_repite"
                                        {{ old('frecuencia', $recordatorioEnEdicion->frecuencia ?? 'no_repite') == 'no_repite' ? 'selected' : '' }}>
                                        No se repite</option>
                                    <option value="diario"
                                        {{ old('frecuencia', $recordatorioEnEdicion->frecuencia ?? '') == 'diario' ? 'selected' : '' }}>
                                        Cada día</option>
                                    <option value="semanal"
                                        {{ old('frecuencia', $recordatorioEnEdicion->frecuencia ?? '') == 'semanal' ? 'selected' : '' }}>
                                        Cada semana</option>
                                    <option value="mensual"
                                        {{ old('frecuencia', $recordatorioEnEdicion->frecuencia ?? '') == 'mensual' ? 'selected' : '' }}>
                                        Cada mes</option>
                                    <option value="anual"
                                        {{ old('frecuencia', $recordatorioEnEdicion->frecuencia ?? '') == 'anual' ? 'selected' : '' }}>
                                        Anualmente</option>
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Resumen</label>
                                <input type="text" class="form-control" id="recordatorio_resumen_repeticion" readonly>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label>Usuarios destinatarios</label>
                            <div class="border rounded p-3" style="max-height: 220px; overflow-y: auto;">
                                @php
                                    $usuariosSeleccionados = old(
                                        'usuarios',
                                        isset($recordatorioEnEdicion)
                                            ? $recordatorioEnEdicion->usuarios->pluck('id')->toArray()
                                            : [],
                                    );
                                @endphp

                                @foreach ($usuariosRecordatorio as $usuario)
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="usuarios[]"
                                            value="{{ $usuario->id }}" id="usuario_recordatorio_{{ $usuario->id }}"
                                            {{ in_array($usuario->id, $usuariosSeleccionados) ? 'checked' : '' }}>

                                        <label class="form-check-label" for="usuario_recordatorio_{{ $usuario->id }}">
                                            {{ $usuario->name }}
                                            @if (!empty($usuario->email))
                                                - {{ $usuario->email }}
                                            @endif
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <hr>

                        <div class="form-group mb-2">
                            <label>Canales de notificación</label>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="recordatorio_notificar_interno"
                                    name="notificar_interno" value="1"
                                    {{ old('notificar_interno', isset($recordatorioEnEdicion) ? $recordatorioEnEdicion->notificar_interno : 1) ? 'checked' : '' }}>

                                <label class="form-check-label" for="recordatorio_notificar_interno">
                                    Notificación interna
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="recordatorio_notificar_email"
                                    name="notificar_email" value="1"
                                    {{ old('notificar_email', isset($recordatorioEnEdicion) ? $recordatorioEnEdicion->notificar_email : 0) ? 'checked' : '' }}>

                                <label class="form-check-label" for="recordatorio_notificar_email">
                                    Correo electrónico
                                </label>
                            </div>
                        </div>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="checkbox" id="recordatorio_activo" name="activo"
                                value="1"
                                {{ old('activo', isset($recordatorioEnEdicion) ? $recordatorioEnEdicion->activo : 1) ? 'checked' : '' }}>

                            <label class="form-check-label" for="recordatorio_activo">
                                Recordatorio activo
                            </label>

                            @if ($recordatorioEnEdicion)
                                <div class="mt-3">
                                    <a href="{{ route('documentos.edit', $documento->id) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        Cancelar edición
                                    </a>
                                </div>
                            @endif

                        </div>
                    </form>

                    <hr>

                    <h6 class="mt-4">Recordatorios configurados</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Frecuencia</th>
                                    <th>Próxima ejecución</th>
                                    <th>Canales</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($documento->recordatorios as $recordatorio)
                                    <tr>
                                        <td>
                                            <strong>{{ $recordatorio->nombre }}</strong>

                                            @if ($recordatorio->mensaje)
                                                <br>
                                                <small class="text-muted">{{ $recordatorio->mensaje }}</small>
                                            @endif

                                            @if ($recordatorio->usuarios->count())
                                                <br>
                                                <small class="text-muted">
                                                    Destinatarios:
                                                    {{ $recordatorio->usuarios->pluck('name')->implode(', ') }}
                                                </small>
                                            @endif
                                        </td>

                                        <td>
                                            @php
                                                $fechaBase = $recordatorio->fecha_inicio
                                                    ? \Carbon\Carbon::parse($recordatorio->fecha_inicio)
                                                    : null;

                                                $dia = $fechaBase ? $fechaBase->day : null;
                                                $mes = $fechaBase
                                                    ? $fechaBase->locale('es')->translatedFormat('F')
                                                    : null;
                                                $diaSemana = $fechaBase
                                                    ? $fechaBase->locale('es')->translatedFormat('l')
                                                    : null;
                                            @endphp

                                            @switch($recordatorio->frecuencia)
                                                @case('no_repite')
                                                    No se repite
                                                @break

                                                @case('diario')
                                                    Cada día
                                                @break

                                                @case('semanal')
                                                    Cada semana los {{ $diaSemana }}
                                                @break

                                                @case('mensual')
                                                    Cada mes el día {{ $dia }}
                                                @break

                                                @case('anual')
                                                    Anualmente el {{ $dia }} de {{ $mes }}
                                                @break

                                                @default
                                                    {{ $recordatorio->frecuencia }}
                                            @endswitch
                                        </td>

                                        <td>
                                            {{ $recordatorio->proxima_ejecucion ? $recordatorio->proxima_ejecucion->format('d/m/Y H:i') : '-' }}
                                        </td>

                                        <td>
                                            @if ($recordatorio->notificar_interno)
                                                <span class="badge badge-info">Interna</span>
                                            @endif

                                            @if ($recordatorio->notificar_email)
                                                <span class="badge badge-primary">Email</span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($recordatorio->activo)
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-secondary">Inactivo</span>
                                            @endif
                                        </td>

                                        <td class="text-nowrap">
                                            <a href="{{ route('documentos.edit', ['documento' => $documento->id, 'edit_recordatorio' => $recordatorio->id]) }}"
                                                class="btn btn-sm btn-outline-secondary mb-1">
                                                Editar
                                            </a>

                                            <form
                                                action="{{ route('documentos.recordatorios.toggleActivo', $recordatorio->id) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="btn btn-sm btn-outline-warning mb-1">
                                                    {{ $recordatorio->activo ? 'Desactivar' : 'Activar' }}
                                                </button>
                                            </form>

                                            <form
                                                action="{{ route('documentos.recordatorios.destroy', $recordatorio->id) }}"
                                                method="POST" style="display:inline;"
                                                onsubmit="return confirm('¿Deseás eliminar este recordatorio?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger mb-1">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                Aún no se cargaron recordatorios.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" form="formRecordatorio">
                            {{ $recordatorioEnEdicion ? 'Actualizar recordatorio' : 'Guardar recordatorio' }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>

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

        <!-----------------------------
                                                                                                                                                                Mostrar campos de frecuencia según selección
                                                                                                                                                                ----------------------------->
        <script>
            $(document).ready(function() {

                function obtenerNombreDia(fechaTexto) {
                    if (!fechaTexto) return '';

                    const partes = fechaTexto.split('-');
                    if (partes.length !== 3) return '';

                    const fecha = new Date(partes[0], partes[1] - 1, partes[2]);
                    const dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

                    return dias[fecha.getDay()];
                }

                function obtenerNombreMes(numeroMes) {
                    const meses = [
                        'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                        'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'
                    ];

                    return meses[numeroMes - 1] || '';
                }

                function actualizarResumenRepeticion() {
                    const frecuencia = $('#recordatorio_frecuencia').val();
                    const fechaTexto = $('#recordatorio_fecha_inicio').val();

                    let resumen = '';

                    if (!fechaTexto) {
                        $('#recordatorio_resumen_repeticion').val('');
                        return;
                    }

                    const partes = fechaTexto.split('-');
                    const mes = parseInt(partes[1], 10);
                    const dia = parseInt(partes[2], 10);

                    const nombreDia = obtenerNombreDia(fechaTexto);
                    const nombreMes = obtenerNombreMes(mes);

                    switch (frecuencia) {
                        case 'no_repite':
                            resumen = 'No se repite';
                            break;
                        case 'diario':
                            resumen = 'Cada día';
                            break;
                        case 'semanal':
                            resumen = 'Cada semana los ' + nombreDia;
                            break;
                        case 'mensual':
                            resumen = 'Cada mes el día ' + dia;
                            break;
                        case 'anual':
                            resumen = 'Anualmente el ' + dia + ' de ' + nombreMes;
                            break;
                        default:
                            resumen = '';
                            break;
                    }

                    $('#recordatorio_resumen_repeticion').val(resumen);
                }

                $('#recordatorio_frecuencia').on('change', function() {
                    actualizarResumenRepeticion();
                });

                $('#recordatorio_fecha_inicio').on('change', function() {
                    actualizarResumenRepeticion();
                });

                actualizarResumenRepeticion();
            });
        </script>
        <script>
            $(document).ready(function() {
                @if ($errors->any() || isset($recordatorioEnEdicion))
                    $('#modalRecordatorios').modal('show');
                @endif
            });
        </script>
    @endsection
