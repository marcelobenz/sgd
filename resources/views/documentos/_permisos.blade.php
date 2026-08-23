@php
    $formularioReenviado = session()->hasOldInput('_documento_form');
    $permisosAnteriores = old('permisos', []);
    $esEdicion = isset($documento);
    $documentoActual = $esEdicion ? $documento : null;

    $estadoPermisos = $usuarios->mapWithKeys(function ($usuario) use ($formularioReenviado, $permisosAnteriores, $esEdicion, $documentoActual) {
        if ($formularioReenviado) {
            $permiso = $permisosAnteriores[$usuario->id] ?? [];
        } elseif ($esEdicion) {
            $guardado = $documentoActual->permisos->firstWhere('user_id', $usuario->id);
            $permiso = $guardado ? [
                'puede_leer' => $guardado->puede_leer,
                'puede_escribir' => $guardado->puede_escribir,
                'puede_aprobar' => $guardado->puede_aprobar,
                'puede_eliminar' => $guardado->puede_eliminar,
            ] : [];
        } else {
            $permiso = [];
        }

        return [$usuario->id => [
            'leer' => !empty($permiso['puede_leer']) || !empty($permiso['puede_escribir']) || !empty($permiso['puede_aprobar']) || !empty($permiso['puede_eliminar']),
            'escribir' => !empty($permiso['puede_escribir']),
            'aprobar' => !empty($permiso['puede_aprobar']),
            'eliminar' => !empty($permiso['puede_eliminar']),
        ]];
    });
@endphp

