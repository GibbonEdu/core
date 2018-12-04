<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

require getcwd().'/../config.php';
require getcwd().'/../functions.php';

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $customField = '005';

    // Grab all active students in Secondary
    $data = ['gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']];
    $sql = "SELECT gibbonPerson.gibbonPersonID, username, surname, preferredName, studentID, fields
            FROM gibbonPerson
            JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
            JOIN gibbonYearGroup ON (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID)
            WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
            AND gibbonYearGroup.sequenceNumber >= 011
            AND gibbonPerson.status = 'Full'
            ORDER BY surname, preferredName";

    $students = $pdo->select($sql, $data)->fetchAll();
    $updatedCount = 0;

    foreach ($students as $student) {
        $fields = !empty($student['fields']) ? unserialize($student['fields']) : [];
        $firstLetter = ord(strtoupper(substr($student['surname'], 0, 1)));
        $originalValue = $fields[$customField] ?? '';

        // Update the Academic Advisor field based on student last name
        if ($firstLetter >= ord('A') && $firstLetter <= ord('J')) {
            $newValue = "Jody Hubert";
        } elseif ($firstLetter >= ord('K') && $firstLetter <= ord('N')) {
            $newValue = "Lindsey Doland";
        } else if ($firstLetter >= ord('O') && $firstLetter <= ord('Z')) {
            $newValue = "Dan d'Entremont";
        }
        
        // Save the field if we've made changes
        if ($newValue != $originalValue) {
            $fields[$customField] = $newValue;

            $data = ['gibbonPersonID' => $student['gibbonPersonID'], 'fields' => serialize($fields)];
            $sql = "UPDATE gibbonPerson SET fields=:fields WHERE gibbonPersonID=:gibbonPersonID";

            $updated = $pdo->update($sql, $data);
            if ($updated) $updatedCount++;
        }
    }

    echo sprintf('Found %1$s students, updated %2$s fields.', count($students), $updatedCount)."\n";
}
