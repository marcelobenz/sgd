# Guía de desarrollo

## Requisitos

- PHP 8.1 o superior con extensiones requeridas por Laravel y MySQL.
- Composer.
- Node.js y npm.
- MySQL.
- Credenciales del almacenamiento configurado; S3 es el destino previsto en operación.
- Un transporte de correo cuando se prueben notificaciones reales.
- LibreOffice para conversiones de archivos Office a PDF.

En Windows, el proyecto está preparado para ejecutarse desde Laragon, aunque Laravel no depende de él.

## Preparación local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

En PowerShell, la copia del archivo de entorno puede hacerse con `Copy-Item .env.example .env`. Configurar en `.env` la base, el disco de archivos, correo y `SOFFICE_PATH` según el entorno. Nunca reutilizar credenciales de producción ni confirmar `.env` en Git.

Para desarrollo interactivo:

```bash
php artisan serve
npm run dev
php artisan schedule:work
```

El servidor web, Vite y el scheduler son procesos separados.

## Base de datos y migraciones

- MySQL es el motor soportado y las migraciones contienen sentencias específicas para ese motor.
- No editar migraciones ya aplicadas. Crear una migración posterior con `up()` y `down()` coherentes.
- Revisar todas las migraciones relacionadas antes de cambiar una tabla: el esquema vigente suele resultar de varias etapas.
- Preservar restricciones, índices y comportamiento de claves foráneas.
- Las operaciones con varias escrituras relacionadas deben usar `DB::transaction()`.
- Los correlativos ISO deben protegerse frente a concurrencia; `CodigoIsoService` usa bloqueos como referencia.

## Estructura de trabajo

Antes de implementar:

1. Leer `AGENTS.md` y `CONTEXTO_PROYECTO.md`.
2. Ejecutar `git status --short` y preservar cambios ajenos.
3. Revisar rutas, controlador, modelos, migraciones, vistas y pruebas del flujo afectado.
4. Identificar permisos, estados, trazabilidad y evidencia relacionados.

Mantener textos de interfaz y conceptos de dominio en español. Seguir PSR-12, cuatro espacios y las convenciones existentes de Laravel. No editar dependencias instaladas ni artefactos generados.

## Pruebas

`phpunit.xml` configura:

- `APP_ENV=testing`;
- MySQL con base `sgd_testing`;
- caché y sesión aisladas;
- correo en memoria;
- cola síncrona.

Crear `sgd_testing` como una base independiente y otorgar acceso al usuario configurado para pruebas. Nunca apuntar la suite a una base con información útil.

Ejecutar de menor a mayor alcance:

```bash
php artisan test --filter=NombreDelTest
php artisan test tests/Feature/Iso/ProveedorFlowTest.php
php artisan test tests/Feature/Iso
php artisan test
```

Las pruebas con persistencia deben usar las herramientas de aislamiento de Laravel. Además del camino exitoso, cubrir permisos insuficientes, usuario deshabilitado, período cerrado, transición inválida y preservación de trazabilidad.

## Formato y frontend

```bash
vendor/bin/pint --test
npm run build
```

Usar Pint para PHP. La compilación de Vite valida imports, Tailwind y los puntos de entrada frontend. No confirmar `public/build/` salvo que el flujo de despliegue lo requiera expresamente.

## Comandos operativos

```bash
php artisan recordatorios:procesar
php artisan iso:importar-proveedores ruta/al/archivo.xlsx --dry-run
php artisan iso:revertir-proveedores UUID-DEL-LOTE --dry-run
```

Las importaciones deben simularse primero. La reversión real exige `--confirmar=REVERTIR` y sólo procede si no existe actividad posterior o ajena al lote.

## Diagnóstico habitual

- Si las pruebas no conectan, verificar que MySQL esté activo y exista `sgd_testing`.
- Si no aparecen recordatorios, verificar que el scheduler se esté ejecutando.
- Si falla una exportación Office, revisar `SOFFICE_PATH`, permisos del proceso y directorios temporales.
- Si falla una descarga, revisar el disco Laravel y sus credenciales, sin exponerlas en logs o documentación.
- Si una ruta ISO devuelve 403, revisar usuario habilitado, rol global y `iso_usuario_permisos`.

## Entrega

Revisar `git diff` y `git diff --check`, ejecutar validaciones proporcionales y comunicar con precisión qué se modificó y qué no pudo comprobarse. Actualizar la documentación cuando cambien arquitectura, reglas transversales, operación o alcance funcional.