<div class="card mt-4 permisos-documento" data-permisos-documento>
    <div class="card-header bg-light">
        <h5 class="mb-1"><i class="fa-solid fa-user-shield mr-2"></i>Acceso y aprobación</h5>
        <small class="text-muted">Agregá solamente a quienes necesiten usar o aprobar este documento.</small>
    </div>
    <div class="card-body">
        <div class="alert alert-light border d-flex align-items-center mb-4">
            <i class="fa-solid fa-circle-user fa-lg text-primary mr-3"></i>
            <div><strong>{{ auth()->user()->name }}</strong><br><small class="text-muted">Como creador, tendrás acceso completo.</small></div>
        </div>

        <label for="buscarUsuarioPermisos"><strong>Personas con acceso</strong></label>
        <div class="input-group mb-2">
            <div class="input-group-prepend"><span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span></div>
            <input type="search" class="form-control" data-buscar-usuario placeholder="Buscar por nombre o correo…" autocomplete="off">
            <div class="input-group-append">
                <button type="button" class="btn btn-outline-primary" data-ver-todos-usuarios>
                    <i class="fa-solid fa-users mr-1"></i> Ver todos
                </button>
            </div>
        </div>
        <small class="form-text text-muted mb-2">Podés recorrer la lista completa o escribir para filtrarla.</small>
        <div class="list-group mb-3 d-none" data-resultados-usuarios style="max-height: 280px; overflow-y: auto;"></div>

        <div data-usuarios-seleccionados>
            @foreach ($usuarios as $usuario)
                @php
                    $estado = $estadoPermisos[$usuario->id];
                    $seleccionado = $estado['leer'];
                    $perfil = $estado['escribir'] && $estado['eliminar'] ? 'administrar' : ($estado['escribir'] && !$estado['eliminar'] ? 'editar' : (!$estado['escribir'] && !$estado['eliminar'] ? 'leer' : 'personalizado'));
                @endphp
                <div class="border rounded p-3 mb-2 {{ $seleccionado ? '' : 'd-none' }}" data-fila-usuario
                    data-user-id="{{ $usuario->id }}" data-search="{{ mb_strtolower($usuario->name.' '.$usuario->email) }}">
                    <div class="row align-items-center">
                        <div class="col-md-5 mb-2 mb-md-0">
                            <strong>{{ $usuario->name }}</strong><br><small class="text-muted">{{ $usuario->email }}</small>
                        </div>
                        <div class="col-md-5">
                            <label class="sr-only" for="perfil-{{ $usuario->id }}">Nivel de acceso</label>
                            <select class="form-control" data-perfil id="perfil-{{ $usuario->id }}" {{ $seleccionado ? '' : 'disabled' }}>
                                <option value="leer" {{ $perfil === 'leer' ? 'selected' : '' }}>Solo lectura</option>
                                <option value="editar" {{ $perfil === 'editar' ? 'selected' : '' }}>Puede editar</option>
                                <option value="administrar" {{ $perfil === 'administrar' ? 'selected' : '' }}>Administrar documento</option>
                                <option value="personalizado" {{ $perfil === 'personalizado' ? 'selected' : '' }}>Personalizado</option>
                            </select>
                        </div>
                        <div class="col-md-2 text-md-right mt-2 mt-md-0">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-quitar-usuario>Quitar</button>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top {{ $perfil === 'personalizado' ? '' : 'd-none' }}" data-permisos-avanzados>
                        <small class="text-muted d-block mb-2">Permisos personalizados</small>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" data-permiso="leer" id="leer-{{ $usuario->id }}" name="permisos[{{ $usuario->id }}][puede_leer]" {{ $estado['leer'] ? 'checked' : '' }} {{ $seleccionado ? '' : 'disabled' }}>
                            <label class="form-check-label" for="leer-{{ $usuario->id }}">Leer</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" data-permiso="escribir" id="escribir-{{ $usuario->id }}" name="permisos[{{ $usuario->id }}][puede_escribir]" {{ $estado['escribir'] ? 'checked' : '' }} {{ $seleccionado ? '' : 'disabled' }}>
                            <label class="form-check-label" for="escribir-{{ $usuario->id }}">Editar</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input type="checkbox" class="form-check-input" data-permiso="eliminar" id="eliminar-{{ $usuario->id }}" name="permisos[{{ $usuario->id }}][puede_eliminar]" {{ $estado['eliminar'] ? 'checked' : '' }} {{ $seleccionado ? '' : 'disabled' }}>
                            <label class="form-check-label" for="eliminar-{{ $usuario->id }}">Eliminar</label>
                        </div>
                    </div>
                    <div class="form-check mt-3 pt-2 border-top">
                        <input type="checkbox" class="form-check-input" data-permiso="aprobar" id="aprobar-{{ $usuario->id }}"
                            name="permisos[{{ $usuario->id }}][puede_aprobar]" {{ $estado['aprobar'] ? 'checked' : '' }} {{ $seleccionado ? '' : 'disabled' }}>
                        <label class="form-check-label" for="aprobar-{{ $usuario->id }}">También puede aprobar este documento</label>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="text-muted mb-0 {{ $estadoPermisos->contains('leer', true) ? 'd-none' : '' }}" data-sin-usuarios>No agregaste otras personas todavía.</p>

        <div class="mt-3 pt-3 border-top" data-resumen-permisos aria-live="polite"></div>
    </div>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-permisos-documento]').forEach(function (panel) {
                const buscador = panel.querySelector('[data-buscar-usuario]');
                const botonVerTodos = panel.querySelector('[data-ver-todos-usuarios]');
                const resultados = panel.querySelector('[data-resultados-usuarios]');
                const vacio = panel.querySelector('[data-sin-usuarios]');
                const resumen = panel.querySelector('[data-resumen-permisos]');
                const filas = Array.from(panel.querySelectorAll('[data-fila-usuario]'));

                function estaActivo(fila) { return !fila.classList.contains('d-none'); }

                function aplicarPerfil(fila) {
                    const perfil = fila.querySelector('[data-perfil]').value;
                    fila.querySelector('[data-permisos-avanzados]').classList.toggle('d-none', perfil !== 'personalizado');
                    if (perfil === 'personalizado') return;
                    fila.querySelector('[data-permiso="leer"]').checked = true;
                    fila.querySelector('[data-permiso="escribir"]').checked = perfil !== 'leer';
                    fila.querySelector('[data-permiso="eliminar"]').checked = perfil === 'administrar';
                }

                function actualizarResumen() {
                    const activas = filas.filter(estaActivo);
                    const editores = activas.filter(f => f.querySelector('[data-perfil]').value === 'editar').length;
                    const administradores = activas.filter(f => f.querySelector('[data-perfil]').value === 'administrar').length;
                    const personalizados = activas.filter(f => f.querySelector('[data-perfil]').value === 'personalizado').length;
                    const lectores = activas.length - editores - administradores - personalizados;
                    const aprobadores = activas.filter(f => f.querySelector('[data-permiso="aprobar"]').checked).length;
                    vacio.classList.toggle('d-none', activas.length > 0);
                    resumen.innerHTML = activas.length
                        ? `<strong>${activas.length} persona${activas.length === 1 ? '' : 's'} con acceso:</strong> ${lectores} de lectura, ${editores} editor${editores === 1 ? '' : 'es'}, ${administradores} administrador${administradores === 1 ? '' : 'es'}${personalizados ? `, ${personalizados} personalizado${personalizados === 1 ? '' : 's'}` : ''} · ${aprobadores} aprobador${aprobadores === 1 ? '' : 'es'}.`
                        : '<strong>Solo el creador tendrá acceso.</strong>';
                }

                function agregar(fila) {
                    fila.classList.remove('d-none');
                    fila.querySelectorAll('input, select').forEach(campo => campo.disabled = false);
                    aplicarPerfil(fila);
                    buscador.value = '';
                    resultados.classList.add('d-none');
                    actualizarResumen();
                }

                function mostrarDisponibles() {
                    const termino = buscador.value.trim().toLocaleLowerCase();
                    resultados.innerHTML = '';
                    const disponibles = filas.filter(f => !estaActivo(f) && f.dataset.search.includes(termino));

                    disponibles.forEach(function (fila) {
                        const boton = document.createElement('button');
                        boton.type = 'button';
                        boton.className = 'list-group-item list-group-item-action';
                        boton.innerHTML = fila.querySelector('.col-md-5').innerHTML;
                        boton.addEventListener('click', () => agregar(fila));
                        resultados.appendChild(boton);
                    });

                    if (!disponibles.length) {
                        const mensaje = document.createElement('div');
                        mensaje.className = 'list-group-item text-muted';
                        mensaje.textContent = termino ? 'No hay personas que coincidan con la búsqueda.' : 'Todas las personas disponibles ya fueron agregadas.';
                        resultados.appendChild(mensaje);
                    }

                    resultados.classList.remove('d-none');
                }

                buscador.addEventListener('focus', mostrarDisponibles);
                buscador.addEventListener('input', mostrarDisponibles);
                botonVerTodos.addEventListener('click', function () {
                    buscador.value = '';
                    mostrarDisponibles();
                    buscador.focus();
                });

                filas.forEach(function (fila) {
                    fila.querySelector('[data-perfil]').addEventListener('change', function () { aplicarPerfil(fila); actualizarResumen(); });
                    fila.querySelectorAll('[data-permisos-avanzados] input').forEach(function (campo) {
                        campo.addEventListener('change', function () {
                            if ((campo.dataset.permiso === 'escribir' || campo.dataset.permiso === 'eliminar') && campo.checked) {
                                fila.querySelector('[data-permiso="leer"]').checked = true;
                            }
                            if (campo.dataset.permiso === 'leer' && !campo.checked) {
                                fila.querySelector('[data-permiso="escribir"]').checked = false;
                                fila.querySelector('[data-permiso="eliminar"]').checked = false;
                            }
                        });
                    });
                    fila.querySelector('[data-permiso="aprobar"]').addEventListener('change', actualizarResumen);
                    fila.querySelector('[data-quitar-usuario]').addEventListener('click', function () {
                        fila.classList.add('d-none');
                        fila.querySelectorAll('input, select').forEach(campo => campo.disabled = true);
                        fila.querySelectorAll('input[type="checkbox"]').forEach(campo => campo.checked = false);
                        actualizarResumen();
                    });
                });
                actualizarResumen();
            });
        });
    </script>
@endonce
