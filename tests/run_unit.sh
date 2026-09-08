#!/usr/bin/env bash

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

docker compose --project-directory "${PROJECT_DIR}" \
    -f "${PROJECT_DIR}/resources/ops/compose.yaml" \
    -f "${PROJECT_DIR}/resources/ops/compose.dev.yaml" \
    run --rm test \
        /var/www/html/vendor/phpunit/phpunit/phpunit \
        --bootstrap /var/www/html/tests/bootstrap.php \
        --verbose \
        "${1:-/var/www/html/tests/unit}"
