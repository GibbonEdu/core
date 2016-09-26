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

include '../../functions.php';
include '../../config.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();


//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Markbook/markbook_edit.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_copy.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $gibbonMarkbookCopyClassID = (isset($_GET['gibbonMarkbookCopyClassID']))? $_GET['gibbonMarkbookCopyClassID'] : null;
    $copyColumnID = (isset($_POST['copyColumnID']))? $_POST['copyColumnID'] : null;

    if (empty($_POST)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else if (empty($gibbonCourseClassID) || empty($gibbonMarkbookCopyClassID) || empty($copyColumnID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {

        try {
            $data2 = array('gibbonCourseClassID' => $gibbonMarkbookCopyClassID);
            $sql2 = 'SELECT * FROM gibbonMarkbookColumn WHERE gibbonCourseClassID=:gibbonCourseClassID';
            $result2 = $connection2->prepare($sql2);
            $result2->execute($data2);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result2->rowCount() <= 0) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
            exit();
        } else {

            $partialFail = false;
            while ($column = $result2->fetch() ) {

                // Only include the selected columns
                if ( isset($copyColumnID[ $column['gibbonMarkbookColumnID'] ]) && $column['gibbonMarkbookColumnID'] == true ) {

                    //Write to database
                    try {
                        $date = (!empty($_POST['date']))? dateConvert($guid, $_POST['date']) : date('Y-m-d');
                        $data = array('gibbonUnitID' => $column['gibbonUnitID'], 'gibbonHookID' => $column['gibbonHookID'], 'gibbonPlannerEntryID' => $column['gibbonPlannerEntryID'], 'gibbonCourseClassID' => $gibbonCourseClassID, 'name' => $column['name'], 'description' => $column['description'], 'type' => $column['type'], 'date' => $date, 'sequenceNumber' => $column['sequenceNumber'], 'attainment' => $column['attainment'], 'gibbonScaleIDAttainment' => $column['gibbonScaleIDAttainment'], 'attainmentWeighting' => $column['attainmentWeighting'], 'attainmentRaw' => $column['attainmentRaw'], 'attainmentRawMax' => $column['attainmentRawMax'], 'effort' => $column['effort'], 'gibbonScaleIDEffort' => $column['gibbonScaleIDEffort'], 'gibbonRubricIDAttainment' => $column['gibbonRubricIDAttainment'], 'gibbonRubricIDEffort' => $column['gibbonRubricIDEffort'], 'comment' => $column['comment'], 'uploadedResponse' => $column['uploadedResponse'], 'viewableStudents' => $column['viewableStudents'], 'viewableParents' => $column['viewableParents'], 'attachment' => $column['attachment'], 'gibbonPersonIDCreator' => $column['gibbonPersonIDCreator'], 'gibbonPersonIDLastEdit' => $column['gibbonPersonIDLastEdit'], 'gibbonSchoolYearTermID' => $column['gibbonSchoolYearTermID']);
                    $sql = 'INSERT INTO gibbonMarkbookColumn SET gibbonUnitID=:gibbonUnitID, gibbonHookID=:gibbonHookID, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, date=:date, sequenceNumber=:sequenceNumber, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, attainmentWeighting=:attainmentWeighting, attainmentRaw=:attainmentRaw, attainmentRawMax=:attainmentRawMax, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, gibbonRubricIDEffort=:gibbonRubricIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, gibbonSchoolYearTermID=:gibbonSchoolYearTermID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }

                }

                if ($partialFail) {
                    $URL .= '&return=warning1';
                    header("Location: {$URL}");
                    exit();
                } else {
                    $URL .= "&return=success0";
                    header("Location: {$URL}");
                }
            }
            
        }
    }
}

?>