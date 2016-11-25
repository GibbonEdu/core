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

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

//Set timezone from session variable
date_default_timezone_set($_SESSION[$guid]['timezone']);

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/firstAidRecord_add.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'];

if (isActionAccessible($guid, $connection2, '/modules/Students/firstAidRecord_add.php') == false) {
    $URL .= '&return=error0&step=1';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0&step=1';
        header("Location: {$URL}");
    } else {
        $step = null;
        if (isset($_GET['step'])) {
            $step = $_GET['step'];
        }
        if ($step != 1 and $step != 2) {
            $step = 1;
        }
        $gibbonBehaviourID = null;
        if (isset($_POST['gibbonBehaviourID'])) {
            $gibbonBehaviourID = $_POST['gibbonBehaviourID'];
        }

        //Step 1
        if ($step == 1 or $gibbonBehaviourID == null) {
            //Proceed!
            $gibbonPersonID = $_POST['gibbonPersonID'];
            $date = $_POST['date'];
            $type = $_POST['type'];
            $descriptor = null;
            if (isset($_POST['descriptor'])) {
                $descriptor = $_POST['descriptor'];
            }
            $level = null;
            if (isset($_POST['level'])) {
                $level = $_POST['level'];
            }
            $comment = $_POST['comment'];
            $followup = $_POST['followup'];

            if ($gibbonPersonID == '' or $date == '' or $type == '' or ($descriptor == '' and $enableDescriptors == 'Y')) {
                $URL .= '&return=error1&step=1';
                header("Location: {$URL}");
            } else {
                //Write to database
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID, 'date' => dateConvert($guid, $date), 'type' => $type, 'descriptor' => $descriptor, 'level' => $level, 'comment' => $comment, 'followup' => $followup, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                    $sql = 'INSERT INTO gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, descriptor=:descriptor, level=:level, comment=:comment, followup=:followup, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonSchoolYearID=:gibbonSchoolYearID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=erorr2&step=1';
                    header("Location: {$URL}");
                    exit();
                }

                //Last insert ID
                $AI = str_pad($connection2->lastInsertID(), 12, '0', STR_PAD_LEFT);

                $gibbonBehaviourID = $connection2->lastInsertID();

                //Attempt to add like on positive behaviour
                if ($type == 'Positive') {
                    $likeComment = '';
                    if ($descriptor != null) {
                        $likeComment .= $descriptor;
                    }
                    if ($descriptor != null and $comment != '') {
                        $likeComment .= ': ';
                    }
                    if ($comment != '') {
                        $likeComment .= $comment;
                    }
                    $return = setLike($connection2, 'Behaviour', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID', $gibbonBehaviourID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonID, 'Positive Behaviour', $likeComment);
                }

                //Attempt to notify tutor(s) on negative behaviour
                if ($type == 'Negative') {
                    try {
                        $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                        $sqlDetail = 'SELECT gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, surname, preferredName FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    } catch (PDOException $e) {
                        echo "<div class='error'>".$e->getMessage().'</div>';
                    }
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();
                        $name = formatName('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false);
                        $notificationText = sprintf(__($guid, 'Someone has created a negative behaviour record for your tutee, %1$s.'), $name);
                        if ($rowDetail['gibbonPersonIDTutor'] != null and $rowDetail['gibbonPersonIDTutor'] != $_SESSION[$guid]['gibbonPersonID']) {
                            setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor'], $notificationText, 'Behaviour', "/index.php?q=/modules/Students/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                        }
                        if ($rowDetail['gibbonPersonIDTutor2'] != null and $rowDetail['gibbonPersonIDTutor2'] != $_SESSION[$guid]['gibbonPersonID']) {
                            setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', "/index.php?q=/modules/Students/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                        }
                        if ($rowDetail['gibbonPersonIDTutor3'] != null and $rowDetail['gibbonPersonIDTutor3'] != $_SESSION[$guid]['gibbonPersonID']) {
                            setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', "/index.php?q=/modules/Students/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                        }
                    }
                }

                $URL .= "&return=success1&step=2&gibbonBehaviourID=$gibbonBehaviourID&editID=$AI";
                header("Location: {$URL}");
            }
        } elseif ($step == 2 and $gibbonBehaviourID != null) {
            //Proceed!
            $gibbonPersonID = $_POST['gibbonPersonID'];
            if ($_POST['gibbonPlannerEntryID'] == '') {
                $gibbonPlannerEntryID = null;
            } else {
                $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
            }
            $AI = '';
            if (isset($_GET['editID'])) {
                $AI = $_GET['editID'];
            }

            if ($gibbonPersonID == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                try {
                    $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID' => $gibbonBehaviourID, 'gibbonPersonID' => $gibbonPersonID);
                    $sql = "SELECT * FROM gibbonBehaviour JOIN gibbonPerson ON (gibbonBehaviour.gibbonPersonID=gibbonPerson.gibbonPersonID) JOIN gibbonStudentEnrolment ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) WHERE gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonBehaviour.gibbonPersonID=:gibbonPersonID";
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=warning0&step=2';
                    header("Location: {$URL}");
                    exit();
                }
                if ($result->rowCount() != 1) {
                    $URL .= '&return=error2&step=2';
                    header("Location: {$URL}");
                    exit();
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonBehaviourID' => $gibbonBehaviourID);
                        $sql = 'UPDATE gibbonBehaviour SET gibbonPlannerEntryID=:gibbonPlannerEntryID WHERE gibbonBehaviourID=:gibbonBehaviourID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=warning0&step=2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= "&return=success0&editID=$AI";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
