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
        SET value = 'http://gibbon.test'
        WHERE name = 'absoluteURL'
SQL
log 'OK: absoluteURL value is http://gibbon.test'

log 'Running acceptance tests'
${DOCKER_COMPOSE} run --rm test \
    /var/www/html/vendor/codeception/codeception/codecept \
    -c /var/www/html/tests/codeception.yml \
    run \
    "${@:-acceptance}"
log 'OK: Finished running acceptance tests'

log 'Reverting absoluteURL value in gibbonSetting table'
${DOCKER_COMPOSE} exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db \
    mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" <<'SQL'
        UPDATE gibbonSetting
        SET value = 'http://localhost:8080'
        WHERE name = 'absoluteURL'
SQL
log 'OK: absoluteURL value is http://localhost:8080'

#log "Delete admin user created by dump.sql"
#docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db \
#  mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" <<'SQL'
#    DELETE FROM gibbonPerson WHERE email='foobar_gibbon@mailinator.com';
#SQL
#log "OK: Deleted admin user"
#
#log "Creating admin user"
#docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db \
#  mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" <<'SQL'
#INSERT INTO gibbonPerson (
#  gibbonPersonID, title, surname, firstName, preferredName, officialName,
#  gender, username, email, passwordStrong, passwordStrongSalt, passwordForceReset,
#  status, canLogin, gibbonRoleIDPrimary, gibbonRoleIDAll,
#  viewCalendarSchool, viewCalendarPersonal, viewCalendarSpaceBooking, receiveNotificationEmails
#) VALUES (
#  '0000000001', 'Mr.', 'Bar', 'Foo', 'Foo', 'Bar, Foo',
#  'M', 'admin', 'foobar_gibbon@mailinator.com',
#  '5532db23077db329701297a10220be053d9cd87b8eb6023a069dbab66692f26b', 'JtexpYdvkayAIsACKmpWHq', 'N',
#  'Full', 'Y', 001, '001,002,003,004,006',
#  'Y', 'Y', 'Y', 'Y'
#)
#SQL
#log "OK: Created admin user"
