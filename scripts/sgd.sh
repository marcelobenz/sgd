#!/usr/bin/env bash
set -Eeuo pipefail
umask 077

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"

PROJECT_DIR="${SGD_PROJECT_DIR:-/var/www/sgd}"
LOCK_FILE="${SGD_LOCK_FILE:-/var/lock/sgd-deploy.lock}"
LOG_DIR="${SGD_LOG_DIR:-$HOME/.local/state/sgd/deployments}"
WEB_USER="www-data"
BRANCH="main"
COMMIT=""
HEALTH_URL=""
BACKUP_REFERENCE=""
CHECK_ONLY=0
STAGE="argumentos"
MAINTENANCE_STARTED=0
COOKIE_FILE=""

usage() {
    cat <<'USAGE'
Uso: bash sgd.sh --commit SHA_COMPLETO --health-url https://sitio/login [--check]
                [--backup-reference IDENTIFICADOR]

--check realiza prevalidaciones sin cambiar codigo ni activar mantenimiento.
El despliegue real requiere una referencia de respaldo verificado por el operador.
Ejecutar como el usuario de despliegue, nunca con sudo bash sgd.sh.
USAGE
}

die() { printf 'ERROR [%s]: %s\n' "$STAGE" "$*" >&2; exit 1; }
web() { sudo -n -u "$WEB_USER" -- php "$@"; }

finish() {
    local result=$?
    trap - EXIT INT TERM
    if [[ -n "$COOKIE_FILE" ]]; then rm -f -- "$COOKIE_FILE"; fi
    if (( result != 0 )); then
        printf '\nFALLO en etapa: %s. Codigo: %s.\n' "$STAGE" "$result" >&2
        if (( MAINTENANCE_STARTED )); then
            # Usually the original down file still exists. Re-enter if the final HTTP check failed after up.
            web artisan down --retry=60 --render='errors::503' >/dev/null 2>&1 || true
            if [[ -f storage/framework/down ]]; then
                printf 'Mantenimiento conservado. No se ejecuta rollback de codigo ni de base automaticamente.\n' >&2
            else
                printf 'ERROR: no se pudo confirmar mantenimiento. Verificar y restringir el acceso manualmente.\n' >&2
            fi
        fi
    fi
    exit "$result"
}

while (( $# )); do
    case "$1" in
        --commit|--health-url|--backup-reference)
            (( $# >= 2 )) || die "Falta el valor de $1."
            case "$1" in
                --commit) COMMIT="$2" ;;
                --health-url) HEALTH_URL="$2" ;;
                --backup-reference) BACKUP_REFERENCE="$2" ;;
            esac
            shift 2 ;;
        --check) CHECK_ONLY=1; shift ;;
        --help|-h) usage; exit 0 ;;
        *) usage >&2; die "Argumento desconocido." ;;
    esac
done

