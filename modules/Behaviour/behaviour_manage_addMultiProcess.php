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

use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\IndividualNeeds\INGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Domain\Behaviour\BehaviourFollowUpGateway;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$settingGateway = $container->get(SettingGateway::class);
$enableDescriptors = $settingGateway->getSettingByScope('Behaviour', 'enableDescriptors');
$enableLevels = $settingGateway->getSettingByScope('Behaviour', 'enableLevels');

$address = $_POST['address'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$type = $_GET['type'] ?? '';

//generate a unique and random incidentID for multiple behavior records
$salt = getSalt();
$gibbonMultiIncidentID = hash('sha256', $salt);

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($address)."/behaviour_manage_add.php&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID&type=$type";

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $gibbonPersonIDMulti = $_POST['gibbonPersonIDMulti'] ?? [];
    $date = $_POST['date'] ?? '';
    $type = $_POST['type'] ?? '';
    $descriptor = $_POST['descriptor'] ?? null;
    $level = $_POST['level'] ?? null;
    $comment = $_POST['comment'] ?? '';
    $followUp = $_POST['followUp'] ?? '';
    $copyToNotes = $_POST['copyToNotes'] ?? null;

    $customRequireFail = false;
    $fields = $container->get(CustomFieldHandler::class)->getFieldDataFromPOST('Behaviour', [], $customRequireFail);

    if (empty($gibbonPersonIDMulti) or $date == '' or $type == '' or ($descriptor == '' and $enableDescriptors == 'Y') || $customRequireFail) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $partialFail = false;

        // Initialize the notification sender & gateway objects
        $notificationGateway = $container->get(NotificationGateway::class);
        $notificationSender = $container->get(NotificationSender::class);

        foreach ($gibbonPersonIDMulti as $gibbonPersonID) {
            //Write to database
            try {
                $data = array('gibbonPersonID' => $gibbonPersonID, 'date' => Format::dateConvert($date),'gibbonMultiIncidentID' => $gibbonMultiIncidentID, 'type' => $type, 'descriptor' => $descriptor, 'level' => $level, 'comment' => $comment, 'fields' => $fields, 'gibbonPersonIDCreator' => $session->get('gibbonPersonID'), 'gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'));
                $sql = 'INSERT INTO gibbonBehaviour SET gibbonPersonID=:gibbonPersonID, date=:date, type=:type, gibbonMultiIncidentID=:gibbonMultiIncidentID, descriptor=:descriptor, level=:level, comment=:comment, fields=:fields, gibbonPersonIDCreator=:gibbonPersonIDCreator, gibbonSchoolYearID=:gibbonSchoolYearID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $partialFail = true;
            }

            $gibbonBehaviourID = $connection2->lastInsertID();

            // Add a follow up log
            if (!empty($followUp)) {
                $behaviourFollowUpGateway = $container->get(BehaviourFollowUpGateway::class);
                $data = [
                            'gibbonBehaviourID' => $gibbonBehaviourID,
                            'gibbonPersonID' => $session->get('gibbonPersonID'),
                            'followUp' => $followUp,
                        ];

                $inserted = $behaviourFollowUpGateway->insert($data);

                if (!$inserted) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit;
                }
            } 

            // Attempt to notify tutor(s) and EA(s) of negative behaviour

            $dataDetail = array('gibbonSchoolYearID' => $session->get('gibbonSchoolYearID'), 'gibbonPersonID' => $gibbonPersonID);
            $sqlDetail = 'SELECT gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, surname, preferredName, gibbonStudentEnrolment.gibbonYearGroupID FROM gibbonFormGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID) JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID';
            $resultDetail = $connection2->prepare($sqlDetail);
            $resultDetail->execute($dataDetail);
        if ($resultDetail->rowCount() == 1) {
            $rowDetail = $resultDetail->fetch();

            $studentName = Format::name('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false);
            $staffName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);
            $actionLink = "/index.php?q=/modules/Behaviour/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=";

            // Raise a new notification event
            $event = new NotificationEvent('Behaviour', $type == 'Positive' ? 'New Positive Record' : 'New Negative Record');
            $event->setNotificationText(__('{person} has created a {type} behaviour record for {student}.', [
                'type' => strtolower($type),
                'person' => $staffName,
                'student' => $studentName,
            ]));

            $event->setActionLink($actionLink);
            $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
            $event->addScope('gibbonYearGroupID', $rowDetail['gibbonYearGroupID']);

            // Add notifications for Educational Assistants
            if ($settingGateway->getSettingByScope('Behaviour', 'notifyEducationalAssistants') == 'Y') {
                $educationalAssistants = $container->get(INAssistantGateway::class)->selectINAssistantsByStudent($gibbonPersonID)->fetchAll();
                foreach ($educationalAssistants as $ea) {
                    $event->addRecipient($ea['gibbonPersonID']);
                }
            }

            // Add event listeners to the notification sender
            $event->pushNotifications($notificationGateway, $notificationSender);

            // Add direct notifications to form group tutors
            if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
                if ($settingGateway->getSettingByScope('Behaviour', 'notifyTutors') == 'Y') {

                    $notificationText = __('{person} has created a {type} behaviour record for your tutee, {student}.', [
                        'type' => strtolower($type),
                        'person' => $staffName,
                        'student' => $studentName,
                    ]);

                    if ($rowDetail['gibbonPersonIDTutor'] != null and $rowDetail['gibbonPersonIDTutor'] != $session->get('gibbonPersonID')) {
                        $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor'], $notificationText, 'Behaviour', $actionLink);
                    }
                    if ($rowDetail['gibbonPersonIDTutor2'] != null and $rowDetail['gibbonPersonIDTutor2'] != $session->get('gibbonPersonID')) {
                        $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', $actionLink);
                    }
                    if ($rowDetail['gibbonPersonIDTutor3'] != null and $rowDetail['gibbonPersonIDTutor3'] != $session->get('gibbonPersonID')) {
                        $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', $actionLink);
                    }
                }
            }

            // Check if this is an IN student
            $studentIN = $container->get(INGateway::class)->selectIndividualNeedsDescriptorsByStudent($gibbonPersonID)->fetchAll();
            if (!empty($studentIN)) {
                // Raise a notification event for IN students
                $eventIN = new NotificationEvent('Behaviour', 'Behaviour Record for IN Student');
                $eventIN->setNotificationText(__('{person} has created a {type} behaviour record for {student}.', [
                    'type' => strtolower($type),
                    'person' => $staffName, 
                    'student' => $studentName,
                ]));
                
                $eventIN->setActionLink($actionLink);
                $eventIN->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                $eventIN->addScope('gibbonYearGroupID', $rowDetail['gibbonYearGroupID']);

                // Add event listeners to the notification sender
                $eventIN->pushNotifications($notificationGateway, $notificationSender);
            }
        }

            if ($copyToNotes == 'on') {
                //Write to notes
                $noteGateway = $container->get(StudentNoteGateway::class);
                $note = [
                    'title'                       => __('Behaviour').': '.$descriptor,
                    'note'                        => empty($followup) ? $comment : $comment.' <br/><br/>'.$followup,
                    'gibbonPersonID'              => $gibbonPersonID,
                    'gibbonPersonIDCreator'       => $session->get('gibbonPersonID'),
                    'gibbonStudentNoteCategoryID' => $noteGateway->getNoteCategoryIDByName('Behaviour') ?? null,
                    'timestamp'                   => date('Y-m-d H:i:s', time()),
                ];

                $inserted = $noteGateway->insert($note);

                if (!$inserted) $partialFail = true;
            }
        }

        // Send all notifications
        $notificationSender->sendNotifications();

        if ($partialFail == true) {
            $URL .= '&return=warning1';
            header("Location: {$URL}");
        } else {
            $URL .= '&return=success0';
            header("Location: {$URL}");
        }
    }
}
