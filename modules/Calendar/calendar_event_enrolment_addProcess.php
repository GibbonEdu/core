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
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Messenger\GroupGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Calendar\CalendarEventGateway;
use Gibbon\Domain\Calendar\CalendarEventPersonGateway;
use Gibbon\Domain\Timetable\CourseClassGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonCalendarEventID = $_GET['gibbonCalendarEventID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Calendar/calendar_event_enrolment.php&gibbonCalendarEventID='.$gibbonCalendarEventID;

if (isActionAccessible($guid, $connection2, '/modules/Calendar/calendar_event_enrolment.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed
    $targetStudents = $_POST['targetStudents'] ?? '';
    $gibbonActivityID = $_POST['gibbonActivityID'] ?? '';
    $gibbonGroupID = $_POST['gibbonGroupID'] ?? '';
    $gibbonCourseClassID = $_POST['gibbonCourseClassID'] ?? '';
    $gibbonPersonIDList = $_POST['participants'] ?? [];
    $foreignTable = '';
    
    // Check if required values are specified
    if (empty($targetStudents)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    switch ($targetStudents) {
        case 'Activity':
            if ($container->get(ActivityGateway::class)->exists($gibbonActivityID)) {
                $foreignTable = 'gibbonActivity';
                $targetID = $gibbonActivityID;
            }
            break;
        case 'Messenger':
            if ($container->get(GroupGateway::class)->getByID($gibbonGroupID)) {
                $targetID = $gibbonGroupID;
                $foreignTable = 'gibbonGroup';
            };
            break;
        case 'Class':
            if ($container->get(CourseClassGateway::class)->getByID($gibbonCourseClassID)) {
                $targetID = $gibbonCourseClassID;
                $foreignTable = 'gibbonCourseClass';
            };
            break;
        case 'manualSelect':
            $targetID = $gibbonPersonIDList;
            break;
    }

    if (empty($targetID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Get all the participants from the selected target
    $calendarEventPersonGateway = $container->get(CalendarEventPersonGateway::class);
    $participants = $calendarEventPersonGateway->selectTargetStudentsForEnrolment($session->get('gibbonSchoolYearID'), $targetStudents, $targetID)->fetchAll();
    
    $gibbonPersonIDs = array_column($participants, 'gibbonPersonID');
    
    if (empty($gibbonPersonIDs)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $partialFail = false;

    foreach ($gibbonPersonIDs as $gibbonPersonID) {
        $data = [
            'gibbonCalendarEventID' => $gibbonCalendarEventID,
            'gibbonPersonID'        => $gibbonPersonID,
            'role'                  => 'Attendee',
            'timestampCreated'      => date('Y-m-d H:i:s'),
            'timestampModified'     => date('Y-m-d H:i:s'),
            'gibbonPersonIDCreated'   => $session->get('gibbonPersonID'),
            'gibbonPersonIDModified'   => $session->get('gibbonPersonID'),            
        ];

        $inserted = $calendarEventPersonGateway->insertAndUpdate($data, $data);
        $partialFail &= !$inserted;
    }

    if (!($targetStudents == 'manualSelect')) {
        $calendarEventGateway = $container->get(CalendarEventGateway::class);
        $data = ['foreignTable' => $foreignTable, 'foreignTableID' => $targetID];
        $partialFail &= !$calendarEventGateway->update($gibbonCalendarEventID, $data);
    }

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';

    header("Location: {$URL}");

    // NOTIFICATION
    // if (!empty($added)) {
    //     // Raise a new notification event
    //     $event = new NotificationEvent('Activities', 'Activity Enrolment Added');

    //     $notificationText = __('The following participants have been added to the activity {name}', ['name' => $activity['name']]).':<br/>'.Format::list($added);

    //     $event->setNotificationText($notificationText);
    //     $event->setActionLink('/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$gibbonActivityID.'&search=&gibbonSchoolYearTermID=');

    //     $activityStaff = $activityStaffGateway->selectActivityStaff($gibbonActivityID)->fetchAll();
    //     foreach ($activityStaff as $staff) {
    //         $event->addRecipient($staff['gibbonPersonID']);
    //     }

    //     $event->sendNotifications($pdo, $session);
    // }
}