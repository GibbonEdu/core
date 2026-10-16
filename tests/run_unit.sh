#!/usr/bin/env bash

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "${PROJECT_DIR}/resources/ops/scripts/dev-common.sh"

load_env
require_docker

if [[ $# -eq 0 ]]; then
    run_codecept unit
else
    run_codecept "$@"
fi
