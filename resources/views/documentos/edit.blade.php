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
                                        <option value="">Seleccioná una categoría</option>
                                        @include('documentos._categoria-options', [
                                            'categoriaSeleccionada' => old('id_categoria', $documento->id_categoria),
                                        ])
                                    </select>
                                    <small class="form-text text-muted">Las subcategorías aparecen debajo de su categoría principal.</small>
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

    <style>
        #modalRecordatorios .modal-dialog {
            max-width: 1080px;
        }

        #modalRecordatorios .modal-body {
            max-height: calc(100vh - 190px);
            overflow-y: auto;
        }

        .recordatorio-tabs .nav-link {
            border: 0;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 600;
            padding: 0.8rem 1rem;
        }

        .recordatorio-tabs .nav-link.active {
            background: #007bff;
            border-bottom-color: #007bff;
            color: #fff !important;
        }

        .recordatorio-tabs .nav-link.active .badge {
            background: #fff;
            color: #007bff;
        }

        .recordatorio-section-heading {
            align-items: center;
            display: flex;
            margin-bottom: 1rem;
        }

        .recordatorio-section-icon {
            align-items: center;
            background: #eaf2ff;
            border-radius: 8px;
            color: #007bff;
            display: inline-flex;
            height: 36px;
            justify-content: center;
            margin-right: 0.75rem;
            width: 36px;
        }

        .recordatorio-summary {
            align-items: center;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            color: #495057;
            display: flex;
            min-height: 38px;
            padding: 0.375rem 0.75rem;
        }

        .recordatorio-disclosure {
            align-items: center;
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 1rem;
        }

        .recordatorio-disclosure[aria-expanded="true"] .recordatorio-disclosure-icon {
            transform: rotate(180deg);
        }

        .recordatorio-disclosure-icon {
            transition: transform 0.2s ease;
        }

        .recordatorio-collapsible-panel {
            background: #f8fafc;
            border: 1px solid #dbe5f0;
            border-radius: 0 0 8px 8px;
            border-top: 0;
            padding: 1rem;
        }

        .recordatorio-users-list {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            max-height: 230px;
            overflow-y: auto;
            padding: 12px;
        }

        .recordatorio-user-option,
        .recordatorio-channel-option,
        .recordatorio-active-option {
            background: #fff;
            border: 1px solid #e3e7eb;
            border-radius: 8px;
            margin: 0;
            padding: 10px 12px 10px 36px;
        }

        .recordatorio-channel-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .recordatorios-table-wrapper {
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }

        .recordatorio-modal-footer {
            background: #fff;
            position: sticky;
            bottom: 0;
            z-index: 2;
        }

        #modalDestinatariosRecordatorio {
            z-index: 1060;
        }

        #modalDestinatariosRecordatorio .recordatorio-users-list {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        @media (max-width: 767px) {
            .recordatorio-users-list,
            .recordatorio-channel-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <!-- Modal de recordatorios -->
    <div class="modal fade" id="modalRecordatorios" tabindex="-1" role="dialog"
        aria-labelledby="modalRecordatoriosLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-light align-items-center">
                    <div>
                        <h5 class="modal-title" id="modalRecordatoriosLabel">
                            <i class="fa fa-bell text-primary mr-2"></i>
                            {{ $recordatorioEnEdicion ? 'Editar recordatorio' : 'Administrar recordatorios' }}
                        </h5>
                        <small class="text-muted ml-4">
                            Programá revisiones y asigná responsables para este documento
                        </small>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-0">
                    <ul class="nav nav-tabs recordatorio-tabs px-4 pt-3" id="recordatoriosTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="recordatorio-form-tab" data-toggle="tab"
                                href="#recordatorio-form-panel" role="tab">
                                <i class="fa fa-plus-circle mr-1"></i>
                                {{ $recordatorioEnEdicion ? 'Editar recordatorio' : 'Nuevo recordatorio' }}
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="recordatorios-configurados-tab" data-toggle="tab"
                                href="#recordatorios-configurados-panel" role="tab">
                                <i class="fa fa-list mr-1"></i>
                                Configurados
                                <span class="badge badge-light ml-1">{{ $documento->recordatorios->count() }}</span>
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active p-4" id="recordatorio-form-panel" role="tabpanel">
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

                        <div class="recordatorio-section-heading">
                            <span class="recordatorio-section-icon"><i class="fa fa-align-left"></i></span>
                            <div>
                                <strong>Información</strong>
                                <div class="small text-muted">Identificá el objetivo del recordatorio.</div>
                            </div>
                        </div>

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

                        <button type="button"
                            class="btn btn-outline-primary btn-block text-left recordatorio-disclosure mt-4"
                            data-toggle="collapse" data-target="#panelProgramacionRecordatorio"
                            aria-expanded="{{ old('fecha_inicio', $recordatorioEnEdicion->fecha_inicio ?? null) ? 'true' : 'false' }}"
                            aria-controls="panelProgramacionRecordatorio">
                            <span>
                                <i class="fa fa-calendar-alt mr-2"></i>
                                <strong>Programación</strong>
                                <small class="d-block text-muted ml-4">Fecha y opciones de repetición</small>
                            </span>
                            <i class="fa fa-chevron-down recordatorio-disclosure-icon"></i>
                        </button>

                        <div id="panelProgramacionRecordatorio"
                            class="collapse {{ old('fecha_inicio', $recordatorioEnEdicion->fecha_inicio ?? null) ? 'show' : '' }}">
                            <div class="recordatorio-collapsible-panel">
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
                            <div class="form-group col-md-12 mb-0">
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

                        </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <button type="button"
                                class="btn btn-outline-primary btn-block text-left recordatorio-disclosure mt-4"
                                data-toggle="modal" data-target="#modalDestinatariosRecordatorio">
                                <span>
                                    <i class="fa fa-users mr-2"></i>
                                    <strong>Destinatarios</strong>
                                    <small class="d-block text-muted ml-4" id="resumenDestinatariosRecordatorio">
                                        Ningún usuario seleccionado
                                    </small>
                                </span>
                                <span class="badge badge-primary badge-pill" id="cantidadDestinatariosRecordatorio">0</span>
                            </button>
                            @php
                                $usuariosSeleccionados = old(
                                    'usuarios',
                                    isset($recordatorioEnEdicion)
                                        ? $recordatorioEnEdicion->usuarios->pluck('id')->toArray()
                                        : [],
                                );
                            @endphp
                        </div>

                        <hr>

                        <div class="form-group mb-2">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="recordatorio_notificar_interno"
                                    name="notificar_interno" value="1"
                                    {{ old('notificar_interno', isset($recordatorioEnEdicion) ? $recordatorioEnEdicion->notificar_interno : 1) ? 'checked' : '' }}>

                                <label class="form-check-label" for="recordatorio_notificar_interno">
                                    Notificación interna
                                </label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="recordatorio_notificar_email"
                                    name="notificar_email" value="1"
                                    {{ old('notificar_email', isset($recordatorioEnEdicion) ? $recordatorioEnEdicion->notificar_email : 0) ? 'checked' : '' }}>

                                <label class="form-check-label" for="recordatorio_notificar_email">
                                    Correo electrónico
                                </label>
                            </div>

                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="recordatorio_activo" name="activo"
                                    value="1"
                                    {{ old('activo', isset($recordatorioEnEdicion) ? $recordatorioEnEdicion->activo : 1) ? 'checked' : '' }}>

                                <label class="form-check-label" for="recordatorio_activo">
                                    Recordatorio activo
                                </label>
                            </div>

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
                        </div>

                    <div class="tab-pane fade p-4" id="recordatorios-configurados-panel" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-1">Recordatorios configurados</h6>
                            <small class="text-muted">Administrá la programación vigente de este documento.</small>
                        </div>
                    </div>
                    <div class="table-responsive recordatorios-table-wrapper">
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
                                                class="btn btn-sm btn-outline-secondary mb-1" title="Editar">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            <form
                                                action="{{ route('documentos.recordatorios.toggleActivo', $recordatorio->id) }}"
                                                method="POST" style="display:inline;">
                                                @csrf
                                                @method('PATCH')

                                                <button type="submit" class="btn btn-sm btn-outline-warning mb-1"
                                                    title="{{ $recordatorio->activo ? 'Desactivar' : 'Activar' }}">
                                                    <i class="fa {{ $recordatorio->activo ? 'fa-pause' : 'fa-play' }}"></i>
                                                </button>
                                            </form>

                                            <form
                                                action="{{ route('documentos.recordatorios.destroy', $recordatorio->id) }}"
                                                method="POST" style="display:inline;"
                                                onsubmit="return confirm('¿Deseás eliminar este recordatorio?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-outline-danger mb-1"
                                                    title="Eliminar">
                                                    <i class="fa fa-trash"></i>
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
                    </div>
                    </div>

                    <div class="modal-footer recordatorio-modal-footer" id="recordatorioFormFooter">
                        <button type="submit" class="btn btn-primary" form="formRecordatorio">
                            {{ $recordatorioEnEdicion ? 'Actualizar recordatorio' : 'Guardar recordatorio' }}
                        </button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    </div>

                </div>
            </div>
        </div>
        </div>

        <div class="modal fade" id="modalDestinatariosRecordatorio" tabindex="-1" role="dialog"
            aria-labelledby="modalDestinatariosRecordatorioLabel" aria-hidden="true" data-backdrop="static">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <div>
                            <h5 class="modal-title" id="modalDestinatariosRecordatorioLabel">
                                <i class="fa fa-users text-primary mr-2"></i>Seleccionar destinatarios
                            </h5>
                            <small class="text-muted">Buscá y seleccioná uno o más usuarios habilitados.</small>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-search"></i></span>
                            </div>
                            <input type="search" class="form-control" id="buscarDestinatarioRecordatorio"
                                placeholder="Buscar por nombre o correo">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-primary"
                                    id="btnAsignarmeRecordatorio" data-user-id="{{ auth()->id() }}">
                                    <i class="fa fa-user-check mr-1"></i> Asignarme
                                </button>
                            </div>
                        </div>

                        <div class="recordatorio-users-list" id="listaDestinatariosRecordatorio">
                            @foreach ($usuariosRecordatorio as $usuario)
                                <div class="form-check recordatorio-user-option"
                                    data-search="{{ \Illuminate\Support\Str::lower($usuario->name . ' ' . $usuario->email) }}">
                                    <input class="form-check-input recordatorio-destinatario-checkbox" type="checkbox"
                                        name="usuarios[]" value="{{ $usuario->id }}"
                                        id="usuario_recordatorio_{{ $usuario->id }}" form="formRecordatorio"
                                        data-user-name="{{ $usuario->name }}"
                                        {{ in_array($usuario->id, $usuariosSeleccionados) ? 'checked' : '' }}>

                                    <label class="form-check-label" for="usuario_recordatorio_{{ $usuario->id }}">
                                        <span class="font-weight-bold">{{ $usuario->name }}</span>
                                        @if ((int) $usuario->id === (int) auth()->id())
                                            <span class="badge badge-primary ml-1">Vos</span>
                                        @endif
                                        @if (!empty($usuario->email))
                                            <small class="text-muted d-block">{{ $usuario->email }}</small>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <div class="text-center text-muted py-4" id="sinDestinatariosRecordatorio"
                            style="display: none;">
                            No se encontraron usuarios para esa búsqueda.
                        </div>
                    </div>

                    <div class="modal-footer">
                        <span class="text-muted mr-auto">
                            <strong id="cantidadDestinatariosModal">0</strong> seleccionados
                        </span>
                        <button type="button" class="btn btn-primary" data-dismiss="modal">
                            Confirmar selección
                        </button>
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
            Interacciones del formulario de recordatorios
        ----------------------------->
        <script>
            $(document).ready(function() {

                $('#btnAsignarmeRecordatorio').on('click', function() {
                    const userId = $(this).data('user-id');
                    $('#usuario_recordatorio_' + userId).prop('checked', true).trigger('change');
                });

                function actualizarDestinatariosSeleccionados() {
                    const seleccionados = $('.recordatorio-destinatario-checkbox:checked');
                    const cantidad = seleccionados.length;
                    const nombres = seleccionados.map(function() {
                        return $(this).data('user-name');
                    }).get();

                    $('#cantidadDestinatariosRecordatorio').text(cantidad);
                    $('#cantidadDestinatariosModal').text(cantidad);
                    $('#resumenDestinatariosRecordatorio').text(
                        cantidad === 0 ?
                        'Ningún usuario seleccionado' :
                        nombres.slice(0, 3).join(', ') + (cantidad > 3 ? ' y ' + (cantidad - 3) + ' más' : '')
                    );
                }

                $('.recordatorio-destinatario-checkbox').on('change',
                    actualizarDestinatariosSeleccionados);

                $('#buscarDestinatarioRecordatorio').on('input', function() {
                    const busqueda = $(this).val().trim().toLocaleLowerCase();
                    let visibles = 0;

                    $('#listaDestinatariosRecordatorio .recordatorio-user-option').each(function() {
                        const coincide = $(this).data('search').includes(busqueda);
                        $(this).toggle(coincide);
                        if (coincide) visibles++;
                    });

                    $('#sinDestinatariosRecordatorio').toggle(visibles === 0);
                });

                $('#modalDestinatariosRecordatorio').on('shown.bs.modal', function() {
                    $('#buscarDestinatarioRecordatorio').trigger('focus');
                }).on('hidden.bs.modal', function() {
                    $('#buscarDestinatarioRecordatorio').val('').trigger('input');
                    if ($('#modalRecordatorios').hasClass('show')) {
                        $('body').addClass('modal-open');
                    }
                });

                actualizarDestinatariosSeleccionados();

                $('#recordatoriosTabs a[data-toggle="tab"]').on('shown.bs.tab', function(event) {
                    const mostrandoFormulario = $(event.target).attr('href') === '#recordatorio-form-panel';
                    $('#recordatorioFormFooter').toggle(mostrandoFormulario);
                });
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
