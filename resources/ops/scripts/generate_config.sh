#!/usr/bin/env bash

# Bail out upon error
set -e

# Export all variables that need to be substituted in templates
set -a

# Setting up in-container application source variable (APP_SOURCE)
APP_SOURCE=/var/www/html

# Read env variables in same directory, from a file called .env.
# They are shared by both this script and Docker compose files.
cd $APP_SOURCE

if [ -f  ./.env ];then
    source "./.env"
fi

: "${MYSQL_HOST:=gibbon_db}"
: "${MYSQL_USER:=gibbon}"
: "${MYSQL_DATABASE:=gibbon}"
: "${GUID:=wocin5trm-vitd-iws-g2k-3huw6nzxtfp}"
: "${CACHING_FACTOR:=10}"

# Generate config files for Gibbon application using sed
SOURCE=${APP_SOURCE}/resources/ops/configuration/gibbon/config.php.dist
TARGET=${APP_SOURCE}/config.php
# Explicit list of placeholders to replace (only these will be expanded)
VARS='${MYSQL_HOST} ${MYSQL_USER} ${MYSQL_PASSWORD} ${MYSQL_DATABASE} ${GUID} ${CACHING_FACTOR}'

envsubst "$VARS" < "$SOURCE" > "$TARGET"
exit 0
