#=============================================================
# Gibbon Local Development — Docker helper
# =============================================================
# Usage:
#   ./up.sh          Start (or rebuild) the dev environment
#   ./up.sh down     Stop containers and remove volumes (resets DB)
#   ./up.sh reset    Stop containers, remove volumes and config.php
#   ./up.sh logs     Tail live logs from all containers
# =============================================================
set -euo pipefail

## Ensure the script is always run from the project root
if [ ! -f "ops/docker-compose.yaml" ]; then
    echo "Error: Run this script from the project root (where up.sh lives)."
    exit 1
fi

## Ensure the local environment file exists
if [ ! -f ".env" ]; then
    if [ ! -f "ops/.env-example" ]; then
        echo "Error: .env was not found and ops/.env-example is missing."
        exit 1
    fi

    cp ops/.env-example .env
    echo "Created .env from ops/.env-example"
    echo "Review .env to customize local settings if needed."
fi

## Ensure Docker is installed and the daemon is running
if ! command -v docker >/dev/null 2>&1; then
    echo "Error: Docker is not installed or not available on PATH."
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    echo "Error: Docker is not running. Start Docker Desktop and try again."
    exit 1
fi

DOCKER_COMPOSE="docker compose --project-directory ."

env_value() {
    awk -F= -v key="$1" '
        $0 ~ /^[[:space:]]*#/ { next }
        {
            name = $1
            gsub(/^[[:space:]]+|[[:space:]]+$/, "", name)
            if (name == key) {
                value = substr($0, index($0, "=") + 1)
                sub(/[[:space:]]+#.*$/, "", value)
                gsub(/^[[:space:]]+|[[:space:]]+$/, "", value)
                gsub(/^["'\'']|["'\'']$/, "", value)
                print value
                exit
            }
        }
    ' .env
}

port_is_available() {
    ! lsof -nP -iTCP:"$1" -sTCP:LISTEN >/dev/null 2>&1
}

select_app_port() {
    local existing_port preferred_port port max_port

    existing_port="$(${DOCKER_COMPOSE} port app 80 2>/dev/null | awk -F: '{print $NF}' | head -n1 || true)"
    if [ -n "$existing_port" ] && [ -n "$(${DOCKER_COMPOSE} ps --status running -q app 2>/dev/null)" ]; then
        export APP_PORT="$existing_port"
        return
    fi

    preferred_port="${APP_PORT:-$(env_value APP_PORT)}"
    if [ -z "$preferred_port" ] || [ "$preferred_port" = "auto" ]; then
        preferred_port=8080-8179
    fi

    case "$preferred_port" in
        *-*)
            port="${preferred_port%-*}"
            max_port="${preferred_port#*-}"
            case "$port:$max_port" in
                *[!0-9:]* | :* | *:)
                    echo "Error: APP_PORT must be a number, port range, or 'auto'."
                    exit 1
                    ;;
            esac
            if [ "$port" -gt "$max_port" ]; then
                echo "Error: APP_PORT range start must be less than or equal to the range end."
                exit 1
            fi
            ;;
        *[!0-9]*)
            echo "Error: APP_PORT must be a number, port range, or 'auto'."
            exit 1
            ;;
        *)
            port="$preferred_port"
            max_port="$((preferred_port + 99))"
            ;;
    esac

    while [ "$port" -le "$max_port" ]; do
        if port_is_available "$port"; then
            export APP_PORT="$port"
            if [ "$APP_PORT" != "${preferred_port%-*}" ]; then
                echo "Port ${preferred_port%-*} is unavailable; using $APP_PORT instead."
            fi
            return
        fi
        port="$((port + 1))"
    done

    echo "Error: No available port found between $preferred_port and $max_port."
    exit 1
}

sync_absolute_url() {
    local published_port mysql_database mysql_user mysql_password absolute_url

    published_port="$1"
    if [ -z "$published_port" ]; then
        return
    fi

    mysql_database="${MYSQL_DATABASE:-$(env_value MYSQL_DATABASE)}"
    mysql_user="${MYSQL_USER:-$(env_value MYSQL_USER)}"
    mysql_password="${MYSQL_PASSWORD:-$(env_value MYSQL_PASSWORD)}"
    mysql_database="${mysql_database:-gibbon}"
    mysql_user="${mysql_user:-gibbon}"

    if [ -z "$mysql_password" ]; then
        return
    fi

    absolute_url="http://localhost:${published_port}"

    if ${DOCKER_COMPOSE} exec -T db mysql --user="$mysql_user" --password="$mysql_password" "$mysql_database" \
        -e "SELECT 1 FROM gibbonSetting LIMIT 1;" >/dev/null 2>&1; then
        ${DOCKER_COMPOSE} exec -T db mysql --user="$mysql_user" --password="$mysql_password" "$mysql_database" \
            -e "UPDATE gibbonSetting SET value='${absolute_url}' WHERE scope='System' AND name='absoluteURL';" >/dev/null 2>&1
    fi
}

case "${1:-up}" in
    up)
        echo "Starting Gibbon dev environment..."
        select_app_port
        ${DOCKER_COMPOSE} build app db
        ${DOCKER_COMPOSE} up -d
        echo "Installing Composer dependencies (this may take a minute on first run)..."
        ${DOCKER_COMPOSE} exec -T app composer install
        PUBLISHED_PORT="$(${DOCKER_COMPOSE} port app 80 | awk -F: '{print $NF}' | head -n1)"
        sync_absolute_url "$PUBLISHED_PORT"
        echo ""
        echo "Gibbon is running at: http://localhost:${PUBLISHED_PORT:-8080}"
        echo "To follow logs:       ./up.sh logs"
        ;;
    down)
        ${DOCKER_COMPOSE} down --volumes
        ;;
    reset)
        ${DOCKER_COMPOSE} down --volumes
        rm -f config.php
        echo "Removed Docker volumes and config.php. Run ./up.sh to start a fresh installer."
        ;;
    logs)
        ${DOCKER_COMPOSE} logs -f
        ;;
    *)
        echo "Usage: $0 [up|down|reset|logs]"
        exit 1
        ;;
esac
