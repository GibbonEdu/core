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

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;

//Gibbon system-wide includes
include '../../gibbon.php';

//Module includes
include './moduleFunctions.php';

$gibbonPlannerEntryID = $_POST['gibbonPlannerEntryID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&search=".$_POST['search'].$_POST['params'];

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
                $replyTo = $_POST['replyTo'];
                if ($_POST['replyTo'] == '') {
                    $replyTo = null;
                }
                //Attempt to prevent XSS attack
                $comment = $_POST['comment'];
                $comment = tinymceStyleStripTags($comment, $connection2);

                try {
                    $dataInsert = array('gibbonPlannerEntryID' => $gibbonPlannerEntryID, 'gibbonPersonID' => $_SESSION[$guid]['gibbonPersonID'], 'comment' => $comment, 'replyTo' => $replyTo);
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
                $notificationGateway = new NotificationGateway($pdo);
                $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

                //Create notification for all people in class except me
                $dataClassGroup = array('gibbonCourseClassID' => $row['gibbonCourseClassID']);
                $sqlClassGroup = "SELECT * FROM gibbonCourseClassPerson INNER JOIN gibbonPerson ON gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID WHERE gibbonCourseClassID=:gibbonCourseClassID AND status='Full' AND (dateStart IS NULL OR dateStart<='".date('Y-m-d')."') AND (dateEnd IS NULL  OR dateEnd>='".date('Y-m-d')."') AND (NOT role='Student - Left') AND (NOT role='Teacher - Left') ORDER BY role DESC, surname, preferredName";
                $resultClassGroup = $connection2->prepare($sqlClassGroup);
                $resultClassGroup->execute($dataClassGroup);
                while ($rowClassGroup = $resultClassGroup->fetch()) {
                    if ($rowClassGroup['gibbonPersonID'] != $_SESSION[$guid]['gibbonPersonID'] and $rowClassGroup['gibbonPersonID'] != $replyToID) {
                        $notificationText = sprintf(__('Someone has commented on your lesson plan "%1$s".'), $row['name']);

                        $notificationSender->addNotification($rowClassGroup['gibbonPersonID'], $notificationText, 'Planner', "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&date=".$row['date'].'&gibbonCourseClassID=&search=#chat');
                    }
                }

                $notificationSender->sendNotificationsAsBcc();

                //Create notification to person I am replying to
                if (is_null($replyToID) == false) {
                    $notificationText = sprintf(__('Someone has replied to a comment you made on lesson plan "%1$s".'), $row['name']);
                    $notificationSender->addNotification($replyToID, $notificationText, 'Planner', "/index.php?q=/modules/Planner/planner_view_full.php&gibbonPlannerEntryID=$gibbonPlannerEntryID&viewBy=date&date=".$row['date'].'&gibbonCourseClassID=&search=#chat');

                    $notificationSender->sendNotificationsAsBcc();
                }

                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
