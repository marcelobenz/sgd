<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Sistema de Gestion Documental') }}</title>

    <!-- Bootstrap / DataTables / FontAwesome -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.2/css/rowGroup.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css"
        href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css" />

    <style>
        /* Estilo del navbar */
        .navbar {
            background-color: rgba(34, 45, 50, 0.9);
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 3000;
        }

        /* Logo de la empresa */
        .navbar-brand img {
            max-height: 50px;
            padding: 5px;
            border: 2px solid white;
            border-radius: 8px;
            background-color: #ffffff;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Estilo general para los enlaces */
        .nav-link {
            color: #ffffff;
            font-weight: 500;
            padding: 10px 15px;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Hover para enlaces */
        .nav-link:hover {
            background-color: #546899;
            color: white;
        }

        /* Item activo */
        .nav-link.active {
            background-color: #546899;
            color: #ffffff !important;
        }

        /* Icono sesión */
        .nav-link.sesion::before {
            content: '\f2bd';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            margin-right: 8px;
        }

        /* Dropdown */
        .nav-item.dropdown {
            position: relative;
        }

        .dropdown-menu {
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            left: auto;
            right: 0;
            width: auto;
            min-width: 180px;
            z-index: 4000;
        }

        .dropdown-item:hover {
            background-color: #546899;
            color: white;
        }

        /* Modales */
        .modal {
            z-index: 5000 !important;
        }

        .modal-backdrop {
            z-index: 4990 !important;
        }

        /* Contenedor principal */
        .content-container {
            margin-top: 85px;
            padding: 0 15px 20px 15px;
        }

        @media (max-width: 991.98px) {
            .content-container {
                margin-top: 95px;
            }
        }

        .user-avatar {
            --avatar-size: 40px;
            width: var(--avatar-size);
            height: var(--avatar-size);
            flex: 0 0 var(--avatar-size);
            display: inline-grid;
            place-items: center;
            overflow: hidden;
            border-radius: 50%;
            color: #fff;
            font-size: calc(var(--avatar-size) * .36);
            font-weight: 800;
            line-height: 1;
            box-shadow: inset 0 0 0 2px rgba(255, 255, 255, .35), 0 2px 7px rgba(15, 23, 42, .18);
            vertical-align: middle;
        }

        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-initials { background: linear-gradient(135deg, #315d87, #2f7d62); }
        .avatar-blue { background: linear-gradient(135deg, #2563a6, #193c68); }
        .avatar-green { background: linear-gradient(135deg, #3f9b73, #1f6249); }
        .avatar-violet { background: linear-gradient(135deg, #805ad5, #49358e); }
        .avatar-amber { background: linear-gradient(135deg, #e4a62e, #b65d12); }
        .avatar-rose { background: linear-gradient(135deg, #df6680, #a52e55); }
        .avatar-cyan { background: linear-gradient(135deg, #27a3b8, #176477); }
        .avatar-slate { background: linear-gradient(135deg, #718096, #364152); }
        .avatar-indigo { background: linear-gradient(135deg, #5965d8, #303b93); }

        .user-identity-link { display: flex; align-items: center; gap: 9px; }
        .user-inline-identity { display: inline-flex; align-items: center; gap: 7px; min-width: 0; }
        .user-inline-identity > span:last-child { overflow: hidden; text-overflow: ellipsis; }
    </style>

    {{-- Estilos específicos de cada vista --}}
    @stack('styles')

    {{-- Compatibilidad legacy: si alguna vista vieja metía <style> en heading, seguirá funcionando --}}
    @hasSection('heading')
        @php
            $headingContent = trim($__env->yieldContent('heading'));
        @endphp

        @if (str_starts_with($headingContent, '<style') || str_contains($headingContent, '<style'))
            {!! $headingContent !!}
        @endif
    @endif
</head>

<body>

    {{-- SweetAlert2 por sesión --}}
    @if (session('swal'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire(@json(session('swal')));
            });
        </script>
    @endif

    {{-- Compatibilidad con mensajes flash clásicos --}}
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Acceso denegado',
                    text: @json(session('error')),
                });
            });
        </script>
    @endif

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Operación exitosa',
                    text: @json(session('success')),
                });
            });
        </script>
    @endif

    <nav class="navbar navbar-expand-lg fixed-top">
        <a class="navbar-brand" href="/dashboard">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    href="{{ route('dashboard') }}">
                    Inicio
                </a>

                <a class="nav-link {{ request()->routeIs('pendientes.*') ? 'active' : '' }}"
                    href="{{ route('pendientes.index') }}">
                    Mis pendientes
                </a>

                <a class="nav-link {{ request()->routeIs('documentos.*') ? 'active' : '' }}"
                    href="{{ route('documentos.index') }}" role="button" aria-haspopup="true" aria-expanded="false">
                    Documentos
                </a>

                <a class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}"
                    href="{{ route('categorias.index') }}" role="button" aria-haspopup="true" aria-expanded="false">
                    Categorías
                </a>

                <a class="nav-link {{ request()->routeIs('recordatorios.*') ? 'active' : '' }}"
                    href="{{ session('recordatorios_view') === 'calendario'
                        ? route('recordatorios.calendario')
                        : route('recordatorios.mis') }}">
                    Recordatorios
                </a>

                <a class="nav-link {{ request()->routeIs('vacaciones.*') ? 'active' : '' }}"
                    href="{{ route('vacaciones.index') }}">
                    Vacaciones
                </a>

                @if (auth()->user()->puedeVerPlanificacion())
                    <a class="nav-link {{ request()->routeIs('planificacion.*') ? 'active' : '' }}"
                        href="{{ route('planificacion.index') }}">
                        Planificación
                    </a>
                @endif

                @if (auth()->user()->role === 'admin')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->routeIs('usuarios.*') || request()->routeIs('invitations.*') ? 'active' : '' }}"
                            href="#" id="usuariosDropdown" role="button" data-toggle="dropdown"
                            aria-haspopup="true" aria-expanded="false">
                            Usuarios
                        </a>

                        <div class="dropdown-menu" aria-labelledby="usuariosDropdown">
                            <a class="dropdown-item {{ request()->routeIs('usuarios.*') ? 'active' : '' }}"
                                href="{{ route('usuarios.index') }}">
                                Gestión de usuarios
                            </a>

                            <a class="dropdown-item {{ request()->routeIs('invitations.*') ? 'active' : '' }}"
                                href="{{ route('invitations.create') }}">
                                Invitaciones
                            </a>
                        </div>
                    </li>
                @endif
            </ul>
        </div>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto">
                {{-- Campanita --}}
                <li class="nav-item dropdown">
                    <a class="nav-link mr-2" data-toggle="dropdown" href="#" style="position: relative;">
                        <i class="fa-solid fa-bell"></i>

                        @if (auth()->user()->unreadNotifications->count())
                            <span
                                style="
                                    position: absolute;
                                    top: 0;
                                    right: 0;
                                    background: red;
                                    color: white;
                                    border-radius: 50%;
                                    font-size: 10px;
                                    padding: 2px 6px;">
                                {{ auth()->user()->unreadNotifications->count() }}
                            </span>
                        @endif
                    </a>

                    <div class="dropdown-menu dropdown-menu-right" style="width: 350px;">
                        <div class="dropdown-header d-flex justify-content-between align-items-center">
                            <span>Notificaciones</span>

                            <form action="{{ route('notificaciones.leerTodas') }}" method="POST">
                                @csrf
                                <button class="btn btn-sm btn-link">Marcar todas</button>
                            </form>
                        </div>

                        <div style="max-height: 300px; overflow-y: auto;">
                            @forelse(auth()->user()->notifications()->latest()->limit(10)->get() as $notificacion)
                                @php
                                    $data = $notificacion->data;
                                @endphp

                                <div class="dropdown-item {{ is_null($notificacion->read_at) ? 'bg-light' : '' }}">
                                    <strong>{{ $data['recordatorio_nombre'] ?? 'Notificación' }}</strong>

                                    <br>
                                    <small>{{ $data['documento_titulo'] ?? '' }}</small>

                                    @if (!empty($data['mensaje']))
                                        <br>
                                        <small class="text-muted">
                                            {{ $data['mensaje'] }}
                                        </small>
                                    @endif

                                    <div class="mt-2 d-flex justify-content-between">
                                        <a href="{{ $data['url'] ?? '#' }}" class="btn btn-sm btn-primary">
                                            Ver
                                        </a>

                                        @if (is_null($notificacion->read_at))
                                            <form action="{{ route('notificaciones.leer', $notificacion->id) }}"
                                                method="POST">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-secondary">
                                                    Marcar leída
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <div class="dropdown-divider"></div>
                            @empty
                                <div class="dropdown-item text-muted text-center">
                                    Sin notificaciones
                                </div>
                            @endforelse
                        </div>
                    </div>
                </li>

                {{-- Usuario --}}
                <li class="nav-item dropdown">
                    <a class="nav-link user-identity-link" href="#" id="userDropdown" role="button" data-toggle="dropdown"
                        aria-haspopup="true" aria-expanded="false">
                        <x-user-avatar :user="auth()->user()" :size="34" :label="false" />
                        <strong>{{ auth()->user()->name }}</strong>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="{{ route('profile.show') }}">Perfil</a>
                        <a class="dropdown-item" href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            Cerrar sesión
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content-container">
        @yield('contenidoPrincipal')
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.1.2/js/dataTables.rowGroup.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>

    @yield('scripting')
    @stack('scripts')
</body>

</html>
