#!/usr/bin/env bash

# Run bash in strict mode
set -Eeuo pipefail

# Simple logger
log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

DOCKER_COMPOSE=(docker compose \
    --project-directory "${PROJECT_DIR}" \
    -f "${PROJECT_DIR}/resources/ops/compose.yaml" \
    -f "${PROJECT_DIR}/resources/ops/compose.dev.yaml")
    
DEFAULT_TEST_URL='http://gibbon.test'
DEFAULT_DEV_URL='http://localhost:8080'

# Load env file if present
if [ -f "${PROJECT_DIR}/.env" ]; then
  set -a
  source "${PROJECT_DIR}"/.env
  set +a
fi

# -------
# Helpers
# -------
mysql_root() { "${DOCKER_COMPOSE[@]}" exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot "${MYSQL_DATABASE}" "$@"; }

# ---------
# Preflight
# ---------
command -v docker >/dev/null 2>&1 || { err "docker not found in PATH"; exit 1; }
docker compose version >/dev/null 2>&1 || { err "docker compose plugin not found"; exit 1; }
: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD is not set}"

# ---------------------
# Reset tests directory
# ---------------------
log "Cleaning up tests directory"
rm -f "${PROJECT_DIR}"/tests/_output/*fail.html 2>/dev/null || true
rm -f "${PROJECT_DIR}/tests/_output/failed" 2>/dev/null || true
log "OK: Old test output files deleted"

# -----------------------------------
# Configure database for test execution
# -----------------------------------
log 'Updating absoluteURL value in gibbonSetting table'
mysql_root -e "UPDATE gibbonSetting SET value = '${DEFAULT_TEST_URL}' WHERE name = 'absoluteURL';"
log "OK: absoluteURL value is ${DEFAULT_TEST_URL}"

# Always restore database setting, even if tests or this script fails
restore_db() {
  log 'Reverting absoluteURL value in gibbonSetting table'
  mysql_root -e "UPDATE gibbonSetting SET value = '${DEFAULT_DEV_URL}' WHERE name = 'absoluteURL';"
  log "OK: absoluteURL value is ${DEFAULT_DEV_URL}"
}
trap restore_db EXIT

# --------------
# Test execution
# --------------
log 'Running acceptance tests'
"${DOCKER_COMPOSE[@]}" run --rm test \
    /var/www/html/vendor/codeception/codeception/codecept \
    -c /var/www/html/tests/codeception.yml \
    run \
    "${@:-acceptance}"
log 'OK: Finished running acceptance tests'
