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

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$settingGateway = $container->get(SettingGateway::class);

$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

$gibbonBehaviourID = $_GET['gibbonBehaviourID'] ?? '';
$address = $_POST['address'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$type = $_GET['type'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/behaviour_manage_edit.php&gibbonBehaviourID=$gibbonBehaviourID&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type";

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if gibbonBehaviourID specified
        if ($gibbonBehaviourID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                if ($highestAction == 'Manage Behaviour Records_all') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonBehaviourID' => $gibbonBehaviourID);
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID ORDER BY date DESC';
                } elseif ($highestAction == 'Manage Behaviour Records_my') {
                    $data = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonBehaviourID' => $gibbonBehaviourID, 'gibbonPersonID' => $session->get('gibbonPersonID'));
                    $sql = 'SELECT gibbonBehaviour.*, student.surname AS surnameStudent, student.preferredName AS preferredNameStudent, creator.surname AS surnameCreator, creator.preferredName AS preferredNameCreator, creator.title FROM gibbonBehaviour JOIN gibbonPerson AS student ON (gibbonBehaviour.gibbonPersonID=student.gibbonPersonID) JOIN gibbonPerson AS creator ON (gibbonBehaviour.gibbonPersonIDCreator=creator.gibbonPersonID) WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonBehaviourID=:gibbonBehaviourID AND gibbonPersonIDCreator=:gibbonPersonID ORDER BY date DESC';
                }
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
                $behaviourRecord = $result->fetch();

                $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
                $date = $_POST['date'] ?? '';
                $type = $_POST['type'] ?? '';
                $descriptor = $_POST['descriptor'] ?? null;
                $level = $_POST['level'] ?? null;
                $comment = $_POST['comment'] ?? '';
                $followup = $_POST['followup'] ?? '';
                $gibbonPlannerEntryID = !empty($_POST['gibbonPlannerEntryID']) ? $_POST['gibbonPlannerEntryID'] : null;

                $customRequireFail = false;
                $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Behaviour', [], $customRequireFail);

                if ($gibbonPersonID == '' or $date == '' or $type == '' or ($descriptor == '' and $enableDescriptors == 'Y') || $customRequireFail) {
                    $URL .= '&return=error1';
                    header("Location: {$URL}");
                } else {
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'date' => Format::dateConvert($date), 'type' => $type, 'descriptor' => $descriptor, 'level' => $level, 'comment' => $comment, 'followup' => $followup, 'fields' => $fields, 'gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonBehaviourID' => $gibbonBehaviourID);
                        $sql = 'UPDATE gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, descriptor=:descriptor, level=:level, comment=:comment, followup=:followup, fields=:fields, gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonSchoolYearID=:gibbonSchoolYearID WHERE gibbonBehaviourID=:gibbonBehaviourID';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    // Send a notification to student's tutors and anyone subscribed to the notification event
                    $studentGateway = $container->get(StudentGateway::class);
                    $formGroupGateway = $container->get(FormGroupGateway::class);
                    $inAssistantGateway = $container->get(INAssistantGateway::class);

                    // Send behaviour notifications
                    $student = $studentGateway->selectActiveStudentByPerson($session->get('gibbonSchoolYearID'), $gibbonPersonID)->fetch();
                    if (!empty($student)) {
                        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false);
                        $editorName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false);
                        $actionLink = "/index.php?q=/modules/Behaviour/behaviour_manage_edit.php&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=&gibbonYearGroupID=&type=$type&gibbonBehaviourID=$gibbonBehaviourID";

                        // Raise a new notification event
                        $event = new NotificationEvent('Behaviour', 'Updated Behaviour Record');

                        $event->setNotificationText(sprintf(__('A %1$s behaviour record for %2$s has been updated by %3$s.'), strtolower($type), $studentName, $editorName));
                        $event->setActionLink($actionLink);

                        $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                        $event->addScope('gibbonYearGroupID', $student['gibbonYearGroupID']);

                        // Add the person who created the behaviour record, if edited by someone else
                        if ($behaviourRecord['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
                            $event->addRecipient($behaviourRecord['gibbonPersonIDCreator']);
                        }

                        // Add direct notifications to form group tutors
                        if ($settingGateway->getSettingByScope('Behaviour', 'notifyTutors') == 'Y') {
                            $tutors = $formGroupGateway->selectTutorsByFormGroup($student['gibbonFormGroupID'])->fetchAll();
                            foreach ($tutors as $tutor) {
                                $event->addRecipient($tutor['gibbonPersonID']);
                            }
                        }

                        // Add notifications for Educational Assistants
                        if ($settingGateway->getSettingByScope('Behaviour', 'notifyEducationalAssistants') == 'Y') {
                            $educationalAssistants = $inAssistantGateway->selectINAssistantsByStudent($gibbonPersonID)->fetchAll();
                            foreach ($educationalAssistants as $ea) {
                                $event->addRecipient($ea['gibbonPersonID']);
                            }
                        }

                        $event->sendNotificationsAsBcc($pdo, $gibbon->session);
                    }

                    $URL .= '&return=success0';
                    header("Location: {$URL}");
                }
            }
        }
    }
}
