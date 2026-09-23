#!/usr/bin/env bash

# Export variables to be substituted in templates
set -a

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOCKER_COMPOSE="docker compose --project-directory ${PROJECT_DIR} -f ${PROJECT_DIR}/resources/ops/compose.yaml -f ${PROJECT_DIR}/resources/ops/compose.dev.yaml"

# Simple logger
log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

# Delete config.php if present
if [ -f "${PROJECT_DIR}/config.php" ]; then
  rm "${PROJECT_DIR}/config.php"
  log "OK: Deleted config.php"
fi

# Load env file if present
if [ -f "${PROJECT_DIR}/.env" ]; then
  source "${PROJECT_DIR}/.env"
fi

# Drop and recreate database
${DOCKER_COMPOSE} exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\`;"
log "OK: Recreated gibbon database"

# Install test will take up to 3 minutes to run due to Gibbon
# test data import into mysql database
log "Executing Gibbon installer test (this might take a few minutes)"
${DOCKER_COMPOSE} run --rm test \
        /var/www/html/vendor/codeception/codeception/codecept \
        -c /var/www/html/tests/codeception.yml \
        run \
        "${@:-install}"
