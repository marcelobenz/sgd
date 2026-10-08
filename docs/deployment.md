# Pasajes a producción de SGD

Este procedimiento reemplaza el `sgd.sh` que actualizaba `main` antes de activar
mantenimiento y habilitaba la aplicación incluso cuando fallaba una migración.
La primera versión conserva el checkout `/var/www/sgd`, Apache y Composer en el
servidor. No incorpora todavía GitHub Actions ni directorios de releases.

## Qué queda automatizado

1. Validación local: aislamiento de base, formato PHP del cambio, suite completa,
   instalación reproducible de dependencias frontend y compilación de Vite.
2. Selección de un SHA completo, publicado en la punta de `origin/main`.
3. Prevalidación del servidor y bloqueo de despliegues simultáneos.
4. Mantenimiento antes de actualizar código, dependencias o esquema.
5. Migraciones, optimización, aviso de reinicio de workers y recarga de Apache.
6. Verificación HTTP en mantenimiento y después de habilitar la aplicación.
7. Log del resultado, etapa de fallo y commits anterior y nuevo.

La integración en `main`, el push y el respaldo se realizan explícitamente por
el operador. El script no hace commits, merges locales, push ni respaldos.

## Preparación local en Windows

Se requieren Git, Node/npm, PHP 8.1 o superior, dependencias Composer de desarrollo
instaladas y una base MySQL local independiente llamada `sgd_testing`.
El comando utiliza PHP del PATH, o la instalación conocida de Laragon; admite
`-PhpPath` para otra instalación. Los scripts PowerShell son compatibles con 5.1.

Desde la raíz del proyecto, para validar cambios aún sin commit:

```powershell
.\scripts\prepare-deployment.ps1 -ValidateWorkingTree
```

Esta opción ejecuta las mismas validaciones, pero no aprueba una versión mientras
existan cambios pendientes. Preserva los cambios de trabajo y nunca ejecuta
`git add`, `git commit`, `git switch` o `git push`.

El comando fuerza `APP_ENV=testing`, `DB_DATABASE=sgd_testing`, desactiva URLs de
conexión y evita la caché de configuración del entorno normal. Antes de PHPUnit,
el verificador comprueba configuración efectiva, host local y `SELECT DATABASE()`.
Si no puede comprobar el aislamiento, se detiene. Las pruebas pueden recrear
tablas de **sgd_testing**; esa base debe ser exclusiva y descartable.
La configuración restante de MySQL local procede de `.env.testing` o `.env`.
No usar credenciales ni túneles productivos para preparar una entrega.

La compilación usa `npm ci` y `npm run build`. **Este repositorio versiona
`public/build`**, por lo que deben revisarse e incorporarse al commit los assets
generados, incluyendo altas y bajas de archivos. No se compila en el servidor.
No versionar `public/hot`.

Después de confirmar los cambios e integrarlos en `main`, ejecutar:

```powershell
.\scripts\prepare-deployment.ps1
git rev-parse HEAD
```

Si la compilación modifica assets, revisar y confirmar esos cambios y repetir.
Si la suite o el formato falla, corregir el problema y repetir. El comando no
modifica automáticamente el formato PHP. Pint revisa archivos PHP modificados
respecto de `main`; en `main`, revisa el último commit. Blade queda cubierto por
las pruebas y la compilación correspondiente, no por Pint.

Solo cuando todas las comprobaciones pasan y el árbol queda limpio fuera de
`vendor`, se genera `.runtime/deployment/preparation.json`, ignorado por Git,
con SHA, fecha UTC y comprobaciones. Una ejecución nueva invalida la constancia
anterior. Es una evidencia local, no una firma ni una validación de producción.
Si el commit cambia durante el comando, la preparación falla.

Publicar el SHA validado en `origin/main`. Si la integración o publicación cambia
el SHA, preparar de nuevo ese resultado. El servidor rechaza una entrega si la
punta de `origin/main` avanzó respecto del SHA solicitado.

## Preparación inicial del servidor

Realizar esta configuración una sola vez, con el administrador del servidor.
El usuario de despliegue actual es `ubuntu`; cambiarlo en estos ejemplos si se
utiliza otro usuario. **El nuevo script se ejecuta sin `sudo` global**.

- Herramientas: Bash, Git, PHP CLI, Composer, curl, OpenSSL, flock y Apache.
- Checkout en `main`, sin divergencias ni cambios propios fuera de `vendor`.
- `.env` local, `APP_ENV=production`, `APP_DEBUG=false`, MySQL `sgd` y
  mantenimiento Laravel basado en archivos.
