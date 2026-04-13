<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .section { margin-bottom: 30px; }
        .section h2 { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table, th, td { border: 1px solid black; padding: 8px; text-align: left; }
        .footer { text-align: center; margin-top: 50px; font-size: 12px; color: #777; }
    </style>
</head>
<body>

<div class="header">
    <h1>{{ $documento->titulo }}</h1>
</div>

<div class="section">
    <h2>Versión Actual</h2>
    <table>
        <tr>
            <td>Documento</td>
            <td>{{ $documento->titulo }}</td>
        </tr>
        <tr>
            <td>Versión</td>
            <td>{{ $documento->version }}</td>
        </tr>
        <tr>
            <td>Categoría</td>
            <td>{{ $documento->categoria->nombre_categoria }}</td>
        </tr>
        <tr>
            <td>Estado</td>
            <td>{{ $documento->estado }}</td>
        </tr>
        <tr>
            <td>Creador</td>
            <td>{{ $documento->creador->name }}</td>
        </tr>
        <tr>
            <td>Fecha de Creación</td>
            <td>{{ $documento->created_at }}</td>
        </tr>
        <tr>
            <td>Último Editor</td>
            <td>{{ $documento->ultimaModificacion->name }}</td>
        </tr>
        <tr>
            <td>Fecha de Última Modificación</td>
            <td>{{ $documento->updated_at }}</td>
        </tr>
    </table>
</div>

<div class="section">
    <h2>Versiones Anteriores</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($documento->historial as $versionhistorial)
                <tr>
                    <td>{{ $versionhistorial->version }}</td>
                    <td>{{ $versionhistorial->created_at }}</td>
                    <td>Ver</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="footer">
    Documento generado automáticamente por el sistema.
</div>

</body>
</html>
