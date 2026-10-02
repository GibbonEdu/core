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

# Simple logger
log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

log "Cleaning up environment"
# Delete config.php
rm config.php 2>/dev/null || true
if [ ! -f config.php ]; then
  log "OK: config.php deleted"
fi

# Drop and recreate database
docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\`;"
log "OK: Recreated gibbon database"

log "Generating config.php"
docker compose run --rm config
if [ -f config.php ]; then
  log "OK: config.php created"
fi

# Wait for MySQL to be ready
log "Waiting for MySQL to accept connections..."
max_wait=60
i=0
until docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot -e 'SELECT 1' >/dev/null 2>&1; do
  sleep 1
  i=$((i+1))
  if [ "$i" -ge "$max_wait" ]; then
    err "ERROR: MySQL did not become ready within ${max_wait}s"
    exit 3
  fi
done
log "OK: MySQL is ready"

log "Executing gibbon.sql (this may take a few minutes)"
if docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql -uroot "${MYSQL_DATABASE}" < "$SCHEMA_FILE"; then
  log "OK: Imported schema"
else
  err "ERROR: Import schema"
  exit 1
fi

## Import demo data into database in relaxed sql_mode
## Uses relaxed sql_mode to avoid issues with strict mode when importing demo data
## ERROR 1265 (01000) at line 7937: Data truncated for column 'ownershipType' at row 1
log "Executing gibbon_demo.sql"
if docker compose exec -T -e MYSQL_PWD="${MYSQL_ROOT_PASSWORD}" db mysql --init-command="SET SESSION sql_mode='';" -uroot "${MYSQL_DATABASE}" < "$DEMO_DATA_FILE"; then
  log "OK: Imported demo data"
else
  err "ERROR: Import demo data"
  exit 1
fi

log "Creating admin user"
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
log "OK: Created admin user"
