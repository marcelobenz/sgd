<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Sistema de Gestion Documental') }}</title>
    <!-- Incluye Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.1.2/css/rowGroup.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.dataTables.min.css"/>
    @yield('heading')
    <style>
    /* Estilo del navbar */
    .navbar {
        background-color: rgba(34, 45, 50, 0.9); /* Fondo oscuro con transparencia */
        box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); /* Sombra */
    }

    /* Logo de la empresa */
    .navbar-brand img {
        max-height: 50px;
    }

    /* Estilo general para los enlaces */
    .nav-link {
        color: #ffffff;
        font-weight: 500;
        padding: 10px 15px;
        border-radius: 4px;
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Estilos de hover para los enlaces */
    .nav-link:hover {
        background-color: #546899;
        color: white;
    }

    /* Iconos de FontAwesome */
    .nav-link.sesion::before {
        content: '\f2bd'; /* Icono de usuario */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        margin-right: 8px;
    }

    /* Dropdown personalizado */
    .dropdown-menu {
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
        width: 100%; /* Ajusta el ancho del dropdown */
        min-width: 200px; /* Establece un ancho mínimo adecuado */
    }

    /* Asegurar que el dropdown sea al menos tan ancho como el enlace */
    .nav-item.dropdown {
        position: relative;
    }

    .dropdown-menu {
        left: auto;
        right: 0; /* Hace que el dropdown se alinee a la derecha */
        width: auto; /* Hace que el dropdown tenga un ancho adecuado */
        min-width: 180px; /* Puedes ajustar este valor según lo necesites */
    }

    /* Hover en las opciones del dropdown */
    .dropdown-item:hover {
        background-color: #546899;
        color: white;
    }

    .navbar-brand img {
    max-height: 50px;
    padding: 5px;
    border: 2px solid white;
    border-radius: 8px;
    background-color: #ffffff;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2); /* Añadir sombra al logo */
}



</style>

<nav class="navbar navbar-expand-lg fixed-top">
    <a class="navbar-brand" href="/dashboard">
        <img src="{{ asset('images/logo.png') }}" alt="Logo">
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
            <a class="nav-link documentos" href="{{ route('documentos.index') }}" role="button" aria-haspopup="true" aria-expanded="false">
                Documentos
            </a>
            <a class="nav-link categorias" href="{{ route('categorias.index') }}" role="button" aria-haspopup="true" aria-expanded="false">
                Categorías
            </a>
        </ul>
    </div>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link sesion" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <strong>{{ auth()->user()->name }}</strong>
                </a>
                <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                    <a class="dropdown-item" href="{{ route('profile.show') }}">Perfil</a>
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
    <!-- jQuery, Popper.js, Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/rowgroup/1.1.2/js/dataTables.rowGroup.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>

    <!-- Incluye sweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Para el tooltip
        $(function () {
        $('[data-toggle="tooltip"]').tooltip()
        })
    </script>
    @yield('scripting')
</body>
</html>
