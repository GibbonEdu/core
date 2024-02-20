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

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$gibbonINInvestigationID = $_POST['gibbonINInvestigationID'] ?? '';

$URL = $session->get('absoluteURL')."/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonPersonID=$gibbonPersonID&gibbonFormGroupID=$gibbonFormGroupID&gibbonYearGroupID=$gibbonYearGroupID";

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/investigations_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    $highestAction = getHighestGroupedAction($guid, '/modules/Individual Needs/investigations_manage.php', $connection2);
    if ($highestAction == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Proceed!
    $investigationGateway = $container->get(INInvestigationGateway::class);

    $data = [
        'reason'                => $_POST['reason'] ?? '',
        'strategiesTried'       => $_POST['strategiesTried'] ?? '',
        'parentsInformed'       => $_POST['parentsInformed'] ?? '',
        'parentsResponse'       => $_POST['parentsResponse'] ?? null
    ];

    // Validate the required values are present
    if (empty($data['reason']) || empty($data['parentsInformed'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Validate the database record exist
    $investigation = $investigationGateway->getInvestigationByID($gibbonINInvestigationID);
    if (empty($investigation)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $canEdit = false ;
    if ($highestAction == 'Manage Investigations_all' || ($highestAction == 'Manage Investigations_my' && ($investigation['gibbonPersonIDCreator'] == $session->get('gibbonPersonID')))) {
        $canEdit = true ;
    }

    $isTutor = false ;
    if ($investigation['gibbonPersonIDTutor'] == $session->get('gibbonPersonID') || $investigation['gibbonPersonIDTutor2'] == $session->get('gibbonPersonID') || $investigation['gibbonPersonIDTutor3'] == $session->get('gibbonPersonID')) {
        $isTutor = true ;
    }

    if (!$canEdit && !$isTutor) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Update the record
    $updated = $investigationGateway->update($gibbonINInvestigationID, $data);

    //Deal with resolution
    if ($isTutor && $investigation['status'] == 'Referral') {
        $notificationGateway = $container->get(NotificationGateway::class);
        $notificationSender = $container->get(NotificationSender::class);

        $studentName = Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true);
        $status = ($_POST['resolvable'] == 'Y') ? 'Resolved' : 'Investigation';

        if ($status == 'Resolved') { //Notify the requesting teacher
            $data = [
                'resolutionDetails'      => $_POST['resolutionDetails'] ?? '',
                'status'                => $status,
            ];

            $updated = $investigationGateway->update($gibbonINInvestigationID, $data);

            $notificationString = __('An Individual Needs investigation for {student} has been resolved.', ['student' => $studentName]);
            $notificationSender->addNotification($investigation['gibbonPersonIDCreator'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            $notificationSender->sendNotifications();
        } else if ($status == 'Investigation') { //Notify requesting teacher, and start further investigation
            $contributionGateway = $container->get(INInvestigationContributionGateway::class);

            //Get list of checked contributors
            $contributorsCount = 0;
            $contributors = array();
            if (!empty($_POST['gibbonPersonIDHOY'])) {
                $contributors[$contributorsCount]['type'] = 'Head of Year' ;
                $contributors[$contributorsCount]['gibbonPersonID'] = $_POST['gibbonPersonIDHOY'] ;
                $contributors[$contributorsCount]['gibbonCourseClassPersonID'] = null ;
                $contributorsCount++;
            }
            $gibbonCourseClassPersonIDs = $_POST['gibbonCourseClassPersonID'] ?? array();
            foreach ($gibbonCourseClassPersonIDs AS $gibbonCourseClassPersonID) {
                $contributors[$contributorsCount]['type'] = 'Teacher' ;
                $contributors[$contributorsCount]['gibbonPersonID'] = substr($gibbonCourseClassPersonID, 0, 10) ;
                $contributors[$contributorsCount]['gibbonCourseClassPersonID'] = substr($gibbonCourseClassPersonID, 11, 10) ;
                $contributorsCount++;
            }

            if (count($contributors) <1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit;
            } else {
                //Update investigation status
                $data = [
                    'status'                => $status,
                ];
                $updated = $investigationGateway->update($gibbonINInvestigationID, $data);

                foreach ($contributors AS $contributor) {
                    $contributor['gibbonINInvestigationID'] = $gibbonINInvestigationID;

                    //Insert contributor
                    $insert = $contributionGateway->insert($contributor);

                    //Notify contributor
                    $notificationString = __('Your input into an Individual Needs investigation for {student} has been requested.', ['student' => $studentName]);
                    $notificationSender->addNotification($contributor['gibbonPersonID'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_submit_detail.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonINInvestigationContributionID=$insert");
                }
            }

            //Notify requesting teacher
            $notificationString = __('Further inquiry into the Individual Needs investigation for {student} has been initiated.', ['student' => $studentName]);
            $notificationSender->addNotification($investigation['gibbonPersonIDCreator'], $notificationString, "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            $notificationSender->sendNotifications();
        }
    }

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
