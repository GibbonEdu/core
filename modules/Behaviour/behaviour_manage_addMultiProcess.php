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

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/behaviour_manage_addMulti.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'];

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (isset($_POST['gibbonPersonIDMulti'])) {
        $gibbonPersonIDMulti = $_POST['gibbonPersonIDMulti'];
    } else {
        $gibbonPersonIDMulti = null;
    }
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

    if (is_null($gibbonPersonIDMulti) == true or $date == '' or $type == '' or ($descriptor == '' and $enableDescriptors == 'Y')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        //Prep like comment on positive behaviour
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
        }

        foreach ($gibbonPersonIDMulti as $gibbonPersonID) {
            //Write to database
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'date' => dateConvert($guid, $date), 'type' => $type, 'descriptor' => $descriptor, 'level' => $level, 'comment' => $comment, 'followup' => $followup, 'gibbonPersonIDCreator' => $_SESSION[$guid]['gibbonPersonID'], 'gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
                $sql = 'INSERT INTO gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, descriptor=:descriptor, level=:level, comment=:comment, followup=:followup, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonSchoolYearID=:gibbonSchoolYearID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            $gibbonBehaviourID = $connection2->lastInsertID();

            //Attempt to add like on positive behaviour
            if ($type == 'Positive') {
                $return = setLike($connection2, 'Behaviour', $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonBehaviourID', $gibbonBehaviourID, $_SESSION[$guid]['gibbonPersonID'], $gibbonPersonID, 'Positive Behaviour', $likeComment);
            }

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
                        setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor'], $notificationText, 'Behaviour', "/index.php?q=/modules/Behaviour/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                    }
                    if ($rowDetail['gibbonPersonIDTutor2'] != null and $rowDetail['gibbonPersonIDTutor2'] != $_SESSION[$guid]['gibbonPersonID']) {
                        setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', "/index.php?q=/modules/Behaviour/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                    }
                    if ($rowDetail['gibbonPersonIDTutor3'] != null and $rowDetail['gibbonPersonIDTutor3'] != $_SESSION[$guid]['gibbonPersonID']) {
                        setNotification($connection2, $guid, $rowDetail['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', "/index.php?q=/modules/Behaviour/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=");
                    }
                }
            }
        }

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
