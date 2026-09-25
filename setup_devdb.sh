#!/usr/bin/env bash

# Run bash in strict mode
set -Eeuo pipefail

# Simple logger
log() { printf '%s\n' "$*"; }
err() { printf 'ERROR: %s\n' "$*" >&2; }

# Resolve project directory and default file locations
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SCHEMA_FILE="${SCHEMA_FILE:-${PROJECT_DIR}/gibbon.sql}"
DEMO_DATA_FILE="${DEMO_DATA_FILE:-${PROJECT_DIR}/gibbon_demo.sql}"

# Load env file if present
if [ -f "${PROJECT_DIR}"/.env ]; then
  set -a
  source .env
  set +a
fi

# --------
# Defaults
# --------
default_currency='HKD $'
default_analytics='<script></script>'

: "${ABSOLUTE_URL:=http://localhost:8080}"   # must match how the tests reach the app
: "${TIMEZONE:=Asia/Hong_Kong}"
: "${CURRENCY:=$default_currency}"
: "${COUNTRY:=Hong Kong}"
: "${ORGANISATION_NAME:=Syndicate of Worldwide Gibbon Testers}"
: "${ORGANISATION_NAME_SHORT:=JA}"
: "${ORGANISATION_EMAIL:=contact@mailinator.com}"
: "${EMAIL_LINK:=http://email.test}"
: "${WEB_LINK:=http://web.test}"
: "${ANALYTICS:=$default_analytics}"

# The schema dump is older than the code; roll the recorded version back to
# this one so the updater applies every later migration. Bump it when the
# base gibbon.sql is regenerated.
: "${BASE_VERSION:=30.0.00}"

# Admin account in gibbon database. The hash/salt below must correspond to the
# password that your Codeception config logs in with.
: "${ADMIN_ID:=0000000001}"
: "${ADMIN_PASSWORD_HASH:=5532db23077db329701297a10220be053d9cd87b8eb6023a069dbab66692f26b}"
: "${ADMIN_PASSWORD_SALT:=JtexpYdvkayAIsACKmpWHq}"

