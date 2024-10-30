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

use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['targets' => 'HTML', 'strategies' => 'HTML', 'notes' => 'HTML']);

$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$address = $_POST['address'] ?? '';
$search = $_GET['search'] ?? '';
$source = $_GET['source'] ?? '';
$gibbonINDescriptorID = $_GET['gibbonINDescriptorID'] ?? '';
$gibbonAlertLevelID = $_GET['gibbonAlertLevelID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/in_edit.php&gibbonPersonID=$gibbonPersonID&search=$search&source=$source&gibbonINDescriptorID=$gibbonINDescriptorID&gibbonAlertLevelID=$gibbonAlertLevelID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID";

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
            $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
            $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonStudentEnrolmentID, surname, preferredName, gibbonYearGroup.nameShort AS yearGroup, gibbonFormGroup.nameShort AS formGroup, dateStart, dateEnd, gibbonYearGroup.gibbonYearGroupID FROM gibbonPerson, gibbonStudentEnrolment, gibbonYearGroup, gibbonFormGroup WHERE (gibbonPerson.gibbonPersonID=gibbonStudentEnrolment.gibbonPersonID) AND (gibbonStudentEnrolment.gibbonYearGroupID=gibbonYearGroup.gibbonYearGroupID) AND (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) AND gibbonFormGroup.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPerson.gibbonPersonID=:gibbonPersonID AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
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
            $row = $result->fetch();

            if ($highestAction == 'Individual Needs Records_viewEdit') {
                //UPDATE STATUS
                $statuses = array();
                if (isset($_POST['status'])) {
                    $statuses = $_POST['status'] ?? '';
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
                $strategies = $_POST['strategies'] ?? '';
                $targets = $_POST['targets'] ?? '';
                $notes = $_POST['notes'] ?? '';

                $customRequireFail = false;
                $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Individual Needs', [], $customRequireFail);

                try {
                    $data = array('gibbonPersonID' => $gibbonPersonID);
                    $sql = 'SELECT * FROM gibbonIN WHERE gibbonPersonID=:gibbonPersonID';
                    $result = $connection2->prepare($sql);
                    $result->execute($data);
                } catch (PDOException $e) {
                    $partialFail = true;
                }
                if ($result->rowCount() > 1 || $customRequireFail) {
                    $partialFail = true;
                } else {
                    try {
                        $data = array('strategies' => $strategies, 'targets' => $targets, 'notes' => $notes, 'fields' => $fields, 'gibbonPersonID' => $gibbonPersonID);
                        if ($result->rowCount() == 1) {
                            $sql = 'UPDATE gibbonIN SET strategies=:strategies, targets=:targets, notes=:notes, fields=:fields WHERE gibbonPersonID=:gibbonPersonID';
                        } else {
                            $sql = 'INSERT INTO gibbonIN SET gibbonPersonID=:gibbonPersonID, strategies=:strategies, targets=:targets, notes=:notes, fields=:fields';
                        }
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $partialFail = true;
                    }
                }

                //Scan through assistants
                $staff = array();
                if (isset($_POST['staff'])) {
                    $staff = $_POST['staff'] ?? [];
                }
                $comment = $_POST['comment'] ?? '';
                if (count($staff) > 0) {
                    foreach ($staff as $t) {
                        //Check to see if person is already registered as an assistant
                        try {
                            $dataGuest = array('gibbonPersonIDAssistant' => $t, 'gibbonPersonIDStudent' => $gibbonPersonID);
                            $sqlGuest = 'SELECT * FROM gibbonINAssistant WHERE gibbonPersonIDAssistant=:gibbonPersonIDAssistant AND gibbonPersonIDStudent=:gibbonPersonIDStudent';
                            $resultGuest = $connection2->prepare($sqlGuest);
                            $resultGuest->execute($dataGuest);
                        } catch (PDOException $e) {
                            $partialFail = true;
                        }
                        if ($resultGuest->rowCount() == 0) {
                            try {
                                $data = array('gibbonPersonIDAssistant' => $t, 'gibbonPersonIDStudent' => $gibbonPersonID, 'comment' => $comment);
                                $sql = 'INSERT INTO gibbonINAssistant SET gibbonPersonIDAssistant=:gibbonPersonIDAssistant, gibbonPersonIDStudent=:gibbonPersonIDStudent, comment=:comment';
                                $result = $connection2->prepare($sql);
                                $result->execute($data);
                            } catch (PDOException $e) {
                                $partialFail = true;
                            }
                        }
                    }
                }
            } elseif ($highestAction == 'Individual Needs Records_viewContribute') {
                //UPDATE IEP
                $strategies = $_POST['strategies'] ?? '';
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

            if (!$partialFail) {
                // Raise a new notification event
                $event = new NotificationEvent('Individual Needs', 'Updated Individual Needs');

                $staffName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);
                $studentName = Format::name('', $row['preferredName'], $row['surname'], 'Student', false);
                $actionLink = "/index.php?q=/modules/Individual Needs/in_edit.php&gibbonPersonID=$gibbonPersonID&search=";

                $event->setNotificationText(sprintf(__('%1$s has updated the individual needs record for %2$s.'), $staffName, $studentName));
                $event->setActionLink($actionLink);

                $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                $event->addScope('gibbonYearGroupID', $row['gibbonYearGroupID']);

                $event->sendNotifications($pdo, $session);
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
