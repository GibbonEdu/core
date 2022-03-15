<?php

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Comms\NotificationSender;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\Students\StudentNoteGateway;
use Gibbon\Domain\IndividualNeeds\INAssistantGateway;
use Gibbon\Data\Validator;
include '../../gibbon.php';

if (isActionAccessible($guid, $connection2, '/modules/Timetable Admin/tt.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/Timetable Admin/tt.php';

    $gibbonPersonID = $gibbon->session->get('gibbonPersonID');

    $notificationGateway = new NotificationGateway($pdo);
    $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

    // Raise a new notification event
    $event = new NotificationEvent('Timetable', 'Updated Timetable Subscriber');
    $actionLink = "/index.php?q=/modules/Timetable/tt_view.php&gibbonPersonID=".$gibbonPersonID;

    $notificationText = sprintf(('The timetable has been updated, to update your downloaded calendar please export it again.').'<br/><br/>');

    $event->setNotificationText($notificationText);
    $event->setActionLink($actionLink);

    // Add event listeners to the notification sender
    $event->pushNotifications($notificationGateway, $notificationSender);

    // Send all notifications
    $notificationSender->sendNotifications();


    $URL .= "&return=success0"; //TODO: IF THE NOTIFICATION ERRORS, WE MIGHT NOT WANT TO THROW A SUCCESS MESSAGE
    header("Location: {$URL}");
  }
?>
