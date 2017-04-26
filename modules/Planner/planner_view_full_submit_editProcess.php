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

//Gibbon system-wide includes
include '../../functions.php';
include '../../config.php';

//Module includes
include './moduleFunctions.php';

//New PDO DB connection
$pdo = new Gibbon\sqlConnection();
$connection2 = $pdo->getConnection();

@session_start();

$gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=".$_POST['search'].$_POST['params'];

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full_submit_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if planner specified
        if ($gibbonPlannerEntryID == '') {
            $URL .= '&return=error1a';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                if ($_POST['submission'] != 'true' and $_POST['submission'] != 'false') {
                    $URL .= '&return=error1b';
                    header("Location: {$URL}");
                } else {
                    if ($_POST['submission'] == 'true') {
                        $submission = true;
                        $gibbonPlannerEntryHomeworkID = $_POST['gibbonPlannerEntryHomeworkID'];
                    } else {
                        $submission = false;
                        $gibbonPersonID = $_POST['gibbonPersonID'];
                    }

                    $type = null;
                    if (isset($_POST['type'])) {
                        $type = $_POST['type'];
                    }
                    $version = null;
                    if (isset($_POST['version'])) {
                        $version = $_POST['version'];
                    }
                    $link = null;
                    if (isset($_POST['link'])) {
                        $link = $_POST['link'];
                    }
                    $status = null;
                    if (isset($_POST['status'])) {
                        $status = $_POST['status'];
                    }
                    $gibbonPlannerEntryID = null;
                    if (isset($_POST['gibbonPlannerEntryID'])) {
                        $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
                    }
                    $count = null;
                    if (isset($_POST['count'])) {
                        $count = $_POST['count'];
                    }
                    $lesson = null;
                    if (isset($_POST['lesson'])) {
                        $lesson = $_POST['lesson'];
                    }

                    if (($submission == true and $gibbonPlannerEntryHomeworkID == '') or ($submission == false and ($gibbonPersonID == '' or $type == '' or $version == '' or ($type == 'File' and $_FILES['file']['name'] == '') or ($type == 'Link' and $link == '') or $status == '' or $lesson == '' or $count == ''))) {
                        $URL .= '&return=error1';
                        header("Location: {$URL}");
                    } else {
                        if ($submission == true) {
                            try {
                                $data = array('status' => $status, 'gibbonPlannerEntryHomeworkID' => $gibbonPlannerEntryHomeworkID);
                                $sql = 'UPDATE gibbonPlannerEntryHomework SET status=:status WHERE gibbonPlannerEntryHomeworkID=:gibbonPlannerEntryHomeworkID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }
                            $URL .= '&return=success0';
                            header("Location: {$URL}");
                        } else {
                            $partialFail = false;
                            $attachment = null;
                            if ($type == 'Link') {
                                if (substr($link, 0, 7) != 'http://' and substr($link, 0, 8) != 'https://') {
                                    $partialFail = true;
                                } else {
                                    $attachment = $link;
                                }
                            }
                            if ($type == 'File') {
                                $fileUploader = new Gibbon\FileUploader($pdo, $gibbon->session);

                                $file = (isset($_FILES['file']))? $_FILES['file'] : null;

                                // Upload the file, return the /uploads relative path
                                $attachment = $fileUploader->uploadFromPost($file, $_SESSION[$guid]['username'].'_'.$lesson);

                                if (empty($attachment)) {
                                    $partialFail = true;
                                }
                            }

                            //Deal with partial fail
                            if ($partialFail == true) {
                                $URL .= '&return=error6';
                                header("Location: {$URL}");
                            } else {
                                //Write to database
                                try {
                                    $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $gibbonPersonID, 'type' => $type, 'version' => $version, 'status' => $status, 'location' => $attachment, 'count' => ($count + 1), 'timestamp' => date('Y-m-d H:i:s'));
                                    $sql = 'INSERT INTO gibbonPlannerEntryHomework SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, type=:type, version=:version, status=:status, location=:location, count=:count, timestamp=:timestamp';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

                                $URL .= '&return=success0';
                                header("Location: {$URL}");
                            }
                        }
                    }
                }
            }
        }
    }
}
