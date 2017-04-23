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

$gibbonPersonID = $_POST['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/in_edit.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search'].'&source='.$_GET['source'].'&gibbonINDescriptorID='.$_GET['gibbonINDescriptorID'].'&gibbonAlertLevelID='.$_GET['gibbonAlertLevelID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'];

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Get action with highest precendence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false or ($highestAction != 'Individual Needs Records_viewContribute' and $highestAction != 'Individual Needs Records_viewEdit')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Check access to specified student
        try {
            $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonRollGroup.nameShort AS rollGroup, dateStart, dateEnd FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonRollGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) AND gibbonRollGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
            $result = $connection2->prepare($sql);
            $result->execute($data);
        } catch (PDOException $e) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        if ($result->rowCount() != 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $partialFail = false;

            if ($highestAction == 'Individual Needs Records_viewEdit') {
                //UPDATE STATUS
                $statuses = array();
                if (isset($_POST['status'])) {
                    $statuses = $_POST['status'];
                }
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'DELETE FROM gibbonINPersonDescriptor WHERE gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
                foreach ($statuses as $status) {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonINDescriptorID' => substr($status, 0, 3), 'gibbonAlertLevelID' => substr($status, 4, 3));
                        $sql = 'INSERT INTO gibbonINPersonDescriptor SET gibbonPersonID=:gibbonPersonID, gibbonINDescriptorID=:gibbonINDescriptorID, gibbonAlertLevelID=:gibbonAlertLevelID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }

                //UPDATE IEP
                $strategies = $_POST['strategies'];
                $targets = $_POST['targets'];
                $notes = $_POST['notes'];
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
                if ($result->rowCount() > 1) {
                    $partialFail = true;
                } else {
                    try {
                        $data = array('strategies' => $strategies, 'targets' => $targets, 'notes' => $notes, 'gibbonPersonID' => $gibbonPersonID);
                        if ($result->rowCount() == 1) {
                            $sql = 'UPDATE gibbonIN SET strategies=:strategies, targets=:targets, notes=:notes WHERE gibbonPersonID=:gibbonPersonID';
                        } else {
                            $sql = 'INSERT INTO gibbonIN SET gibbonPersonID=:gibbonPersonID, strategies=:strategies, targets=:targets, notes=:notes';
                        }
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            } elseif ($highestAction == 'Individual Needs Records_viewContribute') {
                //UPDATE IEP
                $strategies = $_POST['strategies'];
                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
                if ($result->rowCount() > 1) {
                    $partialFail = true;
                } else {
                    try {
                        $data = array('strategies' => $strategies, 'gibbonPersonID' => $gibbonPersonID);
                        if ($result->rowCount() == 1) {
                            $sql = 'UPDATE gibbonIN SET strategies=:strategies WHERE gibbonPersonID=:gibbonPersonID';
                        } else {
                            $sql = 'INSERT INTO gibbonIN SET gibbonPersonID=:gibbonPersonID, strategies=:strategies';
                        }
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }
            }

            //DEAL WITH OUTCOME
            if ($partialFail) {
                $URL .= '&return=warning1';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
