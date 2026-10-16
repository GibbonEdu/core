#!/usr/bin/env bash
# Shared helpers for developer Docker scripts. Callers must set PROJECT_DIR first.

log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

load_env() {
    # Read KEY=VALUE pairs without executing the file (unlike `source`), so a
    # malicious .env cannot run commands. Handles CR from CRLF-saved files and
    # one pair of surrounding quotes, matching how Docker Compose parses .env.
    local env_file="${PROJECT_DIR}/.env" line key value
    [[ -f "${env_file}" ]] || return 0
    while IFS= read -r line || [[ -n "$line" ]]; do
        line=${line%$'\r'}
        [[ "$line" =~ ^[[:space:]]*$ || "$line" =~ ^[[:space:]]*# ]] && continue
        if [[ "$line" =~ ^[[:space:]]*([A-Za-z_][A-Za-z0-9_]*)=(.*)$ ]]; then
            key="${BASH_REMATCH[1]}"
            value="${BASH_REMATCH[2]}"
            if [[ "$value" =~ ^\"(.*)\"$ || "$value" =~ ^\'(.*)\'$ ]]; then
                value="${BASH_REMATCH[1]}"
            fi
            printf -v "${key}" '%s' "${value}"
            export "${key}"
        else
            err "refusing unsafe line in .env: ${line}"
            return 1
        fi
    done < "${env_file}"

    # These values flow into SQL statements; allowlist their characters.
    for key in MYSQL_DATABASE MYSQL_USER; do
        if [[ -n "${!key:-}" && ! "${!key}" =~ ^[A-Za-z0-9_]+$ ]]; then
            err "${key} may only contain letters, numbers and underscores."
            return 1
        fi
    done
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
    # First MySQL initialisation can be slow on Docker Desktop; allow an override.
    local max_wait="${MYSQL_WAIT_SECONDS:-120}" ok=0 i
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

wait_for_app() {
    # Wait until Apache answers on the hostname the test container will use.
    local max_wait="${APP_WAIT_SECONDS:-60}" i
    for ((i = 0; i < max_wait; i++)); do
        if docker_compose run --rm --no-deps -T test php -r 'exit(fsockopen("gibbon.test", 80) ? 0 : 1);' >/dev/null 2>&1; then
            return 0
        fi
        sleep 1
    done
    return 1
}

run_codecept() {
    # Paths are relative to the container working directory (/var/www/html) because
    # Git Bash on Windows rewrites absolute paths passed to docker.
    docker_compose run --rm --no-deps test \
        vendor/codeception/codeception/codecept \
        -c tests/codeception.yml \
        run \
        "$@"
}
