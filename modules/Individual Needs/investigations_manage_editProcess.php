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

use Gibbon\Comms\NotificationSender;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationGateway;
use Gibbon\Domain\IndividualNeeds\INInvestigationContributionGateway;
use Gibbon\Services\Format;

require_once '../../gibbon.php';

$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$gibbonRollGroupID = $_GET['gibbonRollGroupID'] ?? '';
$gibbonYearGroupID = $_GET['gibbonYearGroupID'] ?? '';
$gibbonINInvestigationID = $_POST['gibbonINInvestigationID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID&gibbonPersonID=$gibbonPersonID&gibbonRollGroupID=$gibbonRollGroupID&gibbonYearGroupID=$gibbonYearGroupID";

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
    $criteria = $investigationGateway->newQueryCriteria();
    $investigation = $investigationGateway->queryInvestigationsByID($criteria, $gibbonINInvestigationID, $_SESSION[$guid]['gibbonSchoolYearID']);
    $investigation = $investigation->getRow(0);

    if (empty($investigation)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    $canEdit = false ;
    if ($highestAction == 'Manage Investigations_all' || ($highestAction == 'Manage Investigations_my' && ($investigation['gibbonPersonIDCreator'] == $_SESSION[$guid]['gibbonPersonID']))) {
        $canEdit = true ;
    }

    $isTutor = false ;
    if ($investigation['gibbonPersonIDTutor'] == $_SESSION[$guid]['gibbonPersonID'] || $investigation['gibbonPersonIDTutor2'] == $_SESSION[$guid]['gibbonPersonID'] || $investigation['gibbonPersonIDTutor3'] == $_SESSION[$guid]['gibbonPersonID']) {
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
        $notificationGateway = new NotificationGateway($pdo);
        $notificationSender = new NotificationSender($notificationGateway, $gibbon->session);

        $status = ($_POST['resolvable'] == 'Y') ? 'Resolved' : 'Investigation';

        if ($status == 'Resolved') { //Notify the requesting teacher
            $data = [
                'resolutionDetails'      => $_POST['resolutionDetails'] ?? '',
                'status'                => $status,
            ];

            $updated = $investigationGateway->update($gibbonINInvestigationID, $data);

            $notificationSender->addNotification($investigation['gibbonPersonIDCreator'], sprintf(__('An Individual Needs investigation for %1$s has been resolved.'), Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            $notificationSender->sendNotifications();
        }
        else if ($status == 'Investigation') { //Notify requestion teacher, and start further investigation
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
            }
            else {
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
                    $notificationSender->addNotification($contributor['gibbonPersonID'], sprintf(__('Your input has been requested for an Individual Needs investigation on %1$s.'), Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_submit_detail.php&gibbonINInvestigationContributionID=$insert");
                }
            }

            //Notify requesting teacher
            $notificationSender->addNotification($investigation['gibbonPersonIDCreator'], sprintf(__('An Individual Needs investigation for %1$s requires further investigation.'), Format::name('', $investigation['preferredName'], $investigation['surname'], 'Student', false, true)), "Individual Needs", "/index.php?q=/modules/Individual Needs/investigations_manage_edit.php&gibbonINInvestigationID=$gibbonINInvestigationID");
            $notificationSender->sendNotifications();
        }
    }

    $URL .= !$updated
        ? "&return=error2"
        : "&return=success0";

    header("Location: {$URL}");
}
