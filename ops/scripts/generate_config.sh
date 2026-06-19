#!/usr/bin/env bash

# bail out upon error
set -e

# display the lines of this script as they are executed for debugging
#set -x

# export all variables that need to be substitued in templates
set -a

# Setting up in-container application source variable (APP_SOURCE).
# It's the counterpart of the host variable APPLICATION
APP_SOURCE=/var/www/html


# read env variables in same directory, from a file called .env.
# They are shared by both this script and Docker compose files.
cd $APP_SOURCE
echo "Current working directory: $PWD"

if [ -f  ./.env ];then
    echo "An .env file is present, sourcing it"
    source "./.env"
fi

# Print directory of this script. We will need it to find nginx config
THIS_SCRIPT_DIR=`dirname "$BASH_SOURCE"`
echo "Running ${THIS_SCRIPT_DIR}/generate_config.sh"

# Generate config files for gigadb-website application using sed
SOURCE=${APP_SOURCE}/ops/configuration/config.php.dist
TARGET=${APP_SOURCE}/config.php
# explicit list of placeholders to replace (only these will be expanded)
VARS='${MYSQL_HOST} ${MYSQL_USER} ${MYSQL_PASSWORD} ${MYSQL_DATABASE} ${GUID} ${CACHING_FACTOR}'

envsubst "$VARS" < "$SOURCE" > "$TARGET"

echo "Done."
exit 0
