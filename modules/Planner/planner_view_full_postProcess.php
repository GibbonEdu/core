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
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST, ['comment' => 'HTML']);

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=".$_POST['search'].($_POST['params'] ?? '');

if (isActionAccessible($guid, $connection2, '/modules/Planner/planner_view_full.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if ($highestAction == false) {
        $URL .= "&return=error0$params";
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if planner specified
        if ($gibbonPlannerEntryID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID);
                $sql = 'SELECT * FROM gibbonPlannerEntry WHERE gibbonPlannerEntryID=:gibbonPlannerEntryID';
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
                $row = $result->fetch();

                //INSERT
                $replyTo = !empty($_POST['replyTo']) ? $_POST['replyTo'] : null;
                $comment = $_POST['comment'] ?? '';
                
                try {
                    $dataInsert = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $session->get('gibbonPersonID'), 'comment' => $comment, 'replyTo' => $replyTo);
                    $sqlInsert = 'INSERT INTO gibbonPlannerEntryDiscuss SET gibbonPlannerEntryID=:gibbonPlannerEntryID, gibbonPersonID=:gibbonPersonID, comment=:comment, gibbonPlannerEntryDiscussIDReplyTo=:replyTo';
                    $resultInsert = $connection2->prepare($sqlInsert);
                    $resultInsert->execute($dataInsert);
                } catch (PDOException $e) {
                    $URL .= '&return=error2';
                    header("Location: {$URL}");
                    exit();
                }

                //Work out who we are replying too
                $replyToID = null;
                $dataClassGroup = array('gibbonPlannerEntryDiscussID' => $replyTo);
                $sqlClassGroup = 'SELECT * FROM gibbonPlannerEntryDiscuss WHERE gibbonPlannerEntryDiscussID=:gibbonPlannerEntryDiscussID';
                $resultClassGroup = $connection2->prepare($sqlClassGroup);
                $resultClassGroup->execute($dataClassGroup);
                if ($resultClassGroup->rowCount() == 1) {
                    $rowClassGroup = $resultClassGroup->fetch();
                    $replyToID = $rowClassGroup['gibbonPersonID'];
                }

                // Initialize the notification sender & gateway objects
                $notificationGateway = $container->get(NotificationGateway::class);
                $notificationSender = $container->get(NotificationSender::class);

                $personName = Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true);

                //Create notification for all people in class except me
                $dataClassGroup = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
                $sqlClassGroup = "SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName";
                $resultClassGroup = $connection2->prepare($sqlClassGroup);
                $resultClassGroup->execute($dataClassGroup);
                while ($rowClassGroup = $resultClassGroup->fetch()) {
                    if ($rowClassGroup['gibbonPersonID'] != $session->get('gibbonPersonID') and $rowClassGroup['gibbonPersonID'] != $replyToID) {
                        $notificationText = __('{person} has commented on your lesson plan {lessonName}.', ['person' => $personName, 'lessonName' => $row['name']]);

                        $notificationSender->addNotification($rowClassGroup['gibbonPersonID'], $notificationText, 'Planner', "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&date=".$row['date'].'&gibbonCourseClassID=&search=#chat');
                    }
                }

                $notificationSender->sendNotificationsAsBcc();

                //Create notification to person I am replying to
                if (is_null($replyToID) == false) {
                    $notificationText = __('{person} has replied to a comment you made on lesson plan {lessonName}.', ['person' => $personName, 'lessonName' => $row['name']]);
                    $notificationSender->addNotification($replyToID, $notificationText, 'Planner', "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&date=".$row['date'].'&gibbonCourseClassID=&search=#chat');

                    $notificationSender->sendNotificationsAsBcc();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
