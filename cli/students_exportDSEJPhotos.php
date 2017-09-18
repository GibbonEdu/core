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
//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Check for CLI, so this cannot be run through browser
if (php_sapi_name() != 'cli') { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {

    $photoPathDSEJ = $_SESSION[$guid]['absolutePath'] .'/uploads/photosDSEJ/';
    if (is_dir($photoPathDSEJ)==FALSE) {
        mkdir($photoPathDSEJ, 0755, TRUE);
    }

    try {
        $dataField = array( 'name' => 'DSEJ ID Number' );
        $sqlField = 'SELECT gibbonPersonFieldID FROM gibbonPersonField WHERE name=:name LIMIT 1';
        $resultField = $connection2->prepare($sqlField);
        $resultField->execute($dataField);
    } catch (PDOException $e) {
        die("Your request failed due to a database error.\n");
    }

    if ($resultField->rowCount() == 0) die("Could not load DSEJ field info.\n");

    $fieldID = $resultField->fetchColumn(0);

    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'] );
        $sql = "SELECT username, surname, preferredName, studentID, image_240, fields, gibbonRollGroup.nameShort as rollGroup from gibbonPerson
        JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID)
        JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
        WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID
        AND gibbonPerson.status = 'Full'
        ORDER BY surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        die("Your request failed due to a database error.\n");
    }

    if ($result->rowCount() == 0) die("Could not load student info.\n");

    $restrainedQuality = 95; //0 = lowest, 100 = highest. ~75 = default
    $sizeLimit = 1024 * 100;

    $countStudents = 0;
    $countDSEJID = 0;
    $countCopied = 0;
    $countResized = 0;

    $missingDSEJ = '';
    $missingPhoto = '';

    while ($row = $result->fetch()) {
        $countStudents++;

        $fields = unserialize( $row['fields'] );
        $dsejID = (isset($fields[$fieldID]))? $fields[$fieldID] : '';

        $photoPath = $_SESSION[$guid]['absolutePath'] .'/'. $row['image_240'];
        $photoPathRollGroup = $photoPathDSEJ . $row['rollGroup'] .'/';

        if (is_dir( $photoPathRollGroup )==FALSE) {
            mkdir( $photoPathRollGroup , 0777, TRUE) ;
        }

        if (file_exists($photoPath) && !empty($dsejID)) {
            $countDSEJID++;

            if ( filesize($photoPath) < $sizeLimit) {
                $countCopied++;
                copy($photoPath, $photoPathRollGroup . $dsejID . '.jpg' );
            } else {
                //create a image resource from the contents of the uploaded image
                $resource = @imagecreatefromjpeg( $photoPath );

                if(!$resource) {
                    die('Something wrong with the file reader!');
                }

                //move the uploaded file with a lesser quality
                imagejpeg($resource, $photoPathRollGroup . $dsejID . '.jpg', $restrainedQuality);
                imagedestroy($resource);

                if ( filesize($photoPathRollGroup . $dsejID . '.jpg') > $sizeLimit ) {
                    echo "File still too large: " . $dsejID . '.jpg' . "\n";
                }
                $countResized++;
            }
        } else {
            if (empty($dsejID)) {
                $missingDSEJ .= $row['surname'].', '. $row['preferredName'] .' ('.$row['rollGroup'].', Student ID:'. $row['studentID'] .')' . "\n";
            }

            if (!file_exists($photoPath)) {
                $missingPhoto .= $row['surname'].', '. $row['preferredName'] .' ('.$row['rollGroup'].', Student ID:'. $row['studentID'] .')' . "\n";
            }
        }
    }

    if (!empty($missingPhoto)) {
        echo "Missing Photos: \n";
        echo $missingPhoto . "\n\n";
    }

    if (!empty($missingDSEJ)) {
        echo "Missing DSEJ ID: \n";
        echo $missingDSEJ . "\n\n";
    }

    echo "Students Total: " . $countStudents . "\n";
    echo "DSEJ Id's Found: " . $countDSEJID . "\n";
    echo "Photos copied: " . $countCopied . "\n";
    echo "Photos resized: " . $countResized . "\n";
}
