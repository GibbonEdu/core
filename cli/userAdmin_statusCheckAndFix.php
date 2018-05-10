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

require getcwd().'/../gibbon.php';
require getcwd().'/../lib/PHPMailer/PHPMailerAutoload.php';

getSystemSettings($guid, $connection2);

setCurrentSchoolYear($guid, $connection2);

//Set up for i18n via gettext
if (isset($_SESSION[$guid]['i18n']['code'])) {
    if ($_SESSION[$guid]['i18n']['code'] != null) {
        putenv('LC_ALL='.$_SESSION[$guid]['i18n']['code']);
        setlocale(LC_ALL, $_SESSION[$guid]['i18n']['code']);
        bindtextdomain('gibbon', getcwd().'/../i18n');
        textdomain('gibbon');
    }
}

//Check for CLI, so this cannot be run through browser
if (!isCommandLineInterface()) { echo __($guid, 'This script cannot be run from a browser, only via CLI.');
} else {
    $count = 0;

    //Scan through every user to correct own status
    try {
        $data = array('gibbonSchoolYearID' => $_SESSION[$guid]['gibbonSchoolYearID']);
        $sql = 'SELECT gibbonPersonID, status, dateEnd, dateStart, gibbonRoleIDAll FROM gibbonPerson ORDER BY gibbonPersonID';
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        //Check for status=='Expected' when met or exceeded start date and set to 'Full'
        if ($row['dateStart'] != '' and date('Y-m-d') >= $row['dateStart'] and $row['status'] == 'Expected') {
            try {
                $dataUpdate = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlUpdate = "UPDATE gibbonPerson SET status='Full' WHERE gibbonPersonID=:gibbonPersonID";
                $resultUpdate = $connection2->prepare($sqlUpdate);
                $resultUpdate->execute($dataUpdate);
            } catch (PDOException $e) {
            }
            ++$count;
        }

        //Check for status=='Full' when end date exceeded, and set to 'Left'
        if ($row['dateEnd'] != '' and date('Y-m-d') > $row['dateEnd'] and $row['status'] == 'Full') {
            try {
                $dataUpdate = array('gibbonPersonID' => $row['gibbonPersonID']);
                $sqlUpdate = "UPDATE gibbonPerson SET status='Left' WHERE gibbonPersonID=:gibbonPersonID";
                $resultUpdate = $connection2->prepare($sqlUpdate);
                $resultUpdate->execute($dataUpdate);
            } catch (PDOException $e) {
            }
            ++$count;
        }
    }

    // Look for parents who are set to Full and counts the active children (also catches parents with no children)
    try {
        $data = array();
        $sql = "SELECT adult.gibbonPersonID,
                COUNT(DISTINCT CASE WHEN NOT child.status='Left' THEN child.gibbonPersonID END) as activeChildren
                FROM gibbonPerson as adult
                JOIN gibbonFamilyAdult ON (adult.gibbonPersonID=gibbonFamilyAdult.gibbonPersonID)
                LEFT JOIN gibbonFamilyChild ON (gibbonFamilyChild.gibbonFamilyID=gibbonFamilyAdult.gibbonFamilyID)
                LEFT JOIN gibbonPerson as child ON (child.gibbonPersonID=gibbonFamilyChild.gibbonPersonID)
                WHERE adult.status='Full'
                GROUP BY adult.gibbonPersonID";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    while ($row = $result->fetch()) {
        // Skip parents who have any active children
        if ($row['activeChildren'] > 0) continue;

        // Mark parents as Left only if they don't have other non-parent roles
        try {
            $data = array('gibbonPersonID' => $row['gibbonPersonID']);
            $sql = "UPDATE gibbonPerson SET gibbonPerson.status='Left' 
                    WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID 
                    AND (SELECT COUNT(*) FROM gibbonRole WHERE FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll) AND category<>'Parent') = 0";
            $resultUpdate = $connection2->prepare($sql);
            $resultUpdate->execute($data);
        } catch (PDOException $e) {
        }

        // Add the number of updated rows to the count
        $count += $resultUpdate->rowCount();
    }

    // Raise a new notification event
    $event = new NotificationEvent('User Admin', 'User Status Check and Fix');

    $event->setNotificationText(sprintf(__($guid, 'A User Admin CLI script has run, updating %1$s users.'), $count));
    $event->setActionLink('/index.php?q=/modules/User Admin/user_manage.php');

    //Notify admin
    $event->addRecipient($_SESSION[$guid]['organisationAdministrator']);

    // Send all notifications
    $sendReport = $event->sendNotifications($pdo, $gibbon->session);

    // Output the result to terminal
    echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
}
