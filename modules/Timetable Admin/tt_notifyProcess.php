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
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Timetable\TimetableGateway;

include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/tt.php';

    $gibbonTTID = $_GET['gibbonTTID'] ?? '';
    $gibbonPersonID = $session->get('gibbonPersonID');

    $tt = $container->get(TimetableGateway::class)->getTTByID($gibbonTTID);

    if (empty($gibbonTTID) || empty($tt)) {
        $page->addError(__('You have not specified one or more required parameters.'));
        return;
    }

    $notificationGateway = $container->get(NotificationGateway::class);
    $notificationSender = $container->get(NotificationSender::class);

    // Raise a new notification event
    $event = new NotificationEvent('Timetable', 'Updated Timetable Subscriber');
    $actionLink = "/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=".$gibbonPersonID;

    $notificationText = __('The timetable {name} has been updated, please visit your timetable and export again to ensure it remains up to date.', ['name' => $tt['name']]);

    $event->setNotificationText($notificationText);
    $event->setActionLink($actionLink);

    // Add event listeners to the notification sender
    $event->pushNotifications($notificationGateway, $notificationSender);

    // Send all notifications
    $sendReport = $notificationSender->sendNotifications();

    $URL .= $sendReport['emailFailed']  > 0
        ? "&return=warning1"
        : "&return=success0"; 
    header("Location: {$URL}");
  }
?>
