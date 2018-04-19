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

use Gibbon\Comms\NotificationEvent;

include '../../functions.php';
include '../../config.php';

//Module includes
include $_SESSION[$guid]['absolutePath'].'/modules/Activities/moduleFunctions.php';

$mode = $_POST['mode'];
$gibbonActivityID = $_POST['gibbonActivityID'];
$gibbonPersonID = $_POST['gibbonPersonID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_view_register.php&gibbonActivityID=$gibbonActivityID&gibbonPersonID=$gibbonPersonID&mode=$mode&search=".$_GET['search'];
$URLSuccess = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_view.php&gibbonPersonID=$gibbonPersonID&search=".$_GET['search'];

$gibbonModuleID = getModuleIDFromName($connection2, 'Activities') ;

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_view_register.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Activities/activities_view_register.php', $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Get current role category
        $roleCategory = getRoleCategory($_SESSION[$guid]['gibbonRoleIDCurrent'], $connection2);

        //Check access controls
        $access = getSettingByScope($connection2, 'Activities', 'access');

        if ($access != 'Register') {
            //Fail0
            $URL .= '&return=error0';
            header("Location: {$URL}");
        } else {
            //Proceed!
            //Check if school year specified
            if ($gibbonActivityID == '' or $gibbonPersonID == '') {
                $URL .= '&return=error1';
                header("Location: {$URL}");
            } else {
                $today = date('Y-m-d');
                //Should we show date as term or date?
                $dateType = getSettingByScope($connection2, 'Activities', 'dateType');

                try {
                    if ($dateType != 'Date') {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID);
                        $sql = "SELECT DISTINCT gibbonActivity.*, gibbonStudentEnrolment.gibbonYearGroupID, gibbonPerson.surname, gibbonPerson.preferredName FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND NOT gibbonSchoolYearTermIDList='' AND active='Y' AND registration='Y'";
                    } else {
                        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID, 'listingStart' => $today, 'listingEnd' => $today);
                        $sql = "SELECT DISTINCT gibbonActivity.*, gibbonStudentEnrolment.gibbonYearGroupID, gibbonPerson.surname, gibbonPerson.preferredName FROM gibbonActivity JOIN gibbonStudentEnrolment ON (gibbonActivity.gibbonYearGroupIDList LIKE concat( '%', gibbonStudentEnrolment.gibbonYearGroupID, '%' )) JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) WHERE gibbonActivity.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID AND gibbonActivityID=:gibbonActivityID AND listingStart<=:listingStart AND listingEnd>=:listingEnd AND active='Y' AND registration='Y'";
                    }
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                if ($result->rowCount() < 1) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                } else {
                    $row = $result->fetch();

                    // Grab organizer info for notifications
                    try {
                        $dataStaff = array('gibbonActivityID' => $gibbonActivityID);
                        $sqlStaff = "SELECT gibbonPersonID FROM gibbonActivityStaff WHERE gibbonActivityID=:gibbonActivityID AND role='Organiser'";
                        $resultStaff = $connection2->prepare($sqlStaff);
                        $resultStaff->execute($dataStaff);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $gibbonActivityStaffIDs = ($resultStaff->rowCount() > 0)? $resultStaff->fetchAll(\PDO::FETCH_COLUMN, 0) : array();

                    //Check for existing registration
                    try {
                        $dataReg = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID);
                        $sqlReg = 'SELECT gibbonActivityStudentID, status FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                        $resultReg = $connection2->prepare($sqlReg);
                        $resultReg->execute($dataReg);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    if ($mode == 'register') {

                        if ($resultReg->rowCount() > 0) {
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        } else {
                            //Validate Inputs
                            $backup = getSettingByScope($connection2, 'Activities', 'backupChoice');
                            $gibbonActivityIDBackup = null;
                            if ($backup == 'N') {
                                $gibbonActivityIDBackup = null;
                            } elseif ($backup == 'Y') {
                                $gibbonActivityIDBackup = $_POST['gibbonActivityIDBackup'];
                            }

                            if ($backup == 'Y' and $gibbonActivityIDBackup == '') {
                                $URL .= '&error=error1';
                                header("Location: {$URL}");
                            } else {
                                $status = 'Not accepted';
                                $enrolment = getSettingByScope($connection2, 'Activities', 'enrolmentType');

                                //Lock the activityStudent database table
                                try {
                                    $sql = 'LOCK TABLES gibbonActivityStudent WRITE, gibbonPerson WRITE, gibbonLog WRITE';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

                                if ($enrolment == 'Selection') {
                                    $status = 'Pending';
                                } else {
                                    //Check number of people registered for this activity (if we ignore status it stops people jumping the queue when someone unregisters)
                                    try {
                                        $dataNumberRegistered = array('gibbonActivityID' => $gibbonActivityID);
                                        $sqlNumberRegistered = "SELECT * FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityID=:gibbonActivityID";
                                        $resultNumberRegistered = $connection2->prepare($sqlNumberRegistered);
                                        $resultNumberRegistered->execute($dataNumberRegistered);
                                    } catch (PDOException $e) {
                                        echo "<div class='error'>".$e->getMessage().'</div>';
                                    }

                                    //If activity is full...
                                    if ($resultNumberRegistered->rowCount() >= $row['maxParticipants']) {
                                        $status = 'Waiting List';
                                    } else {
                                        $status = 'Accepted';
                                    }
                                }

                                //Write to database
                                try {
                                    $data = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID, 'status' => $status, 'timestamp' => date('Y-m-d H:i:s', time()), 'gibbonActivityIDBackup' => $gibbonActivityIDBackup);
                                    $sql = 'INSERT INTO gibbonActivityStudent SET gibbonActivityID=:gibbonActivityID, gibbonPersonID=:gibbonPersonID, status=:status, timestamp=:timestamp, gibbonActivityIDBackup=:gibbonActivityIDBackup';
                                    $result = $connection2->prepare($sql);
                                    $result->execute($data);
                                } catch (PDOException $e) {
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

                                //Set log
                                setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], $gibbonModuleID, $_SESSION[$guid]['gibbonPersonID'], 'Activities - Student Registered', array('gibbonPersonIDStudent' => $gibbonPersonID));

                                //Unlock locked database tables
                                try {
                                    $sql = 'UNLOCK TABLES';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                }

                                // Get the start and end date of the activity, depending on which dateType we're using
                                $activityTimespan = getActivityTimespan($connection2, $gibbonActivityID, $row['gibbonSchoolYearTermIDList']);

                                // Is the activity running right now?
                                if (time() >= $activityTimespan['start'] && time() <= $activityTimespan['end']) {
                                    // Raise a new notification event
                                    $event = new NotificationEvent('Activities', 'New Activity Registration');

                                    $studentName = formatName('', $row['preferredName'], $row['surname'], 'Student', false);
                                    $notificationText = sprintf(__('%1$s has registered for the activity %2$s (%3$s)'), $studentName, $row['name'], $status);

                                    $event->setNotificationText($notificationText);
                                    $event->setActionLink('/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$gibbonActivityID.'&search=&gibbonSchoolYearTermID=');

                                    $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                                    $event->addScope('gibbonYearGroupID', $row['gibbonYearGroupID']);

                                    foreach ($gibbonActivityStaffIDs as $gibbonPersonIDStaff) {
                                        $event->addRecipient($gibbonPersonIDStaff);
                                    }

                                    $event->sendNotifications($pdo, $gibbon->session);
                                }

                                if ($status == 'Waiting List') {
                                    $URLSuccess = $URLSuccess.'&return=success2';
                                    header("Location: {$URLSuccess}");
                                } else {
                                    $URLSuccess = $URLSuccess.'&return=success0';
                                    header("Location: {$URLSuccess}");
                                }
                            }
                        }
                    } elseif ($mode == 'unregister') {

                        if ($resultReg->rowCount() < 1) {
                            $URL .= '&return=error3';
                            header("Location: {$URL}");
                        } else {
                            //Write to database
                            try {
                                $data = array('gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID);
                                $sql = 'DELETE FROM gibbonActivityStudent WHERE gibbonActivityID=:gibbonActivityID AND gibbonPersonID=:gibbonPersonID';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $URL .= '&return=error2';
                                header("Location: {$URL}");
                                exit();
                            }

                            //Set log
                            setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], $gibbonModuleID, $_SESSION[$guid]['gibbonPersonID'], 'Activities - Student Withdrawn', array('gibbonPersonIDStudent' => $gibbonPersonID));

                            $reg = $resultReg->fetch();

                            // Raise a new notification event
                            if ($reg['status'] == 'Accepted') {
                                // Get the start and end date of the activity, depending on which dateType we're using
                                $activityTimespan = getActivityTimespan($connection2, $gibbonActivityID, $row['gibbonSchoolYearTermIDList']);

                                // Is the activity running right now?
                                if (time() >= $activityTimespan['start'] && time() <= $activityTimespan['end']) {
                                    $event = new NotificationEvent('Activities', 'Student Withdrawn');

                                    $studentName = formatName('', $row['preferredName'], $row['surname'], 'Student', false);
                                    $notificationText = sprintf(__('%1$s has withdrawn from the activity %2$s'), $studentName, $row['name']);

                                    $event->setNotificationText($notificationText);
                                    $event->setActionLink('/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$gibbonActivityID.'&search=&gibbonSchoolYearTermID=');

                                    $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                                    $event->addScope('gibbonYearGroupID', $row['gibbonYearGroupID']);

                                    foreach ($gibbonActivityStaffIDs as $gibbonPersonIDStaff) {
                                        $event->addRecipient($gibbonPersonIDStaff);
                                    }

                                    $event->sendNotifications($pdo, $gibbon->session);
                                }
                            }

                            //Bump up any waiting in competitive selection, to fill spaces available
                            $enrolment = getSettingByScope($connection2, 'Activities', 'enrolmentType');
                            if ($enrolment == 'Competitive') {
                                //Check to see who is registering in system
                                $studentRegistration = false;
                                $parentRegistration = false ;
                                try {
                                    $dataAccess = array();
                                    $sqlAccess = "SELECT
                                            gibbonAction.name, gibbonRole.category
                                        FROM gibbonAction
                                            JOIN gibbonPermission ON (gibbonPermission.gibbonActionID=gibbonAction.gibbonActionID)
                                            JOIN gibbonRole ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID)
                                        WHERE
                                            gibbonAction.name IN ('View Activities_studentRegister', 'View Activities_studentRegisterByParent')
                                            AND gibbonRole.category IN ('Parent','Student')";
                                    $resultAccess = $connection2->prepare($sqlAccess);
                                    $resultAccess->execute($dataAccess);
                                } catch (PDOException $e) {}
                                while ($rowAccess = $resultAccess->fetch()) {
                                    if ($rowAccess['name'] == 'View Activities_studentRegister' && $rowAccess['category'] == 'Student') {
                                        $studentRegistration = true;
                                    }
                                    else if ($rowAccess['name'] == 'View Activities_studentRegisterByParent' && $rowAccess['category'] == 'Parent') {
                                        $parentRegistration = true;
                                    }
                                }

                                //Lock the activityStudent database table
                                try {
                                    $sql = 'LOCK TABLES gibbonActivityStudent WRITE, gibbonActivity READ, gibbonPerson READ, gibbonNotificationEvent READ, gibbonModule READ, gibbonAction READ, gibbonPermission READ, gibbonNotificationListener READ, gibbonNotification WRITE, gibbonFamilyChild READ, gibbonFamily READ, gibbonFamilyAdult READ, gibbonLog WRITE';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                    $URL .= '&return=error2';
                                    header("Location: {$URL}");
                                    exit();
                                }

                                //Count spaces
                                try {
                                    $dataNumberRegistered = array('gibbonActivityID' => $gibbonActivityID);
                                    $sqlNumberRegistered = "SELECT * FROM gibbonActivityStudent JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonPerson.status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND gibbonActivityID=:gibbonActivityID AND gibbonActivityStudent.status='Accepted'";
                                    $resultNumberRegistered = $connection2->prepare($sqlNumberRegistered);
                                    $resultNumberRegistered->execute($dataNumberRegistered);
                                } catch (PDOException $e) {
                                }

                                //If activity is not full...
                                $spaces = $row['maxParticipants'] - $resultNumberRegistered->rowCount();
                                if ($spaces > 0) {
                                    //Get top of waiting list
                                    try {
                                        $dataBumps = array('gibbonActivityID' => $gibbonActivityID);
                                        $sqlBumps = "SELECT gibbonActivityStudentID, name, gibbonPerson.gibbonPersonID, surname, preferredName
                                            FROM gibbonActivityStudent
                                            JOIN gibbonActivity ON (gibbonActivityStudent.gibbonActivityID=gibbonActivity.gibbonActivityID)
                                            JOIN gibbonPerson ON (gibbonActivityStudent.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                        WHERE gibbonPerson.status='Full'
                                            AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."')
                                            AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."')
                                            AND gibbonActivityStudent.gibbonActivityID=:gibbonActivityID
                                            AND gibbonActivityStudent.status='Waiting List'
                                        ORDER BY timestamp ASC LIMIT 0, $spaces";
                                        $resultBumps = $connection2->prepare($sqlBumps);
                                        $resultBumps->execute($dataBumps);
                                    } catch (PDOException $e) { }

                                    //Bump students up
                                    while ($rowBumps = $resultBumps->fetch()) {
                                        try {
                                            $dataBump = array('gibbonActivityStudentID' => $rowBumps['gibbonActivityStudentID']);
                                            $sqlBump = "UPDATE gibbonActivityStudent SET status='Accepted' WHERE gibbonActivityStudentID=:gibbonActivityStudentID";
                                            $resultBump = $connection2->prepare($sqlBump);
                                            $resultBump->execute($dataBump);
                                        } catch (PDOException $e) {
                                        }

                                        //Set log
                                        setLog($connection2, $_SESSION[$guid]['gibbonSchoolYearIDCurrent'], $gibbonModuleID, $_SESSION[$guid]['gibbonPersonID'], 'Activities - Student Bump', array('gibbonPersonIDStudent' => $rowBumps['gibbonPersonID']));

                                        //Raise notifications
                                        $event = new NotificationEvent('Activities', 'Student Bumped');

                                        $studentName = formatName('', $rowBumps['preferredName'], $rowBumps['surname'], 'Student', false);
                                        $notificationText = sprintf(__('%1$s has been bumped into activity %2$s'), $studentName, $rowBumps['name']);

                                        $event->setNotificationText($notificationText);
                                        $event->setActionLink('/index.php?q=/modules/Activities/activities_view.php&gibbonPersonID='.$rowBumps['gibbonPersonID']);

                                        //DO WE WANT TO ADD STUDENT/PARENTS HERE, BASED ON ACCESS?
                                        if ($studentRegistration) { //Notify student
                                            $event->addRecipient($rowBumps['gibbonPersonID']);
                                        }
                                        if ($parentRegistration) { //Notify contact priority 1 parents in associated families
                                            try {
                                                $dataAdult = array('gibbonPersonID' => $rowBumps['gibbonPersonID']);
                                                $sqlAdult = "
                                                    SELECT
                                                        gibbonFamilyAdult.gibbonPersonID
                                                    FROM gibbonFamilyChild
                                                        JOIN gibbonFamily ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                                        JOIN gibbonFamilyAdult ON (gibbonFamilyAdult.gibbonFamilyID=gibbonFamily.gibbonFamilyID)
                                                        JOIN gibbonPerson ON (gibbonFamilyAdult.gibbonPersonID=gibbonPerson.gibbonPersonID)
                                                    WHERE
                                                        gibbonFamilyChild.gibbonPersonID=:gibbonPersonID
                                                        AND childDataAccess='Y'
                                                        AND contactPriority=1
                                                        AND gibbonPerson.status='Full'";
                                                $resultAdult = $connection2->prepare($sqlAdult);
                                                $resultAdult->execute($dataAdult);
                                            } catch (PDOException $e) { }
                                            while ($rowAdult = $resultAdult->fetch()) {
                                                $event->addRecipient($rowAdult['gibbonPersonID']);
                                            }
                                        }

                                        $event->sendNotifications($pdo, $gibbon->session);
                                    }
                                }
                                //Unlock locked database tables
                                try {
                                    $sql = 'UNLOCK TABLES';
                                    $result = $connection2->query($sql);
                                } catch (PDOException $e) {
                                }
                            }

                            $URLSuccess = $URLSuccess.'&return=success1';
                            header("Location: {$URLSuccess}");
                        }
                    }
                }
            }
        }
    }
}
