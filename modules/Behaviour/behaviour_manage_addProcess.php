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
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Services\Format;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;

include '../../gibbon.php';

$enableDescriptors = getSettingByScope($connection2, 'Behaviour', 'enableDescriptors');
$enableLevels = getSettingByScope($connection2, 'Behaviour', 'enableLevels');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/behaviour_manage_add.php&gibbonPersonID='.$_GET['gibbonPersonID'].'&gibbonRollGroupID='.$_GET['gibbonRollGroupID'].'&gibbonYearGroupID='.$_GET['gibbonYearGroupID'].'&type='.$_GET['type'];

if (isActionAccessible($guid, $connection2, '/modules/Behaviour/behaviour_manage_add.php') == false) {
    $URL .= '&return=error0&step=1';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0&step=1';
        header("Location: {$URL}");
    } else {

        $step = $_GET['step'] ?? null;

        if ($step != 1 and $step != 2) {
            $step = 1;
        }
        $gibbonBehaviourID = $_POST['gibbonBehaviourID'] ?? null;


        //Step 1
        if ($step == 1 or $gibbonBehaviourID == null) {
            //Proceed!
            $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
            $date = $_POST['date'] ?? '';
            $type = $_POST['type'] ?? '';
            $descriptor = $_POST['descriptor'] ?? null;
            $level = $_POST['level'] ?? null;

            $comment = $_POST['comment'] ?? '';
            $followup = $_POST['followup'] ?? '';
            $copyToNotes = $_POST['copyToNotes'] ?? null;

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

                // Attempt to notify tutor(s) and EA(s) of negative behaviour
                if ($type == 'Negative') {

                        $dataDetail = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID'], 'gibbonPersonID' => $gibbonPersonID);
                        $sqlDetail = 'SELECT gibbonPersonIDTutor, gibbonPersonIDTutor2, gibbonPersonIDTutor3, surname, preferredName, gibbonStudentEnrolment.gibbonYearGroupID FROM gibbonRollGroup JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID) JOIN gibbonPerson ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStudentEnrolment.gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID';
                        $resultDetail = $connection2->prepare($sqlDetail);
                        $resultDetail->execute($dataDetail);
                    if ($resultDetail->rowCount() == 1) {
                        $rowDetail = $resultDetail->fetch();

                        // Initialize the notification sender & gateway objects
                        $notificationGateway = new NotificationGateway($pdo);
                        $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

                        $studentName = Format::name('', $rowDetail['preferredName'], $rowDetail['surname'], 'Student', false);
                        $actionLink = "/index.php?q=/modules/Behaviour/behaviour_view_details.php&gibbonPersonID=$gibbonPersonID&search=";

                        // Raise a new notification event
                        $event = new NotificationEvent('Behaviour', 'New Negative Record');

                        $event->setNotificationText(sprintf(__('Someone has created a negative behaviour record for %1$s.'), $studentName));
                        $event->setActionLink($actionLink);

                        $event->addScope('gibbonPersonIDStudent', $gibbonPersonID);
                        $event->addScope('gibbonYearGroupID', $rowDetail['gibbonYearGroupID']);

                        // Add notifications for Educational Assistants
                        if (getSettingByScope($connection2, 'Behaviour', 'notifyEducationalAssistants') == 'Y') {
                            $educationalAssistants = $container->get(INAssistantGateway::class)->selectINAssistantsByStudent($gibbonPersonID)->fetchAll();
                            foreach ($educationalAssistants as $ea) {
                                $event->addRecipient($ea['gibbonPersonID']);
                            }
                        }

                        // Add event listeners to the notification sender
                        $event->pushNotifications($notificationGateway, $notificationSender);

                        // Add direct notifications to roll group tutors
                        if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
                            if (getSettingByScope($connection2, 'Behaviour', 'notifyTutors') == 'Y') {
                                $notificationText = sprintf(__('Someone has created a negative behaviour record for your tutee, %1$s.'), $studentName);

                                if ($rowDetail['gibbonPersonIDTutor'] != null and $rowDetail['gibbonPersonIDTutor'] != $_SESSION[$guid]['gibbonPersonID']) {
                                    $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor'], $notificationText, 'Behaviour', $actionLink);
                                }
                                if ($rowDetail['gibbonPersonIDTutor2'] != null and $rowDetail['gibbonPersonIDTutor2'] != $_SESSION[$guid]['gibbonPersonID']) {
                                    $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor2'], $notificationText, 'Behaviour', $actionLink);
                                }
                                if ($rowDetail['gibbonPersonIDTutor3'] != null and $rowDetail['gibbonPersonIDTutor3'] != $_SESSION[$guid]['gibbonPersonID']) {
                                    $notificationSender->addNotification($rowDetail['gibbonPersonIDTutor3'], $notificationText, 'Behaviour', $actionLink);
                                }
                            }
                        }

                        // Send all notifications
                        $notificationSender->sendNotifications();
                    }
                }

                if ($copyToNotes == 'on') {
                    //Write to notes
                    $noteGateway = $container->get(StudentNoteGateway::class);
                    $note = [
                        'title'                       => __('Behaviour').': '.$descriptor,
                        'note'                        => empty($followup) ? $comment : $comment.' <br/><br/>'.$followup,
                        'gibbonPersonID'              => $gibbonPersonID,
                        'gibbonPersonIDCreator'       => $_SESSION[$guid]['gibbonPersonID'],
                        'gibbonStudentNoteCategoryID' => $noteGateway->getNoteCategoryIDByName('Behaviour') ?? null,
                        'timestamp'                   => date('Y-m-d H:i:s', time()),
                    ];

                    $inserted = $noteGateway->insert($note);

                    if (!$inserted) {
                        $URL .= "&return=warning1&step=2&gibbonBehaviourID=$gibbonBehaviourID&editID=$AI";
                        header("Location: {$URL}");
                        exit;
                    }
                }

                $URL .= "&return=success1&step=2&gibbonBehaviourID=$gibbonBehaviourID&editID=$AI";
                header("Location: {$URL}");
            }
        } elseif ($step == 2 and $gibbonBehaviourID != null) {
            //Proceed!
            $gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
            $gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'] ?? null;
            $AI = $_GET['editID'] ?? '';
            

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

                    $URL .= "&return=success0&editID=$gibbonBehaviourID";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
