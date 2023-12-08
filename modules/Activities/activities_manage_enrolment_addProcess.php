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
use Gibbon\Domain\User\UserGateway;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\Activities\ActivityGateway;

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

$logGateway = $container->get(LogGateway::class);
$gibbonActivityID = $_GET['gibbonActivityID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/activities_manage_enrolment_add.php&gibbonActivityID=$gibbonActivityID&search=".$_GET['search']."&gibbonSchoolYearTermID=".$_GET['gibbonSchoolYearTermID'];

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_enrolment_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    //Validate Inputs
    $status = $_POST['status'] ?? '';

    if ($gibbonActivityID == '' or $status == '') {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        //Run through each of the selected participants.
        $update = true;
        $choices = $_POST['Members'] ?? [];

        if (count($choices) < 1) {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            $activity = $container->get(ActivityGateway::class)->getByID($gibbonActivityID);

            $activityStudentGateway = $container->get(ActivityStudentGateway::class);
            $activityStaffGateway = $container->get(ActivityStaffGateway::class);
            $userGateway = $container->get(UserGateway::class);
            $added = [];

            foreach ($choices as $gibbonPersonID) {
                // Check to see if student is already registered in this activity
                $activityStudent = $activityStudentGateway->selectBy(['gibbonPersonID' => $gibbonPersonID, 'gibbonActivityID' => $gibbonActivityID])->fetch();

                // If student not in activity, add them
                if (empty($activityStudent)) {
                    $data = [
                        'gibbonPersonID' => $gibbonPersonID,
                        'gibbonActivityID' => $gibbonActivityID,
                        'status' => $status,
                        'timestamp' => date('Y-m-d H:i:s', time()),
                    ];

                    $inserted = $activityStudentGateway->insert($data);
                    $student = $userGateway->getUserDetails($gibbonPersonID, $session->get('gibbonSchoolYearID'));

                    if (!empty($inserted) && !empty($student)) {
                        $added[] = Format::name('', $student['preferredName'], $student['surname'], 'Student', false, false).' ('.$student['formGroup'].') '.$status;
                    }

                    // Set log
                    $gibbonModuleID = getModuleIDFromName($connection2, 'Activities') ;
                    $logGateway->addLog($session->get('gibbonSchoolYearIDCurrent'), $gibbonModuleID, $session->get('gibbonPersonID'), 'Activities - Student Added', ['gibbonPersonIDStudent' => $gibbonPersonID]);
                }
            }

            if (!empty($added)) {
                // Raise a new notification event
                $event = new NotificationEvent('Activities', 'Activity Enrolment Added');

                $notificationText = __('The following participants have been added to the activity {name}', ['name' => $activity['name']]).':<br/>'.Format::list($added);

                $event->setNotificationText($notificationText);
                $event->setActionLink('/index.php?q=/modules/Activities/activities_manage_enrolment.php&gibbonActivityID='.$gibbonActivityID.'&search=&gibbonSchoolYearTermID=');

                $activityStaff = $activityStaffGateway->selectActivityStaff($gibbonActivityID)->fetchAll();
                foreach ($activityStaff as $staff) {
                    $event->addRecipient($staff['gibbonPersonID']);
                }

                $event->sendNotifications($pdo, $session);
            }

            //Write to database
            if ($update == false) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $URL .= '&return=success0';
                header("Location: {$URL}");
            }
        }
    }
}
