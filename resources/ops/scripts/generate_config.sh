#!/usr/bin/env bash

# Bail out upon error
set -e

# Export all variables that need to be substituted in templates
set -a

# Setting up in-container application source variable (APP_SOURCE)
APP_SOURCE=/var/www/html

# Read env variables in same directory, from a file called .env.
PROJECT_DIR="$APP_SOURCE"
# shellcheck disable=SC1091
source "${APP_SOURCE}/resources/ops/scripts/dev-common.sh"
load_env

: "${MYSQL_HOST:=gibbon_db}"
: "${MYSQL_USER:=gibbon}"
: "${MYSQL_DATABASE:=gibbon}"
: "${GUID:=wocin5trm-vitd-iws-g2k-3huw6nzxtfp}"
: "${CACHING_FACTOR:=10}"

# Reject values that could inject PHP code into config.php.
[[ "${CACHING_FACTOR}" =~ ^[0-9]+$ ]] || { err "CACHING_FACTOR must be a number."; exit 1; }
[[ "${GUID}" =~ ^[A-Za-z0-9-]+$ ]] || { err "GUID may only contain letters, numbers and hyphens."; exit 1; }
[[ "${MYSQL_HOST}" =~ ^[A-Za-z0-9._-]+$ ]] || { err "MYSQL_HOST contains unsafe characters."; exit 1; }

# Escape backslashes and single quotes for the PHP string in config.php.
MYSQL_PASSWORD=${MYSQL_PASSWORD//\\/\\\\}
MYSQL_PASSWORD=${MYSQL_PASSWORD//\'/\\\'}
export MYSQL_PASSWORD

# Generate config.php from the template using envsubst.
# Explicit list of placeholders to replace (only these will be expanded)
SOURCE=${APP_SOURCE}/resources/ops/configuration/gibbon/config.php.dist
TARGET=${APP_SOURCE}/config.php
VARS='${MYSQL_HOST} ${MYSQL_USER} ${MYSQL_PASSWORD} ${MYSQL_DATABASE} ${GUID} ${CACHING_FACTOR}'

envsubst "$VARS" < "$SOURCE" > "$TARGET"
