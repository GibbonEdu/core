#!/usr/bin/env bash
# Gibbon local development — Docker helper
# Usage:
#   ./up.sh          Start (or rebuild) the dev environment
#   ./up.sh down     Stop containers and remove volumes (resets DB)
#   ./up.sh logs     Tail live logs from all containers

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "${PROJECT_DIR}/resources/ops/scripts/dev-common.sh"

OPS_DIR="${PROJECT_DIR}/resources/ops"
GIBBON_CONF_DIR="${OPS_DIR}/configuration/gibbon"

if [[ ! -f "${OPS_DIR}/compose.yaml" ]]; then
    err "Run this script from the project root (where up.sh lives)."
    exit 1
fi

if [[ ! -f "${PROJECT_DIR}/.env" ]]; then
    if [[ ! -f "${GIBBON_CONF_DIR}/.env-example" ]]; then
        err ".env was not found and ${GIBBON_CONF_DIR}/.env-example is missing."
        exit 1
    fi
    cp "${GIBBON_CONF_DIR}/.env-example" "${PROJECT_DIR}/.env"
    log "Created .env from ${GIBBON_CONF_DIR}/.env-example"
    log "Review .env to customize local settings if needed."
fi

require_docker

if ! docker info >/dev/null 2>&1; then
    err "Docker is not running. Start Docker Desktop and try again."
    exit 1
fi

case "${1:-up}" in
    up)
        log "Starting Gibbon dev environment..."
        docker_compose build app db
        docker_compose up -d app db
        log "Installing Composer dependencies (this may take a minute on first run)..."
        docker_compose exec -T app composer install
        log ""
        log "Gibbon is running at: http://localhost:8080"
        log "To follow logs:       ./up.sh logs"
        ;;
    down)
        docker_compose down -v
        ;;
    logs)
        docker_compose logs -f
        ;;
    *)
        log "Usage: $0 [up|down|logs]"
        exit 1
        ;;
esac
