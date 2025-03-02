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


$_POST['address'] = '/modules/Free Learning/report_mentorshipOverview.php';

require_once '../../gibbon.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Free Learning/report_mentorshipOverview.php';

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/report_mentorshipOverview.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $action = $_REQUEST['action'] ?? '';
    $name = $_REQUEST['name'] ?? [];
    $freeLearningUnitStudentIDs = $_POST['freeLearningUnitStudentID'] ?? [];
    $partialFail = false;

    if (empty($action)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    if ($action == 'Approve') {
    	$notificationGateway = new \Gibbon\Domain\System\NotificationGateway($pdo);
    	
        foreach ($freeLearningUnitStudentIDs AS $freeLearningUnitStudentID) {
            try {
                $data = array('freeLearningUnitStudentID' => $freeLearningUnitStudentID) ;
                $sql = 'SELECT freeLearningUnitStudent.*, freeLearningUnit.name AS unit FROM freeLearningUnitStudent JOIN freeLearningUnit ON (freeLearningUnitStudent.freeLearningUnitID=freeLearningUnit.freeLearningUnitID) WHERE freeLearningUnitStudentID=:freeLearningUnitStudentID AND status=\'Current - Pending\'';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            if ($result->rowCount()!=1) {
                $partialFail = true;
            }
            else {
                $row = $result->fetch() ;
                $unit = $row['unit'];
                $freeLearningUnitID = $row['freeLearningUnitID'];
                $confirmationKey = $row['confirmationKey'];

                try {
                    $data = ["confirmationKey" => $confirmationKey, 'freeLearningUnitStudentID' => $freeLearningUnitStudentID] ;
                    $sql = 'UPDATE freeLearningUnitStudent SET status=\'Current\' WHERE confirmationKey=:confirmationKey AND freeLearningUnitStudentID=:freeLearningUnitStudentID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $URL .= "&return=error2&freeLearningUnitID=$freeLearningUnitID";
                    header("Location: {$URL}");
                    exit();
                }

                //Notify student
				$notificationSender = new \Gibbon\Comms\NotificationSender($notificationGateway, $session);
				$notificationText = sprintf(__m('Your mentorship request for the Free Learning unit %1$s has been accepted.'), $unit);
				$notificationSender->addNotification($row['gibbonPersonIDStudent'], $notificationText, 'Free Learning', '/index.php?q=/modules/Free Learning/units_browse_details.php&freeLearningUnitID='.$freeLearningUnitID.'&freeLearningUnitStudentID='.$freeLearningUnitStudentID.'&gibbonDepartmentID=&difficulty=&name=&sidebar=true&tab=1');
				$notificationSender->sendNotifications();
            }
        }
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");

}
