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

use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;

require getcwd().'/../gibbon.php';

//Check for CLI, so this cannot be run through browser
$settingGateway = $container->get(SettingGateway::class);
$remoteCLIKey = $settingGateway->getSettingByScope('System Admin', 'remoteCLIKey');
$remoteCLIKeyInput = $_GET['remoteCLIKey'] ?? null;
if (!(isCommandLineInterface() OR ($remoteCLIKey != '' AND $remoteCLIKey == $remoteCLIKeyInput))) {
	print __("This script cannot be run from a browser, only via CLI.") ;
}
else {
    //SCAN THROUGH ALL UPCOMING EXPIRES
    $today = date('Y-m-d');
    $data = array('today' => $today);
    $sql = "SELECT p.gibbonPersonID, p.surname, p.preferredName, s.firstAidExpiry 
    
    FROM `gibbonStaff` s 
    JOIN gibbonPerson p ON (p.`gibbonPersonID` = s.`gibbonPersonID`) 
    WHERE (p.`status` = 'Full' 
    AND firstAidExpiry <= DATE_ADD(:today, INTERVAL 3 month) 
    AND firstAidExpiry > :today);";
    $result = $connection2->prepare($sql);
    $result->execute($data);

    // Initialize the notification sender & gateway objects
    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = $container->get(NotificationSender::class);

    // Raise a new notification event
    $event = new NotificationEvent('Staff', 'First Aid Qualification Expiry');
    $staffList = [];

    if ($event->getEventDetails($notificationGateway, 'active') == 'Y') {
        if ($result->rowCount() > 0) {
            while ($row = $result->fetch()) { //For every staff
                $staffNames = $row['surname']. ', ' . $row['preferredName'] . ' - ' . Format::date($row['firstAidExpiry']);
                $staffList[] = $staffNames;
            }
        }
    }

    if(empty($staffList) )
    {
        return;
    }

    $event->setNotificationText(__('The following people have first aid qualifications that are expiring soon:').'<br/></br>'.Format::list($staffList));
    $event->setActionLink('/index.php?q=/modules/Staff/staff_manage.php');

    // Push the event to the notification sender
    $event->pushNotifications($notificationGateway, $notificationSender);

    // Send all notifications
    $sendReport = $notificationSender->sendNotifications();

    // Output the result to terminal
    echo sprintf('Sent %1$s notifications: %2$s inserts, %3$s updates, %4$s emails sent, %5$s emails failed.', $sendReport['count'], $sendReport['inserts'], $sendReport['updates'], $sendReport['emailSent'], $sendReport['emailFailed'])."\n";
} 