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

use Gibbon\Domain\System\LogGateway;
use Gibbon\Data\Validator;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$logGateway = $container->get(LogGateway::class);
$gibbonActivityID = $_GET['gibbonActivityID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_edit.php') == false) { 
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_manage_enrolment_edit.php&gibbonPersonID=$gibbonPersonID&gibbonActivityID=$gibbonActivityID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID'];

    if ($gibbonActivityID == '' or $gibbonPersonID == '') {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    } 

    $student = $container->get(UserGateway::class)->getUserDetails($gibbonPersonID, $session->get('gibbonSchoolYearID'));
    if (empty($student)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    // Check if status specified
    $status = $_POST['status'] ?? '';
    if (empty($status)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $activityStudentGateway = $container->get(ActivityStudentGateway::class);
    $activityStaffGateway = $container->get(ActivityStaffGateway::class);

    $activity = $container->get(ActivityGateway::class)->getByID($gibbonActivityID);
    $activityStudent = $activityStudentGateway->selectBy(['gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID])->fetch();

    if (empty($activity) || empty($activityStudent)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }
    
    $statusOld = $activity['status'];

    // Write to database
    $data = ['gibbonActivityID' => $gibbonActivityID, 'gibbonPersonID' => $gibbonPersonID, 'status' => $status];
    $activityStudentGateway->update($activityStudent['gibbonActivityStudentID'], $data);

    
    if ($statusOld != $status) {
        // Raise a new notification event
        $event = new NotificationEvent('Activities', 'Activity Status Changed');
        $studentName = Format::name('', $student['preferredName'], $student['surname'], 'Student', false, false).' ('.$student['formGroup'].')';
        
        $notificationText = __('The following participants have been set to {status} in {name}', ['name' => $activity['name'], 'status' => __($status) ]).':<br/>'.Format::list([$studentName]);
        
        $event->setNotificationText($notificationText);
        $event->setActionLink('/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$gibbonActivityID.'&search=&gibbonSchoolYearTermID=');

        $activityStaff = $activityStaffGateway->selectActivityStaff($gibbonActivityID)->fetchAll();
        foreach ($activityStaff as $staff) {
            $event->addRecipient($staff['gibbonPersonID']);
        }

        $event->sendNotifications($pdo, $session);
        
        // Set log
        $gibbonModuleID = getModuleIDFromName($connection2, 'Activities') ;
        $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), $gibbonModuleID, $session->get('gibbonPersonID'), 'Activities - Student Status Changed', array('gibbonPersonIDStudent' => $gibbonPersonID, 'statusOld' => $statusOld, 'statusNew' => $status));
    }

    $URL .= '&return=success0';
    header("Location: {$URL}");
}
