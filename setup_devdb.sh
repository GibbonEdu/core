#!/usr/bin/env bash

# Resolve script directory and default file locations
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="${SCHEMA_FILE:-${SCRIPT_DIR}/gibbon.sql}"
DEMO_DATA_FILE="${DEMO_DATA_FILE:-${SCRIPT_DIR}/gibbon_demo.sql}"

# Bail out upon error
set -e

# Display lines for debugging
#set -x

# Export variables to be substituted in templates
set -a

# Load env file if present
if [ -f .env ]; then
  source .env
fi

printf "Cleaning up environment\n"
# Delete config.php
rm config.php 2>/dev/null || true
if [ ! -f config.php ]; then
  printf "OK: config.php deleted\n"
fi
# Drop and recreate database
docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\`;"
printf "OK: recreated gibbon database\n"

printf "Generating config.php\n"
docker compose run --rm config
if [ -f config.php ]; then
  printf "OK: config.php created\n"
fi

printf "Executing gibbon.sql [this will take a few minutes]\n"
if docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot "${MYSQL_DATABASE}" < "$SCHEMA_FILE"; then
  printf "OK: imported schema\n"
else
  printf "ERROR: import schema\n" >&2
  exit 1
fi

## Import demo data into database in relaxed sql_mode
## Uses relaxed sql_mode to avoid issues with strict mode when importing demo data
## ERROR 1265 (01000) at line 7937: Data truncated for column 'ownershipType' at row 1
printf "Executing gibbon_demo.sql\n"
if docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" < "$DEMO_DATA_FILE"; then
  printf "OK: imported demo data\n"
else
  printf "ERROR: import demo data\n" >&2
  exit 1
fi

printf "Creating admin user\n"
docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db \
  mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" <<'SQL'
INSERT INTO gibbonPerson (
  gibbonPersonID, title, surname, firstName, preferredName, officialName,
  gender, username, email, passwordStrong, passwordStrongSalt, passwordForceReset,
  status, canLogin, gibbonRoleIDPrimary, gibbonRoleIDAll,
  viewCalendarSchool, viewCalendarPersonal, viewCalendarSpaceBooking, receiveNotificationEmails
) VALUES (
  '0000000001', 'Mr.', 'Bar', 'Foo', 'Foo', 'Foo Bar',
  'M', 'admin', 'foobar_gibbon@mailinator.com',
  '5532db23077db329701297a10220be053d9cd87b8eb6023a069dbab66692f26b', 'JtexpYdvkayAIsACKmpWHq', 'N',
  'Full', 'Y', '0000000001', '001',
  'Y', 'Y', 'Y', 'Y'
)
SQL
printf "OK: Created admin user\n"
