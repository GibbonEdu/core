#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "${PROJECT_DIR}/resources/ops/scripts/dev-common.sh"

load_env
require_docker

: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD is not set. Copy .env-example to .env.}"
: "${MYSQL_DATABASE:?MYSQL_DATABASE is not set. Copy .env-example to .env.}"
: "${ABSOLUTE_URL:=http://localhost:8080}"
: "${TEST_ABSOLUTE_URL:=http://gibbon.test}"

wait_for_mysql || { err "MySQL did not become ready. Run ./up.sh first."; exit 3; }

restore_absolute_url() {
    log "Reverting absoluteURL value in gibbonSetting table"
    mysql_root "${MYSQL_DATABASE}" -e "UPDATE gibbonSetting SET value = '$(sql_escape "${ABSOLUTE_URL}")' WHERE name = 'absoluteURL';"
    log "OK: absoluteURL value is ${ABSOLUTE_URL}"
}
trap restore_absolute_url EXIT

log "Cleaning up tests directory"
rm -f "${PROJECT_DIR}/tests/_output/"*fail.html || true
rm -f "${PROJECT_DIR}/tests/_output/failed" || true
log "OK: Old test output files deleted"

log "Updating absoluteURL value in gibbonSetting table"
mysql_root "${MYSQL_DATABASE}" -e "UPDATE gibbonSetting SET value = '$(sql_escape "${TEST_ABSOLUTE_URL}")' WHERE name = 'absoluteURL';"
log "OK: absoluteURL value is ${TEST_ABSOLUTE_URL}"

log "Running acceptance tests"
if [[ $# -eq 0 ]]; then
    run_codecept acceptance
else
    run_codecept "$@"
fi
log "OK: Finished running acceptance tests"