- PHP CLI y Apache deben tener extensiones compatibles con Composer.
  No se ignora `ext-gd`: instalarla si el lock de Composer la requiere.
- Usuario `ubuntu` con escritura en checkout y `.git`; `www-data` con lectura
  del código y escritura en `storage` y `bootstrap/cache`.
- Usuario de despliegue en el grupo `www-data`. El setgid de directorios de caché
  conserva el grupo para archivos nuevos.
- Sudo no interactivo para los comandos del script: PHP como `www-data`, ajuste
  de escritura grupal de archivos de `bootstrap/cache`, `apache2ctl configtest`,
  `systemctl reload apache2` y `systemctl is-active apache2`. El administrador
  debe ajustar sudoers según las rutas reales; no habilitar sudo indiscriminado.

Si el script anterior dejó todo bajo propiedad de root, normalizar una sola vez:

```bash
sudo usermod -aG www-data ubuntu
sudo chown -R ubuntu:www-data /var/www/sgd
sudo chown -R www-data:www-data /var/www/sgd/storage /var/www/sgd/bootstrap/cache
sudo find /var/www/sgd/storage /var/www/sgd/bootstrap/cache -type d -exec chmod 2775 {} +
sudo find /var/www/sgd/storage /var/www/sgd/bootstrap/cache -type f -exec chmod 0664 {} +
sudo install -o ubuntu -g www-data -m 0660 /dev/null /var/lock/sgd-deploy.lock
```

Cerrar y abrir la sesión para aplicar el grupo nuevo. No volver a crear el
archivo de bloqueo mientras haya un despliegue: debe mantener el mismo inode.
Si `public/hot` existe, revisar cómo se generó y retirarlo manualmente antes de
desplegar. El script no elimina archivos locales ni descarta cambios de Git.

Instalar el script y su verificador desde una copia revisada de estos archivos,
por ejemplo transferida al directorio `~/sgd-deploy`. Este paso inicial permite
verificar el checkout anterior, que todavía no contiene los scripts:

```bash
sudo install -d -m 0755 /usr/local/lib/sgd
sudo install -m 0755 ~/sgd-deploy/sgd.sh /usr/local/lib/sgd/sgd.sh
sudo install -m 0644 ~/sgd-deploy/check-deployment.php /usr/local/lib/sgd/check-deployment.php
```

Mantener ambos archivos instalados alineados con la versión revisada del
repositorio. No sobrescribirlos mientras un despliegue esté en ejecución.
Los comandos PHP posteriores al checkout utilizan el verificador de la entrega.

## Migraciones y respaldo

Antes de ejecutar el despliegue, revisar las migraciones de la entrega, sus
operaciones y compatibilidad con los datos actuales. El administrador debe
proveer permisos para las operaciones realmente necesarias durante el pasaje.
Esta primera versión ejecuta `artisan migrate` con la conexión de la aplicación;
la configuración de una conexión separada para migraciones queda como mejora
posterior. No almacenar contraseñas en comandos, scripts, commits o documentación.

El perfil local `codex_iso_prod` y la cuenta `codex_iso_ro` son **exclusivamente de
lectura** y no sirven para desplegar ni migrar. Las reglas de `AGENTS.md` siguen
vigentes: Codex solo consulta producción con solicitud explícita y verificación
de identidad en la tarea correspondiente.

Verificar un respaldo recuperable previo a las migraciones, con su ubicación,
fecha y procedimiento de restauración. Conservar también la versión anterior
del código y considerar cualquier almacenamiento que la entrega modifique.
`--backup-reference` registra el identificador declarado por el operador: **no
crea, valida ni restaura el respaldo**. No pasar URLs firmadas ni secretos en
ese argumento. Un respaldo diario puede no cubrir las últimas escrituras.

## Ejecutar el pasaje

Usar una URL real de la instalación que renderice la página de login y responda
HTTP 200 sin sesión. Debe apuntar directamente al protocolo y host definitivos,
sin redirecciones HTTP a HTTPS, autenticación adicional, query ni fragmento.
Esta versión asume que SGD se publica en la raíz del host. El servidor debe
poder acceder a esa URL y al mecanismo de bypass de mantenimiento de Laravel.

Primero, reemplazar `SHA_COMPLETO` por los 40 caracteres validados y prevalidar:

