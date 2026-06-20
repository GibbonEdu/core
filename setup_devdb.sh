#!/usr/bin/env bash

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

echo "Generating config.php"
docker compose run --rm config

echo "Executing gibbon.sql"
docker compose exec -T db mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < gibbon.sql

## Import demo data into database in relaxed sql_mode
## Uses relaxed sql_mode to avoid issues with strict mode when importing demo data
## ERROR 1265 (01000) at line 7937: Data truncated for column 'ownershipType' at row 1
echo "Executing gibbon_demo.sql"
docker compose exec -T db mysql --init-command="SET SESSION sql_mode='';" -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < gibbon_demo.sql

echo "Creating admin user"
docker compose exec -T db mysql --init-command="SET SESSION sql_mode='';" -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" <<SQL
INSERT INTO gibbonPerson (gibbonPersonID, title, surname, firstName, preferredName, officialName, nameInCharacters, gender, username, email, passwordStrong, passwordStrongSalt, passwordForceReset, status, canLogin, gibbonRoleIDPrimary, gibbonRoleIDAll, viewCalendarSchool, viewCalendarPersonal,viewCalendarSpaceBooking,receiveNotificationEmails, address1, address1District, address1Country,address2,address2District,address2Country,phone1CountryCode,phone1,phone3CountryCode,phone3,phone2CountryCode,phone2,phone4CountryCode,phone4,website,languageFirst,languageSecond,languageThird,countryOfBirth,birthCertificateScan,ethnicity,religion)
VALUES (0000000001, 'Mr.', 'Bar', 'Foo', 'Foo', 'Foo Bar', '', 'M', 'admin', 'foobar_gibbon@mailinator.com', '5532db23077db329701297a10220be053d9cd87b8eb6023a069dbab66692f26b','JtexpYdvkayAIsACKmpWHq','N','Full','Y', 0000000001,001,'Y','Y','Y','Y','','','','','','','','','','','','','','','','','','','','','','');
SQL
