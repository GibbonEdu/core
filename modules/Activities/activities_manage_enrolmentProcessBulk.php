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

use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Services\Format;
use Gibbon\Domain\System\LogGateway;

include '../../gibbon.php';

$action = $_POST['action'] ?? '';
$gibbonActivityID = $_POST['gibbonActivityID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID=$gibbonActivityID";

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $enrolments = $_POST['gibbonActivityStudentID'] ?? [];

    if (empty($action) || ($action != 'Mark as Left' && $action != 'Mark as Accepted' && $action != 'Delete')) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } 
    
    // Check if person specified
    if (empty($enrolments)) {
        $URL .= '&return=error3';
        header("Location: {$URL}");
        exit;
    } 

    $activity = $container->get(ActivityGateway::class)->getByID($gibbonActivityID);
    if (empty($activity)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    } 

    $activityStudentGateway = $container->get(ActivityStudentGateway::class);
    $activityStaffGateway = $container->get(ActivityStaffGateway::class);
    $userGateway = $container->get(UserGateway::class);
    $logGateway = $container->get(LogGateway::class);

    $partialFail = false;
    $students = [];
    
    foreach ($enrolments AS $gibbonActivityStudentID) {
        $activityStudent = $activityStudentGateway->getByID($gibbonActivityStudentID);
        $student = $userGateway->getUserDetails($activityStudent['gibbonPersonID'] ?? '', $session->get('gibbonSchoolYearID'));

        if (empty($activityStudent) || empty($student)) {
            $partialFail = true;
            continue;
        }

        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false, false).' ('.$student['formGroup'].')';
        $students[] = $studentName;

        if ($action == 'Mark as Accepted') {
            $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $activityStudent['gibbonPersonID'], 'status' => 'Accepted'];
            $activityStudentGateway->update($activityStudent['gibbonActivityStudentID'], $data);
        } elseif ($action == 'Mark as Left') {
            $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $activityStudent['gibbonPersonID'], 'status' => 'Left'];
            $activityStudentGateway->update($activityStudent['gibbonActivityStudentID'], $data);
        } else if ($action == 'Delete') {
            $activityStudentGateway->delete($activityStudent['gibbonActivityStudentID']);
        }
    }

    // Raise a new notification event
    $event = new NotificationEvent('Activities', 'Activity Enrolment Removed');
    
    if ($action == 'Mark as Accepted') {
        $notificationText =  __('The following participants have been set to {status} in {name}', ['name' => $activity['name'], 'status' => __('Accepted') ]).':<br/>'.Format::list($students);
    } elseif ($action == 'Mark as Left') {
        $notificationText =  __('The following participants have been set to {status} in {name}', ['name' => $activity['name'], 'status' => __('Left') ]).':<br/>'.Format::list($students);
    } else if ($action == 'Delete') {
        $notificationText = __('The following participants have been removed from the activity {name}', ['name' => $activity['name']]).':<br/>'.Format::list($students);
    }
    
    $event->setNotificationText($notificationText);
    $event->setActionLink('/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$gibbonActivityID.'&search=&gibbonSchoolYearTermID=');

    $activityStaff = $activityStaffGateway->selectActivityStaff($gibbonActivityID)->fetchAll();
    foreach ($activityStaff as $staff) {
        $event->addRecipient($staff['gibbonPersonID']);
    }

    $event->sendNotifications($pdo, $session);
    
    // Set log
    $gibbonModuleID = getModuleIDFromName($connection2, 'Activities') ;
    $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), $gibbonModuleID, $session->get('gibbonPersonID'), $action == 'Delete' ? 'Activities - Student Deleted' : 'Activities - Student Status Changed', ['students' => implode(',', $students)]);

    $URL .= $partialFail
        ? '&return=warning1'
        : '&return=success0';
    header("Location: {$URL}");
}
