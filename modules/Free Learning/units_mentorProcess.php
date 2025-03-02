<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\System\SettingGateway;

require_once '../../gibbon.php';

require_once  './moduleFunctions.php';

$publicUnits = $container->get(SettingGateway::class)->getSettingByScope('Free Learning', 'publicUnits');

$highestAction = false;
$canManage = false;
$gibbonPersonID ='';
if ($session->exists('gibbonPersonID')) {
    $highestAction = getHighestGroupedAction($guid, '/modules/Free Learning/units_browse.php', $connection2);
    $gibbonPersonID = $session->get('gibbonPersonID');
    $canManage = false;
    if (isActionAccessible($guid, $connection2, '/modules/Free Learning/units_manage.php') and $highestAction == 'Browse Units_all') {
        $canManage = true;
    }
    if ($canManage and isset($_GET['gibbonPersonID'])) {
        $gibbonPersonID = $_GET['gibbonPersonID'];
    }
}

//Get params
$freeLearningUnitID = $_REQUEST['freeLearningUnitID'] ?? '';
$showInactive = ($canManage and isset($_GET['showInactive'])) ? $_GET['showInactive'] : 'N';
$gibbonDepartmentID = $_REQUEST['gibbonDepartmentID'] ?? '';
$difficulty = $_GET['difficulty'] ?? '';
$name = $_GET['name'] ?? '';
$view = $_GET['view'] ?? '';
if ($view != 'grid' and $view != 'map') {
    $view = 'list';
}
$response = $_REQUEST['response'] ?? null;
$reason = $_REQUEST['reason'] ?? null;
$reason = ($reason == "Other" && !empty($_REQUEST['details'])) ? $_REQUEST['details'] : $reason;
$freeLearningUnitStudentID = $_REQUEST['freeLearningUnitStudentID'] ?? null;
$confirmationKey = $_REQUEST['confirmationKey'] ?? null;

//Check to see if system settings are set from databases
if (@$session->get('systemSettingsSet') == false) {
    getSystemSettings($guid, $connection2);
}

//Set return URL
$URL = $session->get('absoluteURL')."/index.php?q=/modules/Free Learning/units_mentor.php&sidebar=true&gibbonDepartmentID=$gibbonDepartmentID&difficulty=$difficulty&name=$name&showInactive=$showInactive&gibbonPersonID=$gibbonPersonID&view=$view";

if ($response == '' or $freeLearningUnitStudentID == '' or $confirmationKey == '') {
    $URL .= '&return=error3';
    header("Location: {$URL}");
} else {
    //Check student & confirmation key
    try {
        $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID, 'confirmationKey' => $confirmationKey) ;
        $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit FROM freeLearningUnitStudent JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID AND confirmationKey=:confirmationKey AND status=\'Current - Pending\'';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }

    if ($result->rowCount()!=1) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit();
    }
    else {
        $row = $result->fetch() ;
        $unit = $row['unit'];
        $freeLearningUnitID = $row['freeLearningUnitID'];

		$notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
		$notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);

        if ($response == 'Y') { //If yes, updated student and collaborators based on confirmation key
            try {
                $data = array('confirmationKey' => $confirmationKey) ;
                $sql = 'UPDATE freeLearningUnitStudent SET status=\'Current\' WHERE confirmationKey=:confirmationKey';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= "&return=error2&freeLearningUnitID=$freeLearningUnitID";
                header("Location: {$URL}");
                exit();
            }

            //Notify student
            $notificationText = sprintf(__m('Your mentorship request for the Free Learning unit %1$s has been accepted.'), $unit);
            $notificationSender->addNotification($row['gibbonPersonIDStudent'], $notificationText, 'Free Learning', '/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$freeLearningUnitID.'&freeLearningUnitStudentID='.$freeLearningUnitStudentID.'&gibbonDepartmentID=&difficulty=&name=&sidebar=true&tab=1');
			$notificationSender->sendNotifications();

            //Return to thanks page
            $URL .= "&return=success1&freeLearningUnitID=$freeLearningUnitID";
            header("Location: {$URL}");
        }
        else { //If no, delete the records
            try {
                $data = array('confirmationKey' => $confirmationKey) ;
                $sql = 'DELETE FROM freeLearningUnitStudent WHERE confirmationKey=:confirmationKey';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= "&return=error2&freeLearningUnitID=$freeLearningUnitID";
                header("Location: {$URL}");
                exit();
            }

            //Notify student
            $notificationText = sprintf(__m('Your mentorship request for the Free Learning unit %1$s has been declined. Your enrolment has been deleted.'), $unit);
            $notificationText .= (!empty($reason)) ? " ".sprintf(__m('The following reason was given: %1$s.'), $reason) : '' ;
           	$notificationSender->addNotification($row['gibbonPersonIDStudent'], $notificationText, 'Free Learning', '/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$freeLearningUnitID.'&freeLearningUnitStudentID='.$freeLearningUnitStudentID.'&gibbonDepartmentID=&difficulty=&name=&sidebar=true&tab=1');
			$notificationSender->sendNotifications();

            //Return to thanks page
            $URL .= "&return=success0&freeLearningUnitID=$freeLearningUnitID";
            header("Location: {$URL}");
        }
    }
}