[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || die "Indicar un SHA completo de 40 caracteres."
[[ "$HEALTH_URL" =~ ^https?://[^/?#@[:space:]]+(/[^?#[:space:]]*)?$ ]] || die "Indicar una URL HTTP(S) sin credenciales, query ni fragmento."
(( CHECK_ONLY )) || [[ -n "$BACKUP_REFERENCE" ]] || die "Falta --backup-reference."
[[ "$(id -u)" != 0 ]] || die "Ejecutar como usuario de despliegue, sin sudo."

for tool in git php composer sudo flock curl openssl tee mktemp; do
    command -v "$tool" >/dev/null || die "Falta la herramienta $tool."
done
[[ -d "$PROJECT_DIR/.git" && -f "$PROJECT_DIR/.env" ]] || die "Falta el checkout o .env en PROJECT_DIR."
cd "$PROJECT_DIR"
[[ "$(git rev-parse --show-toplevel)" == "$(pwd -P)" ]] || die "PROJECT_DIR no es la raiz del repositorio."
[[ -f "$LOCK_FILE" && -w "$LOCK_FILE" && ! -L "$LOCK_FILE" ]] || die "Preparar el archivo de bloqueo segun docs/deployment.md."
exec 9>>"$LOCK_FILE"
flock -n 9 || die "Ya hay otro despliegue o prevalidacion en curso."

mkdir -p "$LOG_DIR"
LOG_FILE="$LOG_DIR/$(date -u +%Y%m%dT%H%M%SZ)-${COMMIT:0:12}-$$.log"
exec > >(tee -a "$LOG_FILE") 2>&1
# Logs remain private; checked-out code and Composer files must be readable by Apache.
umask 022
trap finish EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
printf 'Objetivo: %s\nLog: %s\n' "$COMMIT" "$LOG_FILE"

STAGE="prevalidacion"
[[ -w .git && -w . && -w bootstrap/cache ]] || die "El usuario de despliegue necesita escritura en checkout, .git y bootstrap/cache."
[[ "$(git branch --show-current)" == "$BRANCH" ]] || die "El servidor debe estar en main."
[[ ! -e public/hot ]] || die "Existe public/hot: el servidor esta apuntando a Vite de desarrollo."
[[ ! -e storage/framework/down && ! -e storage/framework/maintenance.php ]] || die "Hay mantenimiento previo. Resolverlo antes de un nuevo despliegue."
CHANGES="$(git status --porcelain --untracked-files=all -- . ':(exclude)vendor')"
[[ -z "$CHANGES" ]] || die "Hay cambios locales fuera de vendor. Revisarlos manualmente con git status."
git fetch origin "$BRANCH"
[[ "$(git rev-parse "origin/$BRANCH")" == "$COMMIT" ]] || die "El SHA solicitado ya no es la punta de origin/main. Preparar la version vigente."
git merge-base --is-ancestor HEAD "$COMMIT" || die "La actualizacion no es fast-forward."
git cat-file -e "$COMMIT:public/build/manifest.json" || die "El commit no incluye el manifest de Vite."
git cat-file -e "$COMMIT:scripts/check-deployment.php" || die "El commit no incluye el verificador."
for protected in .env public/hot; do
    if git cat-file -e "$COMMIT:$protected" 2>/dev/null; then die "El commit versiona un archivo reservado: $protected."; fi
done
web "$SCRIPT_DIR/check-deployment.php" production "$PROJECT_DIR"
composer check-platform-reqs --lock --no-dev
PREVIOUS_COMMIT="$(git rev-parse HEAD)"
if [[ -n "$(git status --porcelain --untracked-files=all -- vendor)" \
    && -n "$(git diff --name-only HEAD "$COMMIT" -- vendor)" ]]; then
    die "vendor tiene cambios locales y la version tambien lo modifica. Resolver antes del despliegue."
fi
printf 'Version anterior: %s\n' "$PREVIOUS_COMMIT"
if (( CHECK_ONLY )); then
    printf 'Prevalidacion completa. No valida aun dependencias ni migraciones de la nueva version.\n'
    exit 0
fi
printf 'Respaldo declarado: %s\n' "$BACKUP_REFERENCE"

STAGE="activar mantenimiento"
# A secret cookie permits the HTTP check while public requests remain in maintenance.
SECRET="$(openssl rand -hex 24)"
MAINTENANCE_STARTED=1
web artisan down --retry=60 --render='errors::503' --secret="$SECRET" >/dev/null
[[ -f storage/framework/down ]] || die "No se pudo confirmar el mantenimiento."

STAGE="actualizar codigo"
git merge --ff-only "$COMMIT"
[[ "$(git rev-parse HEAD)" == "$COMMIT" ]] || die "El codigo no coincide con el SHA solicitado."

STAGE="instalar dependencias"
sudo -n -- find bootstrap/cache -type f -exec chmod g+rw -- '{}' +
composer check-platform-reqs --lock --no-dev
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction

STAGE="verificar configuracion y assets"
web artisan optimize:clear
web scripts/check-deployment.php production
web scripts/check-deployment.php assets

STAGE="migraciones"
web artisan migrate --force --no-interaction
web artisan migrate:status --no-interaction

STAGE="optimizar y reiniciar servicios"
web artisan optimize
web artisan queue:restart
sudo -n -- find bootstrap/cache -type f -exec chmod g+rw -- '{}' +
sudo -n -- apache2ctl configtest
sudo -n -- systemctl reload apache2
sudo -n -- systemctl is-active --quiet apache2

STAGE="verificacion HTTP en mantenimiento"
ORIGIN="${HEALTH_URL#*://}"
ORIGIN="${HEALTH_URL%%://*}://${ORIGIN%%/*}"
COOKIE_FILE="$(mktemp)"
STATUS="$(curl --silent --show-error --connect-timeout 10 --max-time 30 --output /dev/null \
    --cookie-jar "$COOKIE_FILE" --write-out '%{http_code}' "$ORIGIN/$SECRET")"
[[ "$STATUS" == 302 ]] || die "Apache no concedio la cookie de verificacion."
STATUS="$(curl --silent --show-error --connect-timeout 10 --max-time 30 --output /dev/null \
    --cookie "$COOKIE_FILE" --write-out '%{http_code}' "$HEALTH_URL")"
[[ "$STATUS" == 200 ]] || die "La pagina de verificacion no responde HTTP 200."

STAGE="habilitar aplicacion y verificar acceso publico"
web artisan up
STATUS="$(curl --silent --show-error --connect-timeout 10 --max-time 30 --output /dev/null \
    --write-out '%{http_code}' "$HEALTH_URL")"
[[ "$STATUS" == 200 ]] || die "Fallo el acceso publico. Se intentara reactivar mantenimiento."
MAINTENANCE_STARTED=0
printf '\nDESPLIEGUE COMPLETO\n'
git show -s --format='Desplegado: %H | %ci | %s' HEAD
printf 'Anterior: %s\nLog: %s\n' "$PREVIOUS_COMMIT" "$LOG_FILE"
