#=============================================================
# Gibbon Local Development — Docker helper
# =============================================================
# Usage:
#   ./up.sh          Start (or rebuild) the dev environment
#   ./up.sh down     Stop containers and remove volumes (resets DB)
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

case "${1:-up}" in
    up)
        echo "Starting Gibbon dev environment..."
        ${DOCKER_COMPOSE} build app db
        ${DOCKER_COMPOSE} up -d
        echo "Installing Composer dependencies (this may take a minute on first run)..."
        ${DOCKER_COMPOSE} exec -T app composer install
        echo ""
        echo "Gibbon is running at: http://localhost:8080"
        echo "To follow logs:       ./up.sh logs"
        ;;
    logs)
        docker compose logs -f
        ;;
    *)
        echo "Usage: $0 [up|down|logs]"
        exit 1
        ;;
esac
