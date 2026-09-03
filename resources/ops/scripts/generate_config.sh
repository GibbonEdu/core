#!/usr/bin/env bash

# Bail out upon error
set -e

# Display lines of this script as they are executed for debugging
#set -x

# Export all variables that need to be substituted in templates
set -a

# Setting up in-container application source variable (APP_SOURCE)
APP_SOURCE=/var/www/html

# Read env variables in same directory, from a file called .env.
# They are shared by both this script and Docker compose files.
cd $APP_SOURCE
#echo "Current working directory: $PWD"

if [ -f  ./.env ];then
    # echo "An .env file is present, sourcing it"
    source "./.env"
fi

# Print directory of this script
THIS_SCRIPT_DIR=`dirname "$BASH_SOURCE"`
#echo "Running ${THIS_SCRIPT_DIR}/generate_config.sh"

# Generate config files for Gibbon application using sed
SOURCE=${APP_SOURCE}/resources/ops/configuration/gibbon/config.php.dist
TARGET=${APP_SOURCE}/config.php
# Explicit list of placeholders to replace (only these will be expanded)
VARS='${MYSQL_HOST} ${MYSQL_USER} ${MYSQL_PASSWORD} ${MYSQL_DATABASE} ${GUID} ${CACHING_FACTOR}'

envsubst "$VARS" < "$SOURCE" > "$TARGET"

# printf "Done\n"
exit 0
