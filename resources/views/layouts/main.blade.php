<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Tramites Online</title>
    <!-- Incluye Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <!-- Incluye jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Incluye Popper.js y Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    @yield('heading')
    <style>
        /* Añadir margen superior para evitar superposición con la barra de navegación */
        .content-container {
            margin-top: 56px; /* Ajusta este valor según la altura de tu navbar */
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="/dashboard">Principal</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="bandejasDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Documentos
                    </a>
                    <div class="dropdown-menu" aria-labelledby="bandejasDropdown">
                        <a class="dropdown-item" href="{{ route('documentos.index') }}">Mis Documentos</a>
                    </div>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Administracion</a>
                </li>
            </ul>
        </div>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img src="https://via.placeholder.com/30" alt="Avatar" class="avatar-img">
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                        <span class="dropdown-item-text">
                            <strong>Nombre Apellido</strong><br>
                            Nro Legajo: 12345
                        </span>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="#">Perfil</a>
                        <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Cerrar sesión</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                    </div>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Contenedor principal con margen superior ajustado -->
    <div class="content-container">
        @yield('contenidoPrincipal')
    </div>

    @yield('scripting')

</body>
</html>