# -------
# Helpers
# -------
mysql_root()    { docker compose exec -T -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" db mysql -uroot "$@"; }
# Relaxed sql_mode avoids strict-mode failures on the demo data, e.g.
#   ERROR 1265 (01000): Data truncated for column 'ownershipType'
mysql_relaxed() { mysql_root --init-command="SET SESSION sql_mode='';" "$@"; }

# Escape a value for use inside a single-quoted SQL string literal.
sql_escape() {
  local s=$1 q="'"
  s=${s//\\/\\\\}
  s=${s//$q/$q$q}
  printf '%s' "$s"
}

# Queue a gibbonSetting update; apply_settings sends the whole batch at once.
SETTING_NAMES=()
SETTING_VALUES=()
SETTINGS_SQL=""
add_setting() {
  SETTING_NAMES+=("$1")
  SETTING_VALUES+=("$2")
  SETTINGS_SQL+="UPDATE gibbonSetting SET value='$(sql_escape "$2")' WHERE name='$1';"$'\n'
}

apply_settings() {
  if [[ ${#SETTING_NAMES[@]} -eq 0 ]]; then return 0; fi

  # An UPDATE on a misspelt name matches zero rows and still "succeeds",
  # so check every name exists first.
  local in_list missing i
  in_list=$(printf "'%s'," "${SETTING_NAMES[@]}")
  in_list=${in_list%,}
  missing=$(comm -23 \
    <(printf '%s\n' "${SETTING_NAMES[@]}" | sort -u) \
    <(mysql_relaxed -N -e "SELECT DISTINCT name FROM gibbonSetting WHERE name IN (${in_list})" "${MYSQL_DATABASE}" | sort -u))
  if [[ -n "$missing" ]]; then
    err "settings not found in gibbonSetting: $(tr '\n' ' ' <<<"$missing")"
    exit 1
  fi

  mysql_relaxed "$MYSQL_DATABASE" <<<"$SETTINGS_SQL"
  for i in "${!SETTING_NAMES[@]}"; do
    log "OK: ${SETTING_NAMES[$i]} = ${SETTING_VALUES[$i]}"
  done

  SETTING_NAMES=()
  SETTING_VALUES=()
  SETTINGS_SQL=""
}

wait_for_mysql() {
  # Require several consecutive successes: on a fresh volume the image's
  # temporary init server answers first and then restarts.
  local max_wait=60 ok=0 i
  for ((i = 0; i < max_wait; i++)); do
    if mysql_root -e 'SELECT 1' >/dev/null 2>&1; then
      ok=$((ok + 1))
      if [[ $ok -ge 3 ]]; then return 0; fi
    else
      ok=0
    fi
    sleep 1
  done
  return 1
}

# ---------
# Preflight
# ---------
command -v docker >/dev/null 2>&1 || { err "docker not found in PATH"; exit 1; }
docker compose config -q   # fails with compose's own message if the project is unusable
for f in "$SCHEMA_FILE" "$DEMO_DATA_FILE"; do
  [[ -r "$f" ]] || { err "cannot read $f"; exit 1; }
done

log "Cleaning up environment"
rm config.php 2>/dev/null || true
if [ ! -f config.php ]; then
  log "OK: config.php deleted"
fi

# --------------
# Reset database
# --------------
log "Waiting for MySQL to accept connections..."
wait_for_mysql || { err "MySQL did not become ready within 60s"; exit 3; }
log "OK: MySQL is ready"

mysql_root -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\`;"
log "OK: Recreated gibbon database"

log "Generating config.php"
docker compose run --rm config
[[ -f config.php ]] || { err "config.php was not created"; exit 1; }
log "OK: config.php created"

log "Executing $(basename "$SCHEMA_FILE") (this may take a few minutes)"
mysql_root "${MYSQL_DATABASE}" < "${SCHEMA_FILE}" || { err "Import schema failed"; exit 1; }
log "OK: Imported schema"

log "Executing $(basename "$DEMO_DATA_FILE")"
mysql_relaxed "${MYSQL_DATABASE}" < "${DEMO_DATA_FILE}" || { err "Import demo data failed"; exit 1; }
log "OK: Imported demo data"

# --------------------------
# Settings, then run Updater
# --------------------------
log "Applying gibbonSetting overrides"
add_setting absolutePath           /var/www/html
add_setting absoluteURL            "${ABSOLUTE_URL}"
add_setting timezone               "${TIMEZONE}"
add_setting currency               "${CURRENCY}"
add_setting organisationName       "${ORGANISATION_NAME}"
add_setting organisationNameShort  "${ORGANISATION_NAME_SHORT}"
add_setting organisationEmail      "${ORGANISATION_EMAIL}"
add_setting country                "${COUNTRY}"
add_setting emailLink              "${EMAIL_LINK}"
add_setting webLink                "${WEB_LINK}"
add_setting analytics              "${ANALYTICS}"
add_setting installType            Development
add_setting version                "${BASE_VERSION}"
apply_settings

log "Running Updater"
docker compose exec -T app php -r '
require "/var/www/html/gibbon.php";
$updater = $container->get(\Gibbon\Database\Updater::class);
if (!$updater->isUpdateRequired()) {
    echo "OK: Database is already up-to-date.\n";
    exit(0);
}
$errors = $updater->update();
if (!empty($errors)) {
    fwrite(STDERR, print_r($errors, true));
    exit(1);
}
echo "OK: Updater completed successfully.\n";
'

# ----------
# Admin user
# ----------
log "Creating admin user"
mysql_relaxed "${MYSQL_DATABASE}" <<SQL
INSERT INTO gibbonPerson (
  gibbonPersonID, title, surname, firstName, preferredName, officialName,
  gender, username, email, passwordStrong, passwordStrongSalt, passwordForceReset,
  status, canLogin, gibbonRoleIDPrimary, gibbonRoleIDAll,
  viewCalendarSchool, viewCalendarPersonal, viewCalendarSpaceBooking, receiveNotificationEmails
) VALUES (
  '${ADMIN_ID}', 'Mr.', 'Bar', 'Foo', 'Foo', 'Bar, Foo',
  'M', 'admin', 'foobar_gibbon@mailinator.com',
  '${ADMIN_PASSWORD_HASH}', '${ADMIN_PASSWORD_SALT}', 'N',
  'Full', 'Y', 001, '001,002,003,004,006',
  'Y', 'Y', 'Y', 'Y'
);
SQL
log "OK: Created admin user"

log "Creating entry in gibbonStaff table for admin user"
mysql_relaxed "${MYSQL_DATABASE}" <<SQL
INSERT INTO gibbonStaff (
  gibbonPersonID, type, initials, jobTitle, firstAidQualified,
  firstAidQualification, firstAidExpiry, countryOfOrigin, qualifications,
  biography, biographicalGrouping, biographicalGroupingPriority,
  coverageExclude, coveragePriority, fields
) VALUES (
  '${ADMIN_ID}', 'Teaching', NULL, '', '',
  NULL, NULL, '', '',
  '', '', 0,
  'N', 0, NULL
);
SQL
log "OK: Created gibbonStaff table entry"

log "Pointing organisation roles at admin user"
for setting in organisationAdministrator organisationDBA organisationAdmissions organisationHR; do
  add_setting "${setting}" "${ADMIN_ID}"
done
apply_settings

log "Done. Gibbon should be reachable at ${ABSOLUTE_URL} (user: admin)."
