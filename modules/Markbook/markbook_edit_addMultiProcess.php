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

$enableEffort = getSettingByScope($connection2, 'Markbook', 'enableEffort');
$enableRubrics = getSettingByScope($connection2, 'Markbook', 'enableRubrics');

$gibbonCourseClassID = $_GET['gibbonCourseClassID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/markbook_edit_addMulti.php&gibbonCourseClassID=$gibbonCourseClassID";

if (isActionAccessible($guid, $connection2, '/modules/Markbook/markbook_edit_addMulti.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    if (empty($_POST)) {
        $URL .= '&return=warning1';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Validate Inputs
        $gibbonCourseClassIDMulti = $_POST['gibbonCourseClassIDMulti'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $type = $_POST['type'];
        $date = (!empty($_POST['date']))? dateConvert($guid, $_POST['date']) : date('Y-m-d');
        $gibbonSchoolYearTermID = (!empty($_POST['gibbonSchoolYearTermID']))? $_POST['gibbonSchoolYearTermID'] : null;
        //Sort out attainment
        $attainment = $_POST['attainment'];
        $attainmentWeighting = null;
        $attainmentRaw = 'N';
        $attainmentRawMax = null;
        if ($attainment == 'N') {
            $gibbonScaleIDAttainment = null;
            $gibbonRubricIDAttainment = null;
        } else {
            if ($_POST['gibbonScaleIDAttainment'] == '') {
                $gibbonScaleIDAttainment = null;
            } else {
                $gibbonScaleIDAttainment = $_POST['gibbonScaleIDAttainment'];
                if (isset($_POST['attainmentWeighting'])) {
                    if (is_numeric($_POST['attainmentWeighting']) && $_POST['attainmentWeighting'] > 0) {
                        $attainmentWeighting = $_POST['attainmentWeighting'];
                    }
                }
                if (isset($_POST['attainmentRawMax'])) {
                    if (is_numeric($_POST['attainmentRawMax']) && $_POST['attainmentRawMax'] > 0) {
                        $attainmentRawMax = $_POST['attainmentRawMax'];
                        $attainmentRaw = 'Y';
                    }
                }
            }
            if ($enableRubrics != 'Y') {
                $gibbonRubricIDAttainment = null;
            }
            else {
                if ($_POST['gibbonRubricIDAttainment'] == '') {
                    $gibbonRubricIDAttainment = null;
                } else {
                    $gibbonRubricIDAttainment = $_POST['gibbonRubricIDAttainment'];
                }
            }
        }
        //Sort out effort
        if ($enableEffort != 'Y') {
            $effort = 'N';
        }
        else {
            $effort = $_POST['effort'];
        }
        if ($effort == 'N') {
            $gibbonScaleIDEffort = null;
            $gibbonRubricIDEffort = null;
        } else {
            if ($_POST['gibbonScaleIDEffort'] == '') {
                $gibbonScaleIDEffort = null;
            } else {
                $gibbonScaleIDEffort = $_POST['gibbonScaleIDEffort'];
            }
            if ($enableRubrics != 'Y') {
                $gibbonRubricIDEffort = null;
            }
            else {
                if ($_POST['gibbonRubricIDEffort'] == '') {
                    $gibbonRubricIDEffort = null;
                } else {
                    $gibbonRubricIDEffort = $_POST['gibbonRubricIDEffort'];
                }
            }
        }
        $comment = $_POST['comment'];
        $uploadedResponse = $_POST['uploadedResponse'];
        $completeDate = $_POST['completeDate'];
        if ($completeDate == '') {
            $completeDate = null;
            $complete = 'N';
        } else {
            $completeDate = dateConvert($guid, $completeDate);
            $complete = 'Y';
        }
        $viewableStudents = $_POST['viewableStudents'];
        $viewableParents = $_POST['viewableParents'];
        $gibbonPersonIDCreator = $_SESSION[$guid]['gibbonPersonID'];
        $gibbonPersonIDLastEdit = $_SESSION[$guid]['gibbonPersonID'];

        //Lock markbook column table
        try {
            $sqlLock = 'LOCK TABLES gibbonMarkbookColumn WRITE';
            $resultLock = $connection2->query($sqlLock);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        //Get next groupingID
        try {
            $sqlGrouping = 'SELECT DISTINCT groupingID FROM gibbonMarkbookColumn WHERE NOT groupingID IS NULL ORDER BY groupingID DESC';
            $resultGrouping = $connection2->query($sqlGrouping);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $rowGrouping = $resultGrouping->fetch();
        if (is_null($rowGrouping['groupingID'])) {
            $groupingID = 1;
        } else {
            $groupingID = ($rowGrouping['groupingID'] + 1);
        }

        $time = time();
        //Move attached file, if there is one
        if ($_FILES['file']['tmp_name'] != '') {
            //Check for folder in uploads based on today's date
            $path = $_SESSION[$guid]['absolutePath'];
            if (is_dir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time)) == false) {
                mkdir($path.'/uploads/'.date('Y', $time).'/'.date('m', $time), 0777, true);
            }
            $unique = false;
            $count = 0;
            while ($unique == false and $count < 100) {
                $suffix = randomPassword(16);
                $attachment = 'uploads/'.date('Y', $time).'/'.date('m', $time).'/'.preg_replace('/[^a-zA-Z0-9]/', '', $name)."_$suffix".strrchr($_FILES['file']['name'], '.');
                if (!(file_exists($path.'/'.$attachment))) {
                    $unique = true;
                }
                ++$count;
            }
            if (!(move_uploaded_file($_FILES['file']['tmp_name'], $path.'/'.$attachment))) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            }
        } else {
            $attachment = '';
        }

        if (count($gibbonCourseClassIDMulti) < 1 or is_numeric($groupingID) == false or $groupingID < 1 or $name == '' or $description == '' or $type == '' or $date == '' or $viewableStudents == '' or $viewableParents == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            foreach ($gibbonCourseClassIDMulti as $gibbonCourseClassIDSingle) {
                //Write to database
                try {
                    $data = array('groupingID' => $groupingID, 'gibbonCourseClassID' => $gibbonCourseClassIDSingle, 'name' => $name, 'description' => $description, 'type' => $type, 'date' => $date, 'attainment' => $attainment, 'gibbonScaleIDAttainment' => $gibbonScaleIDAttainment, 'attainmentWeighting' => $attainmentWeighting, 'attainmentRaw' => $attainmentRaw, 'attainmentRawMax' => $attainmentRawMax, 'effort' => $effort, 'gibbonScaleIDEffort' => $gibbonScaleIDEffort, 'gibbonRubricIDAttainment' => $gibbonRubricIDAttainment, 'gibbonRubricIDEffort' => $gibbonRubricIDEffort, 'comment' => $comment, 'uploadedResponse' => $uploadedResponse, 'completeDate' => $completeDate, 'complete' => $complete, 'viewableStudents' => $viewableStudents, 'viewableParents' => $viewableParents, 'attachment' => $attachment, 'gibbonPersonIDCreator' => $gibbonPersonIDCreator, 'gibbonPersonIDLastEdit' => $gibbonPersonIDLastEdit, 'gibbonSchoolYearTermID' => $gibbonSchoolYearTermID);
                    $sql = 'INSERT INTO gibbonMarkbookColumn SET groupingID=:groupingID, gibbonCourseClassID=:gibbonCourseClassID, name=:name, description=:description, type=:type, date=:date, attainment=:attainment, gibbonScaleIDAttainment=:gibbonScaleIDAttainment, attainmentWeighting=:attainmentWeighting, attainmentRaw=:attainmentRaw, attainmentRawMax=:attainmentRawMax, effort=:effort, gibbonScaleIDEffort=:gibbonScaleIDEffort, gibbonRubricIDAttainment=:gibbonRubricIDAttainment, gibbonRubricIDEffort=:gibbonRubricIDEffort, comment=:comment, uploadedResponse=:uploadedResponse, completeDate=:completeDate, complete=:complete, viewableStudents=:viewableStudents, viewableParents=:viewableParents, attachment=:attachment, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit, gibbonSchoolYearTermID=:gibbonSchoolYearTermID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
            }

            //Unlock module table
            try {
                $sql = 'UNLOCK TABLES';
                $result = $connection2->query($sql);
            } catch (PDOException $e) {
            }

            if ($partialFail != false) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
