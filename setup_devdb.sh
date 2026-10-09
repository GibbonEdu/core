#!/usr/bin/env bash
# Load schema + demo data into the developer Docker database.

set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "${PROJECT_DIR}/resources/ops/scripts/dev-common.sh"

SCHEMA_FILE="${SCHEMA_FILE:-${PROJECT_DIR}/gibbon.sql}"
DEMO_DATA_FILE="${DEMO_DATA_FILE:-${PROJECT_DIR}/gibbon_demo.sql}"

load_env
require_docker

: "${ABSOLUTE_URL:=http://localhost:8080}"
: "${TIMEZONE:=UTC}"
: "${CURRENCY:=HKD \$}"
: "${COUNTRY:=Hong Kong}"
: "${ORGANISATION_NAME:=Gibbon Testing}"
: "${ORGANISATION_NAME_SHORT:=GiT}"
: "${ORGANISATION_EMAIL:=testing@gibbon.test}"
: "${EMAIL_LINK:=}"
: "${WEB_LINK:=}"
: "${ANALYTICS:=}"
: "${ADMIN_ID:=0000000001}"
: "${ADMIN_PASSWORD_HASH:=5532db23077db329701297a10220be053d9cd87b8eb6023a069dbab66692f26b}"
: "${ADMIN_PASSWORD_SALT:=JtexpYdvkayAIsACKmpWHq}"
: "${MYSQL_ROOT_PASSWORD:?MYSQL_ROOT_PASSWORD is not set. Copy .env-example to .env.}"
: "${MYSQL_DATABASE:?MYSQL_DATABASE is not set. Copy .env-example to .env.}"

# Relaxed sql_mode avoids strict-mode failures on demo rows such as
# ERROR 1265 (01000): Data truncated for column 'ownershipType'
mysql_relaxed() {
    mysql_root --init-command="SET SESSION sql_mode='';" "$@"
}

SETTING_NAMES=()
SETTING_VALUES=()
SETTINGS_SQL=""

add_setting() {
    SETTING_NAMES+=("$1")
    SETTING_VALUES+=("$2")
    SETTINGS_SQL+="UPDATE gibbonSetting SET value='$(sql_escape "$2")' WHERE name='$1';"$'\n'
}

apply_settings() {
    if [[ ${#SETTING_NAMES[@]} -eq 0 ]]; then
        return 0
    fi

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

docker_compose config -q >/dev/null
for f in "$SCHEMA_FILE" "$DEMO_DATA_FILE"; do
    [[ -r "$f" ]] || { err "cannot read $f"; exit 1; }
done

log "Cleaning up environment"
rm -f "${PROJECT_DIR}/config.php"
log "OK: config.php deleted"

log "Waiting for MySQL to accept connections..."
wait_for_mysql || { err "MySQL did not become ready within 60s"; exit 3; }
log "OK: MySQL is ready"

mysql_root -e "DROP DATABASE IF EXISTS \`${MYSQL_DATABASE}\`; CREATE DATABASE \`${MYSQL_DATABASE}\`;"
log "OK: Recreated gibbon database"

log "Generating config.php"
docker_compose run --rm config
[[ -f "${PROJECT_DIR}/config.php" ]] || { err "config.php was not created"; exit 1; }
# Lint inside the app container so PHP is not needed on the host. The path is relative to the
# container working directory (/var/www/html) because Git Bash on Windows rewrites absolute paths.
docker_compose exec -T app php -l config.php >/dev/null || { err "generated config.php is not valid PHP (check GUID and CACHING_FACTOR in .env)"; exit 1; }
log "OK: config.php created"

log "Executing $(basename "$SCHEMA_FILE") (this may take a few minutes)"
mysql_root "${MYSQL_DATABASE}" < "${SCHEMA_FILE}" || { err "Import schema failed"; exit 1; }
log "OK: Imported schema"

log "Executing $(basename "$DEMO_DATA_FILE")"
mysql_relaxed "${MYSQL_DATABASE}" < "${DEMO_DATA_FILE}" || { err "Import demo data failed"; exit 1; }
log "OK: Imported demo data"

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
add_setting cuttingEdgeCode        Y
add_setting cuttingEdgeCodeLine    0
apply_settings

# gibbon.sql already records the current schema version. Only run Updater if
# version.php is ahead of the dump (cutting-edge / unreleased CHANGEDB lines).
log "Checking whether a database update is required"
docker_compose exec -T app php -r '
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

log "Creating admin user"
mysql_relaxed "${MYSQL_DATABASE}" <<SQL
INSERT INTO gibbonPerson (
  gibbonPersonID, title, surname, firstName, preferredName, officialName,
  gender, username, email, passwordStrong, passwordStrongSalt, passwordForceReset,
  status, canLogin, gibbonRoleIDPrimary, gibbonRoleIDAll,
  viewCalendarSchool, viewCalendarPersonal, viewCalendarSpaceBooking, receiveNotificationEmails
) VALUES (
  '$(sql_escape "${ADMIN_ID}")', 'Mr.', 'Bar', 'Foo', 'Foo', 'Bar, Foo',
  'M', 'admin', 'foobar_gibbon@mailinator.com',
  '$(sql_escape "${ADMIN_PASSWORD_HASH}")', '$(sql_escape "${ADMIN_PASSWORD_SALT}")', 'N',
  'Full', 'Y', 001, '001,002,003,004,006',
  'Y', 'Y', 'Y', 'Y'
);
SQL
log "OK: Created admin user"

log "Creating gibbonStaff entry for admin user"
mysql_relaxed "${MYSQL_DATABASE}" <<SQL
INSERT INTO gibbonStaff (
  gibbonPersonID, type, initials, jobTitle, firstAidQualified,
  firstAidQualification, firstAidExpiry, countryOfOrigin, qualifications,
  biography, biographicalGrouping, biographicalGroupingPriority,
  coverageExclude, coveragePriority, fields
) VALUES (
  '$(sql_escape "${ADMIN_ID}")', 'Teaching', NULL, '', '',
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
