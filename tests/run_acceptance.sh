#!/usr/bin/env bash

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DOCKER_COMPOSE="docker compose --project-directory ${PROJECT_DIR} -f ${PROJECT_DIR}/resources/ops/compose.yaml -f ${PROJECT_DIR}/resources/ops/compose.dev.yaml"

# Display lines for debugging
#set -x

# Export variables to be substituted in templates
set -a


# Simple logger
log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

# Clean up test output directory
if [ -f "${PROJECT_DIR}/tests/_output/failed" ]; then
  rm -f "${PROJECT_DIR}/tests/_output/"*fail.html
  rm "${PROJECT_DIR}/tests/_output/failed"
fi

# Load env file if present
if [ -f "${PROJECT_DIR}/.env" ]; then
  source "${PROJECT_DIR}/.env"
fi

log 'Updating absoluteURL value in gibbonSetting table'
${DOCKER_COMPOSE} exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db \
    mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" <<'SQL'
        UPDATE gibbonSetting
        SET value = 'http://172.16.238.10'
        WHERE name = 'absoluteURL'
SQL
log 'OK: absoluteURL value is http://172.16.238.10'

log 'Running acceptance tests'
${DOCKER_COMPOSE} run --rm test \
    /var/www/html/vendor/codeception/codeception/codecept \
    -c /var/www/html/tests/codeception.yml \
    run \
    "${1:-acceptance}"
log 'OK: Finished running acceptance tests'

log 'Reverting absoluteURL value in gibbonSetting table'
${DOCKER_COMPOSE} exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db \
    mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" <<'SQL'
        UPDATE gibbonSetting
        SET value = 'http://localhost:8080'
        WHERE name = 'absoluteURL'
SQL
log 'OK: absoluteURL value is http://localhost:8080'
