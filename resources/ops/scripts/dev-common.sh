#!/usr/bin/env bash
# Shared helpers for developer Docker scripts. Callers must set PROJECT_DIR first.

log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

load_env() {
    local env_file="${PROJECT_DIR}/.env"
    if [[ -f "${env_file}" ]]; then
        set -a
        # shellcheck disable=SC1090
        source "${env_file}"
        set +a
    fi
}

require_docker() {
    command -v docker >/dev/null 2>&1 || { err "docker not found in PATH"; exit 1; }
    docker compose version >/dev/null 2>&1 || { err "docker compose plugin not found"; exit 1; }
}

docker_compose() {
    # Ignore COMPOSE_FILE from older .env copies (they may point at removed paths).
    COMPOSE_FILE='' docker compose \
        --project-directory "${PROJECT_DIR}" \
        -f "${PROJECT_DIR}/resources/ops/compose.yaml" \
        -f "${PROJECT_DIR}/resources/ops/compose.dev.yaml" \
        "$@"
}

sql_escape() {
    local s=$1 q="'"
    s=${s//\\/\\\\}
    s=${s//$q/$q$q}
    printf '%s' "$s"
}

mysql_root() {
    docker_compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot "$@"
}

wait_for_mysql() {
    local max_wait=60 ok=0 i
    for ((i = 0; i < max_wait; i++)); do
        if mysql_root -e 'SELECT 1' >/dev/null 2>&1; then
            ok=$((ok + 1))
            if [[ $ok -ge 3 ]]; then
                return 0
            fi
        else
            ok=0
        fi
        sleep 1
    done
    return 1
}

run_codecept() {
    docker_compose run --rm --no-deps test \
        /var/www/html/vendor/codeception/codeception/codecept \
        -c /var/www/html/tests/codeception.yml \
        run \
        "$@"
}
