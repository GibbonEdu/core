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

use Gibbon\Data\Validator;
use Gibbon\Services\Format;
use Gibbon\Comms\NotificationEvent;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\Activities\ActivityGateway;
use Gibbon\Domain\Activities\ActivityChoiceGateway;
use Gibbon\Domain\Activities\ActivityStaffGateway;
use Gibbon\Domain\Activities\ActivityTripGateway;
use Gibbon\Domain\Activities\ActivityStudentGateway;
use Gibbon\Domain\Activities\ActivityCategoryGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$params = [
    'gibbonActivityCategoryID' => $_POST['gibbonActivityCategoryID'] ?? '',
    'sidebar'             => 'false',
];

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Activities/enrolment_manage.php&'.http_build_query($params);

if (isActionAccessible($guid, $connection2, '/modules/Activities/enrolment_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Proceed!
    $partialFail = false;

    $activityCategoryGateway = $container->get(ActivityCategoryGateway::class);
    $activityGateway = $container->get(ActivityGateway::class);
    $activityStudentGateway = $container->get(ActivityStudentGateway::class);
    $activityChoiceGateway = $container->get(ActivityChoiceGateway::class);
    $studentGateway = $container->get(StudentGateway::class);

    $enrolmentList = $_POST['person'] ?? [];

    // Validate the required values are present
    if (empty($params['gibbonActivityCategoryID']) || empty($enrolmentList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database relationships exist
    $categoryDetails = $activityCategoryGateway->getByID($params['gibbonActivityCategoryID']);
    if (empty($categoryDetails)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $activities = [];
    $unassigned = [];
    $changeList = [];

    // Update student enrolments
    foreach ($enrolmentList as $gibbonPersonID => $gibbonActivityID) {
        $change = '';

        // Get any existing enrolment
        $enrolment = $activityStudentGateway->getEnrolmentByCategoryAndPerson($params['gibbonActivityCategoryID'], $gibbonPersonID);
        $student = $studentGateway->selectActiveStudentByPerson($categoryDetails['gibbonSchoolYearID'], $gibbonPersonID)->fetch();

        if (empty($student)) {
            $partialFail = true;
            continue;
        }

        if (empty($gibbonActivityID)) {
            // Record this removal so it can be updated in the database
            if (!empty($enrolment['gibbonActivityID'])) {
                $unassigned[] = $gibbonPersonID;
                $activities[] = $enrolment['gibbonActivityID'];
                $activityName = $enrolment['activityName'];
                $change = __('Removed from');
            }
        } else {
            // Connect the choice to the enrolment, for future queries and weighting
            $activity = $activityGateway->getByID($gibbonActivityID, ['name']);
            $choice = $activityChoiceGateway->getChoiceByActivityAndPerson($gibbonActivityID, $gibbonPersonID);
            $choiceNumber = intval($choice['choice'] ?? 0);

            if (!empty($enrolment)) {
                // Update and existing enrolment
                $data = [
                    'gibbonActivityID'       => $gibbonActivityID,
                    'gibbonActivityChoiceID' => $choice['gibbonActivityChoiceID'] ?? null,
                    'timestamp'              => date('Y-m-d H:i:s'),
                ];

                $updated = $activityStudentGateway->update($enrolment['gibbonActivityStudentID'], $data);

                if ($enrolment['gibbonActivityID'] != $data['gibbonActivityID']) {
                    $activities[] = $data['gibbonActivityID'];
                    $activities[] = $enrolment['gibbonActivityID'];
                    $activityName = $activity['name'];
                    $change = __('Moved to');
                }
            } else {
                // Add a new enrolment
                $data = [
                    'gibbonActivityID'       => $gibbonActivityID,
                    'gibbonActivityChoiceID' => $choice['gibbonActivityChoiceID'] ?? null,
                    'gibbonPersonID'         => $gibbonPersonID,
                    'status'                 => 'Accepted',
                    'timestamp'              => date('Y-m-d H:i:s'),
                ];

                $inserted = $activityStudentGateway->insert($data);
                $partialFail &= !$inserted;

                $activityName = $activity['name'];
                $activities[] = $gibbonActivityID;
                $change = __('Added to');
            }
        }

        if (!empty($change)) {
            $changeList[] = __('{student} ({formGroup}) - <i>{change} {activity}</i>', [
                'student'    => Format::name('', $student['preferredName'], $student['surname'], 'Student', false, true),
                'formGroup'  => $student['formGroup'],
                'change'     => $change,
                'activity' => $activityName ?? __('Unknown'),
            ]);
        }
    }

    $activities = array_unique($activities);

    // Remove enrolments that have been unassigned
    foreach ($unassigned as $gibbonPersonID) {
        $activityStudentGateway->deleteEnrolmentByCategoryAndPerson($params['gibbonActivityCategoryID'], $gibbonPersonID);
    }

    // Raise a new notification category
    if (!empty($changeList)) {
        $event = new NotificationEvent('Activities', 'Activity Status Changed');
        $event->setNotificationText(__('{person} has made the following changes to {category} enrolment:', [
            'person' => Format::name('', $session->get('preferredName'), $session->get('surname'), 'Staff', false, true),
            'category' => $categoryDetails['name'] ?? __('Activities'),
        ]).'<br/>'.Format::list($changeList));

        // Notify activity leaders
        $staff = $container->get(ActivityStaffGateway::class)->selectStaffByActivity($activities);
        foreach ($staff as $person) {
            $event->addRecipient($person['gibbonPersonID']);
        }

        $event->setActionLink("/index.php?q=/modules/Activities/report_overview.php&gibbonActivityCategoryID=".$params['gibbonActivityCategoryID']);
        $event->sendNotifications($pdo, $session);
    }
    

    $URL .= $partialFail
        ? "&return=warning1"
        : "&return=success0";
    header("Location: {$URL}");
}
