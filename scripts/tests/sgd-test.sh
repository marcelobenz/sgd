#!/usr/bin/env bash
# Exercises production failure paths with fake commands; no network or database.
set -euo pipefail
SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)/sgd.sh"
TEST_ROOT="$(mktemp -d)"
trap 'rm -rf -- "$TEST_ROOT"' EXIT
mkdir -p "$TEST_ROOT/bin"

cat > "$TEST_ROOT/bin/mock" <<'MOCK'
#!/usr/bin/env bash
set -euo pipefail
name="${0##*/}"
case "$name" in
    id) echo 1000 ;;
    git)
        case "$1" in
            status) [[ "$SCENARIO" != dirty ]] || echo ' M routes/web.php' ;;
            fetch|merge-base|cat-file)
                if [[ "$1" == cat-file && ( "$3" == *:.env || "$3" == *:public/hot ) ]]; then exit 1; fi ;;
            branch) echo main ;;
            diff) : ;;
            rev-parse)
                case "$2" in
                    --show-toplevel) pwd -P ;;
                    origin/main) printf '%s\n' "$TARGET" ;;
                    HEAD) cat "$SGD_PROJECT_DIR/commit" ;;
                esac ;;
            merge) echo "$TARGET" > "$SGD_PROJECT_DIR/commit"; echo checkout >> "$EVENTS" ;;
            show) echo 'Desplegado: mock' ;;
            *) exit 90 ;;
        esac ;;
    flock) [[ "$SCENARIO" != locked ]] ;;
    composer)
        echo "composer:$1" >> "$EVENTS"
        if [[ "$SCENARIO" == composer && "$1" == install ]]; then exit 12; fi ;;
    sudo)
        shift # -n
        if [[ "$1" == -u ]]; then shift 2; fi
        [[ "$1" != -- ]] || shift
        exec "$@" ;;
    php)
        if [[ "$1" != artisan ]]; then exit 0; fi
        echo "artisan:$2" >> "$EVENTS"
        case "$2" in
            down) touch storage/framework/down ;;
            up) rm -f storage/framework/down ;;
            migrate) [[ "$SCENARIO" != migrate ]] || exit 13 ;;
        esac ;;
    curl)
        echo curl >> "$EVENTS"
        if [[ "${!#}" == */login ]]; then
            if [[ "$SCENARIO" == http || ( "$SCENARIO" == public-http && ! -f storage/framework/down ) ]]; then
                printf 500
            else printf 200; fi
        else printf 302; fi ;;
    apache2ctl|systemctl) echo "$name" >> "$EVENTS" ;;
    *) exit 91 ;;
esac
MOCK
for tool in id git flock composer sudo php curl apache2ctl systemctl; do
    cp "$TEST_ROOT/bin/mock" "$TEST_ROOT/bin/$tool"
    chmod +x "$TEST_ROOT/bin/$tool"
done

export PATH="$TEST_ROOT/bin:$PATH"
export TARGET=1111111111111111111111111111111111111111
run_case() {
    export SCENARIO="$1"
    local expected="$2" maintenance="$3"
    export SGD_PROJECT_DIR="$TEST_ROOT/$SCENARIO"
    export SGD_LOCK_FILE="$SGD_PROJECT_DIR/deploy.lock"
    export SGD_LOG_DIR="$SGD_PROJECT_DIR/logs"
    export EVENTS="$SGD_PROJECT_DIR/events"
    mkdir -p "$SGD_PROJECT_DIR/.git" "$SGD_PROJECT_DIR/bootstrap/cache" "$SGD_PROJECT_DIR/storage/framework"
    touch "$SGD_PROJECT_DIR/.env" "$SGD_LOCK_FILE" "$EVENTS"
    echo 0000000000000000000000000000000000000000 > "$SGD_PROJECT_DIR/commit"
    local result=0
    bash "$SCRIPT" --commit "$TARGET" --health-url https://example.test/login \
        --backup-reference test-backup ${4:-} > "$SGD_PROJECT_DIR/output" 2>&1 || result=$?
    if [[ "$expected" == success && "$result" != 0 ]] || [[ "$expected" == failure && "$result" == 0 ]]; then
        cat "$SGD_PROJECT_DIR/output"; echo "FAIL $SCENARIO: exit $result"; exit 1
    fi
    if [[ "$maintenance" == yes ]]; then
        [[ -f "$SGD_PROJECT_DIR/storage/framework/down" ]] || { echo "FAIL $SCENARIO: no mantenimiento"; exit 1; }
    else
        [[ ! -f "$SGD_PROJECT_DIR/storage/framework/down" ]] || { echo "FAIL $SCENARIO: mantenimiento inesperado"; exit 1; }
    fi
    case "$SCENARIO" in
        dirty|locked|check)
            if grep -Eq 'checkout|artisan:(down|migrate|up)|composer:install' "$EVENTS"; then
                echo "FAIL $SCENARIO: modifico produccion"; exit 1
            fi ;;
        composer|migrate|http)
            if grep -q 'artisan:up' "$EVENTS"; then echo "FAIL $SCENARIO: habilito aplicacion fallida"; exit 1; fi ;;
        public-http)
            grep -q 'artisan:up' "$EVENTS" || exit 1
            [[ "$(grep -c 'artisan:down' "$EVENTS")" == 2 ]] || exit 1 ;;
        success)
            [[ "$(cat "$SGD_PROJECT_DIR/commit")" == "$TARGET" ]] || exit 1
            # Maintenance must precede checkout, dependencies, migrations and public activation.
            local events
            events="$(grep -E 'artisan:down|checkout|composer:install|artisan:migrate$|artisan:up' "$EVENTS")"
            [[ "$events" == $'artisan:down\ncheckout\ncomposer:install\nartisan:migrate\nartisan:up' ]] || exit 1 ;;
    esac
    echo "PASS $SCENARIO"
}

run_case success success no
run_case check success no --check
run_case dirty failure no
run_case locked failure no
run_case composer failure yes
run_case migrate failure yes
run_case http failure yes
run_case public-http failure yes
echo 'Todas las simulaciones pasaron.'
