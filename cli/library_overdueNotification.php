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

require getcwd().'/../config.php';
require getcwd().'/../functions.php';
require getcwd().'/../lib/PHPMailer/PHPMailerAutoload.php';

@session_start();

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
if (!isCommandLineInterface()) {
	print __($guid, "This script cannot be run from a browser, only via CLI.") ;
}
else {
    //SCAN THROUGH ALL OVERDUE LOANS
    $today = date('Y-m-d');

    try {
        $data = array('today' => $today);
        $sql = "SELECT gibbonLibraryItem.*, surname, preferredName, email FROM gibbonLibraryItem JOIN gibbonPerson ON (gibbonLibraryItem.gibbonPersonIDStatusResponsible=gibbonPerson.gibbonPersonID) WHERE gibbonLibraryItem.status='On Loan' AND borrowable='Y' AND returnExpected<:today AND gibbonPerson.status='Full' ORDER BY surname, preferredName";
        $result = $connection2->prepare($sql);
        $result->execute($data);
    } catch (PDOException $e) {
    }

    // Initialize the notification sender & gateway objects
    $notificationGateway = new NotificationGateway($pdo);
    $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

    // Raise a new notification event
    $event = new NotificationEvent('Library', 'Overdue Loan Items');

    if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) { //For every student
                $notificationText = sprintf(__($guid, 'You have an overdue loan item that needs to be returned (%1$s).'), $row['name']);
                $notificationSender->addNotification($row['gibbonPersonIDStatusResponsible'], $notificationText, 'Library', '/index.php?q=/modules/Library/library_browse.php&gibbonLibraryItemID='.$row['gibbonLibraryItemID']);
            }
        }
    }

    $event->setNotificationText(sprintf(__($guid, 'A Library Overdue Items CLI script has run, notifying %1$s users.'), $notificationSender->getNotificationCount()));
    $event->setActionLink('/index.php?q=/modules/Attendance/report_rollGroupsNotRegistered_byDate.php');

    // Push the event to the notification sender
    $event->pushNotifications($notificationGateway, $notificationSender);

    // Send all notifications
    $sendReport = $notificationSender->sendNotifications();

    // Output the result to terminal
    echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
}
?>
