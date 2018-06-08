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
require getcwd().'/../lib/PHPMailer/PHPMailerAutoload.php';
require getcwd().'/../lib/PHPExcel/Classes/PHPExcel.php';

function alpha2num($column) {
    $number = 0;
    foreach(str_split($column) as $letter){
        $number = ($number * 26) + (ord(strtolower($letter)) - 96);
    }
    return $number;
}

function num2alpha($n)
{
    for ($r = ''; $n >= 0; $n = intval($n / 26) - 1) {
        $r = chr($n % 26 + 0x41).$r;
    }

    return $r;
}

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

    $schoolYear = '012';
    $filePath = 'homeroomList.xlsx';
    $fileOutput = 'homeroomList-updated.xlsx';

    // Try to use the best reader if available, otherwise catch any read errors
    try {
        $objPHPExcel = \PHPExcel_IOFactory::load( $filePath );
    } catch(\PHPExcel_Reader_Exception $e) {
        $this->errorID = importer::ERROR_IMPORT_FILE;
        return false;
    }

    $objWorksheet = $objPHPExcel->getActiveSheet();
    $lastColumn = $objWorksheet->getHighestDataColumn();

    $nextColumn = num2alpha(alpha2num($lastColumn));
    $objWorksheet->setCellValue($nextColumn.'1', 'Student ID');

    $studentCount = 0;
    $studentFoundCount = 0;

    // Grab the header & first row for Step 1
    foreach( $objWorksheet->getRowIterator(2) as $rowIndex => $row ){

        $array = $objWorksheet->rangeToArray('A'.$rowIndex.':'.$lastColumn.$rowIndex, null, true, true, false);

        $studentName = isset($array[0][0])? $array[0][0] : '';
        $yearGroup = isset($array[0][1])? $array[0][1] : '';

        // Parse the student name from surname, preferredName (firstName)
        $matches = array();
        preg_match_all('/([A-Za-z \'\.\-])+/', $studentName, $matches);
        list($surname, $preferredName, $firstName) = array_pad($matches[0], 3, '');

        // Locate a student enrolment for the previous year group with a matching student name
        $data = ['gibbonSchoolYearID' => $schoolYear, 'yearGroup' => $yearGroup, 'preferredName' => trim($preferredName), 'surname' => trim($surname) ];
        $sql = "SELECT gibbonPerson.username as studentID 
                FROM gibbonPerson 
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonYearGroupID=(SELECT MAX(gibbonYearGroupID) FROM gibbonYearGroup WHERE sequenceNumber < (SELECT sequenceNumber FROM gibbonYearGroup WHERE gibbonYearGroup.nameShort=:yearGroup))
                AND (
                    (gibbonPerson.surname LIKE CONCAT('%', :surname, '%') AND gibbonPerson.preferredName LIKE :preferredName)
                    OR (gibbonPerson.surname LIKE :surname AND gibbonPerson.preferredName LIKE CONCAT('%', :preferredName, '%'))
                )";

        $result = $pdo->select($sql, $data);

        if ($result->rowCount() == 1) {
            $foundValue = $result->fetchColumn();
            $studentFoundCount++;
        } else {
            $foundValue = '';
        }

        // Write the ID to the last column
        $nextColumn = num2alpha(alpha2num($lastColumn));
        $objWorksheet->setCellValue($nextColumn.$rowIndex, $foundValue);

        $studentCount++;
    }

    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save($fileOutput);

    echo "Students: {$studentCount}  IDs Found: {$studentFoundCount}  \n";
}

