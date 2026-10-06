#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "${PROJECT_DIR}/resources/ops/scripts/dev-common.sh"

load_env
require_docker

: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD is not set. Copy .env-example to .env.}"
: "${MYSQL_DATABASE:?MYSQL_DATABASE is not set. Copy .env-example to .env.}"

wait_for_mysql || { err "MySQL did not become ready. Run ./up.sh first."; exit 3; }

if [[ -f "${PROJECT_DIR}/config.php" ]]; then
    rm -f "${PROJECT_DIR}/config.php"
    log "OK: Deleted config.php"
fi

mysql_root -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\`;"
log "OK: Recreated gibbon database"

log "Executing Gibbon installer test (this might take a few minutes)"
if [[ $# -eq 0 ]]; then
    run_codecept install
else
    run_codecept "$@"
fi
