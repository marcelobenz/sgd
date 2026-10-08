<?php

// Used by the deployment scripts. Never print environment values or credentials.
$root = $argv[2] ?? dirname(__DIR__);
$mode = $argv[1] ?? '';

try {
    if ($mode === 'assets') {
        $build = $root.'/public/build';
        $manifest = json_decode(file_get_contents($build.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach (['resources/css/app.css', 'resources/js/app.js'] as $entry) {
            if (empty($manifest[$entry]['isEntry'])) {
                throw new RuntimeException('Falta un punto de entrada de Vite.');
            }
        }
        foreach ($manifest as $entry) {
            foreach (array_merge([$entry['file'] ?? ''], $entry['css'] ?? [], $entry['assets'] ?? []) as $asset) {
                $path = realpath($build.'/'.$asset);
                if (
                    $asset === '' || $path === false || ! is_file($path) || ! is_readable($path)
                    || strncmp($path, realpath($build).DIRECTORY_SEPARATOR, strlen(realpath($build)) + 1) !== 0
                ) {
                    throw new RuntimeException('El manifest referencia un asset inexistente o no legible.');
                }
            }
            foreach (array_merge($entry['imports'] ?? [], $entry['dynamicImports'] ?? []) as $import) {
                if (! isset($manifest[$import])) {
                    throw new RuntimeException('El manifest contiene una importacion inexistente.');
                }
            }
        }
        echo "Assets de Vite verificados.\n";
        exit(0);
    }

    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

    if ($mode === 'testing') {
        $connection = config('database.connections.mysql');
        if (
            ! $app->environment('testing') || config('database.default') !== 'mysql'
            || ($connection['database'] ?? '') !== 'sgd_testing'
            || ! in_array($connection['host'] ?? '', ['127.0.0.1', 'localhost', '::1'], true)
            || ! empty($connection['url']) || ! empty($connection['unix_socket'])
        ) {
            throw new RuntimeException('Las pruebas requieren MySQL local, sin URL/socket, con base sgd_testing y entorno testing.');
        }
        // Connect only after checking the effective configuration.
        $database = $app->make('db')->connection()->selectOne('SELECT DATABASE() AS name');
        if ($database->name !== 'sgd_testing') {
            throw new RuntimeException('La conexion no apunta a sgd_testing.');
        }
        echo "Aislamiento de pruebas verificado: sgd_testing local.\n";
    } elseif ($mode === 'production') {
        if (! $app->environment('production') || config('app.debug') !== false) {
            throw new RuntimeException('Se requiere APP_ENV=production y APP_DEBUG=false.');
        }
        if (config('database.default') !== 'mysql' || config('database.connections.mysql.database') !== 'sgd') {
            throw new RuntimeException('Se requiere la conexion MySQL de sgd.');
        }
        if (config('database.connections.mysql.username') === 'codex_iso_ro') {
            throw new RuntimeException('La cuenta de solo lectura no puede utilizarse para desplegar.');
        }
        if (config('app.maintenance.driver', 'file') !== 'file') {
            throw new RuntimeException('Este procedimiento requiere mantenimiento basado en archivos.');
        }
        if (! is_writable($root.'/storage') || ! is_writable($root.'/bootstrap/cache')) {
            throw new RuntimeException('El usuario web necesita escritura en storage y bootstrap/cache.');
        }
        echo "Configuracion de produccion verificada.\n";
    } else {
        throw new RuntimeException('Modo esperado: assets, testing o production.');
    }
} catch (Throwable $error) {
    // Framework/DB exceptions may contain connection data. Only our own messages are printed.
    fwrite(STDERR, get_class($error) === RuntimeException::class
        ? $error->getMessage()."\n"
        : "No se pudo verificar {$mode}. Revisar dependencias, configuracion y conexion sin divulgar credenciales.\n");
    exit(1);
}
