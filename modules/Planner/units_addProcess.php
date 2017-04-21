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

$gibbonSchoolYearID = $_GET['gibbonSchoolYearID'];
$gibbonCourseID = $_GET['gibbonCourseID'];
$classCount = $_POST['classCount'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/units_add.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID";
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['address'])."/units_edit.php&gibbonSchoolYearID=$gibbonSchoolYearID&gibbonCourseID=$gibbonCourseID";

if (isActionAccessible($guid, $connection2, '/modules/Planner/units_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_GET['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        if (!(isset($_POST))) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Validate Inputs
            $name = $_POST['name'];
            $description = $_POST['description'];
            $tags = $_POST['tags'];
            $active = $_POST['active'];
            $map = $_POST['map'];
            $ordering = $_POST['ordering'];
            $details = $_POST['details'];
            $license = $_POST['license'];
            $sharedPublic = null;
            if (isset($_POST['sharedPublic'])) {
                $sharedPublic = $_POST['sharedPublic'];
            }

            if ($gibbonSchoolYearID == '' or $gibbonCourseID == '' or $name == '' or $description == '' or $active == '' or $map == '' or $ordering == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                //Check access to specified course
                try {
                    if ($highestAction == 'Unit Planner_all') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID);
                        $sql = 'SELECT * FROM gibbonCourse WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID';
                    } elseif ($highestAction == 'Unit Planner_learningAreas') {
                        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonCourseID' => $gibbonCourseID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = "SELECT gibbonCourseID, gibbonCourse.name, gibbonCourse.nameShort FROM gibbonCourse JOIN gibbonDepartment ON (gibbonCourse.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) JOIN gibbonDepartmentStaff ON (gibbonDepartmentStaff.gibbonDepartmentID=gibbonDepartment.gibbonDepartmentID) WHERE gibbonDepartmentStaff.gibbonPersonID=:gibbonPersonID AND (role='Coordinator' OR role='Assistant Coordinator' OR role='Teacher (Curriculum)') AND gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonCourseID=:gibbonCourseID ORDER BY gibbonCourse.nameShort";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2'.$e->getMessage();
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() != 1) {
                    $URL .= '&return=error3';
                    header("Location: {$URL}");
                } else {
                    //Lock markbook column table
                    try {
                        $sql = 'LOCK TABLES gibbonUnit WRITE, gibbonUnitClass WRITE, gibbonUnitBlock WRITE,  gibbonUnitOutcome WRITE, gibbonFileExtension READ';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Get next autoincrement
                    try {
                        $sqlAI = "SHOW TABLE STATUS LIKE 'gibbonUnit'";
                        $resultAI = $connection2->query($sqlAI);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $rowAI = $resultAI->fetch();
                    $AI = str_pad($rowAI['Auto_increment'], 10, '0', STR_PAD_LEFT);

                    //Move attached file, if there is one
                    if ($_FILES['file']['tmp_name'] != '') {
                        $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                        $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                        // Upload the file, return the /uploads relative path
                        $attachment = $fileUploader->uploadFromPost($file, $name);

                        if (empty($attachment)) {
                            $partialFail = true;
                        }
                    } else {
                        $attachment = '';
                    }

                    //ADD CLASS RECORDS
                    $partialFail = false;
                    if ($classCount > 0) {
                        for ($i = 0;$i < $classCount;++$i) {
                            $running = $_POST['running'.$i];
                            if ($running != 'Y' and $running != 'N') {
                                $running = 'N';
                            }

                            try {
                                $dataClass = array('gibbonUnitID' => $AI, 'gibbonCourseClassID' => $_POST['gibbonCourseClassID'.$i], 'running' => $running);
                                $sqlClass = 'INSERT INTO gibbonUnitClass SET gibbonUnitID=:gibbonUnitID, gibbonCourseClassID=:gibbonCourseClassID, running=:running';
                                $resultClass = $connection2->prepare($sqlClass);
                                $resultClass->execute($dataClass);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }

                    //ADD BLOCKS
                    $blockCount = ($_POST['blockCount'] - 1);
                    $sequenceNumber = 0;
                    if ($blockCount > 0) {
                        $order = array();
                        if (isset($_POST['order'])) {
                            $order = $_POST['order'];
                        }
                        foreach ($order as $i) {
                            $title = '';
                            if ($_POST["title$i"] != "Block $i") {
                                $title = $_POST["title$i"];
                            }
                            $type2 = '';
                            if ($_POST["type$i"] != 'type (e.g. discussion, outcome)') {
                                $type2 = $_POST["type$i"];
                            }
                            $length = '';
                            if ($_POST["length$i"] != 'length (min)') {
                                $length = $_POST["length$i"];
                            }
                            $contents = $_POST["contents$i"];
                            $teachersNotes = $_POST["teachersNotes$i"];

                            if ($title != '' or $contents != '') {
                                try {
                                    $dataBlock = array('gibbonUnitID' => $AI, 'title' => $title, 'type' => $type2, 'length' => $length, 'contents' => $contents, 'teachersNotes' => $teachersNotes, 'sequenceNumber' => $sequenceNumber);
                                    $sqlBlock = 'INSERT INTO gibbonUnitBlock SET gibbonUnitID=:gibbonUnitID, title=:title, type=:type, length=:length, contents=:contents, teachersNotes=:teachersNotes, sequenceNumber=:sequenceNumber';
                                    $resultBlock = $connection2->prepare($sqlBlock);
                                    $resultBlock->execute($dataBlock);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                                ++$sequenceNumber;
                            }
                        }
                    }

                    //Insert outcomes
                    $count = 0;
                    $outcomeorder = null;
                    if (isset($_POST['outcomeorder'])) {
                        $outcomeorder = $_POST['outcomeorder'];
                    }
                    if (count($outcomeorder) > 0) {
                        foreach ($outcomeorder as $outcome) {
                            if ($_POST["outcomegibbonOutcomeID$outcome"] != '') {
                                try {
                                    $dataInsert = array('AI' => $AI, 'gibbonOutcomeID' => $_POST["outcomegibbonOutcomeID$outcome"], 'content' => $_POST["outcomecontents$outcome"], 'count' => $count);
                                    $sqlInsert = 'INSERT INTO gibbonUnitOutcome SET gibbonUnitID=:AI, gibbonOutcomeID=:gibbonOutcomeID, content=:content, sequenceNumber=:count';
                                    $resultInsert = $connection2->prepare($sqlInsert);
                                    $resultInsert->execute($dataInsert);
                                } catch (PDOException $e) {
                                    $partialFail = true;
                                }
                            }
                            ++$count;
                        }
                    }

                    //Write to database
                    try {
                        $data = array('gibbonCourseID' => $gibbonCourseID, 'name' => $name, 'description' => $description, 'tags' => $tags, 'active' => $active, 'map' => $map, 'ordering' => $ordering, 'license' => $license, 'sharedPublic' => $sharedPublic, 'attachment' => $attachment, 'details' => $details, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonPersonIDLastEdit' => $_SESSION[$guid]['gibbonPersonID']);
                        $sql = 'INSERT INTO gibbonUnit SET gibbonCourseID=:gibbonCourseID, name=:name, description=:description, tags=:tags, active=:active, map=:map, ordering=:ordering, license=:license, sharedPublic=:sharedPublic, attachment=:attachment, details=:details, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonPersonIDLastEdit=:gibbonPersonIDLastEdit';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    //Unlock module table
                    try {
                        $sql = 'UNLOCK TABLES';
                        $result = $connection2->query($sql);
                    } catch (PDOException $e) {
                    }

                    if ($partialFail == true) {
                        $URL .= '&return=warning1';
                        header("Location: {$URL}");
                    } else {
                        $URLSuccess = $URLSuccess."&return=success3&gibbonUnitID=$AI";
                        header("Location: {$URLSuccess}");
                    }
                }
            }
        }
    }
}