```bash
bash /usr/local/lib/sgd/sgd.sh \
  --commit SHA_COMPLETO \
  --health-url https://URL-DEL-SGD/login \
  --check
```

`--check` obtiene referencias con `git fetch` y escribe un log, pero no cambia
el código, instala dependencias, ejecuta migraciones ni activa mantenimiento.
Comprueba el entorno de la versión actual; las dependencias y migraciones
del commit objetivo se validan al desplegar. Debe complementarse con la revisión
local de esa entrega.

Con respaldo verificado, ejecutar:

```bash
bash /usr/local/lib/sgd/sgd.sh \
  --commit SHA_COMPLETO \
  --health-url https://URL-DEL-SGD/login \
  --backup-reference IDENTIFICADOR-DEL-RESPALDO
```

El script registra SHA anterior y objetivo, activa mantenimiento, actualiza con
fast-forward, instala Composer, valida assets, aplica migraciones y optimiza.
No cambia el propietario de todo el proyecto. Recarga Apache después de validar
su configuración. `queue:restart` solicita un reinicio ordenado; requiere caché
persistente y un supervisor que vuelva a iniciar workers, si se utilizan colas.
La ejecución del scheduler debe mantenerse configurada por separado.

Para la prueba HTTP genera una cookie temporal usando el bypass de mantenimiento
de Laravel, la elimina al salir y no registra el token. Si la prueba pasa,
habilita la aplicación y verifica una petición pública. Esta comprobación
valida arranque y renderizado del login, no todos los flujos de negocio ni los
servicios S3/correo. Después del pasaje, revisar el flujo modificado y workers.

Los logs se guardan en `~/.local/state/sgd/deployments`, con permisos restringidos.
No compartirlos completos sin revisar datos sensibles. Variables opcionales:
`SGD_PROJECT_DIR`, `SGD_LOCK_FILE` y `SGD_LOG_DIR`. Todos los operadores deben
usar el mismo archivo de bloqueo. El lock es exclusivo también para `--check`.

## Si falla

- Antes de mantenimiento: el script se detiene sin cambiar el código ni el
  estado público de la aplicación.
- Después de mantenimiento: deja la aplicación en mantenimiento y devuelve
  un código de salida distinto de cero. Informa la etapa, y conserva el log.
- Si falla la comprobación pública después de `artisan up`, intenta reactivar
  mantenimiento e informa el fallo. Verificar manualmente el estado si esa
  reactivación tampoco puede ejecutarse.

No volver a ejecutar a ciegas: se rechaza mantenimiento ya existente y también
cualquier divergencia o cambio fuera de `vendor`. Si hay cambios en `vendor` y
la nueva versión lo modifica, el script exige resolverlos antes de actualizar.
El repositorio versiona dependencias Composer; no se descartan automáticamente.

Identificar la etapa y revisar permisos, logs, estado de migraciones y SHA. Una
migración MySQL puede quedar parcialmente aplicada aunque falle. No ejecutar
automáticamente `migrate:rollback`, `git reset --hard` ni restaurar una base sobre
datos nuevos. El retorno al commit anterior exige comprobar compatibilidad con
el esquema resultante. Recuperar dependencias, configuración y permisos de la
versión elegida antes de habilitarla; verificar por HTTP y luego ejecutar
`artisan up` como `www-data` cuando esté resuelto.

La gestión automática de releases y un procedimiento probado de restauración
son siguientes etapas; esta versión prioriza detenerse con un estado explícito.

## Validación de los scripts

En Windows con Git Bash instalado:

```powershell
& 'C:\laragon\bin\git\bin\bash.exe' -n scripts/sgd.sh
& 'C:\laragon\bin\git\bin\bash.exe' scripts/tests/sgd-test.sh
```

La segunda orden simula comandos en directorios temporales. Comprueba éxito,
prevalidaciones y fallos de dependencias, migración y HTTP; no accede al servidor
ni a ninguna base real. La sintaxis PowerShell puede comprobarse con el parser
de `System.Management.Automation.Language.Parser` sin ejecutar la preparación.

## Base de la futura skill

La skill deberá operar estos scripts, verificar el SHA y su constancia local,
respetar los cambios ajenos, revisar migraciones y reportar evidencias. Deberá
distinguir preparar, publicar y desplegar según el alcance pedido. Las
credenciales y los perfiles de acceso permanecen fuera de la skill.
