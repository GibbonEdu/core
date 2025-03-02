<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright 2010, Gibbon Foundation
Gibbon, Gibbon Education Ltd. (Hong Kong)

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

use Gibbon\Domain\IndividualNeeds\INInterventionContributorGateway;
use Gibbon\Domain\IndividualNeeds\INInterventionGateway;
use Gibbon\Domain\System\NotificationGateway;
use Gibbon\Services\Format;
use Gibbon\Data\Validator;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonINInterventionID = $_POST['gibbonINInterventionID'] ?? '';
$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonFormGroupID = $_POST['gibbonFormGroupID'] ?? '';
$gibbonYearGroupID = $_POST['gibbonYearGroupID'] ?? '';
$status = $_POST['status'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/interventions_manage_edit.php&gibbonINInterventionID='.$gibbonINInterventionID.'&gibbonPersonID='.$gibbonPersonID.'&gibbonFormGroupID='.$gibbonFormGroupID.'&gibbonYearGroupID='.$gibbonYearGroupID.'&status='.$status;

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/interventions_manage_contributor_add.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    // Get action with highest precedence
    $highestAction = getHighestGroupedAction($guid, $_POST['address'], $connection2);
    if (empty($highestAction)) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Proceed!
    if (empty($gibbonINInterventionID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    $interventionGateway = $container->get(INInterventionGateway::class);
    $intervention = $interventionGateway->getInterventionByID($gibbonINInterventionID);

    if (empty($intervention)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Check access based on the highest action level
    if ($highestAction == 'Manage Interventions_my' && $intervention['gibbonPersonIDCreator'] != $session->get('gibbonPersonID')) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
        exit;
    }

    // Validate Inputs
    $gibbonPersonIDContributor = $_POST['gibbonPersonIDContributor'] ?? '';
    $type = $_POST['type'] ?? '';

    if (empty($gibbonPersonIDContributor) || empty($type)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    }

    // Check if this person is already a contributor
    $contributorGateway = $container->get(INInterventionContributorGateway::class);
    $criteria = $contributorGateway->newQueryCriteria()->fromPOST();
    $contributors = $contributorGateway->queryContributorsByIntervention($criteria, $gibbonINInterventionID);
    
    foreach ($contributors as $contributor) {
        if ($contributor['gibbonPersonID'] == $gibbonPersonIDContributor) {
            $URL .= '&return=error7';
            header("Location: {$URL}");
            exit;
        }
    }

    // Add the contributor
    $data = [
        'gibbonINInterventionID' => $gibbonINInterventionID,
        'gibbonPersonID' => $gibbonPersonIDContributor,
        'type' => $type,
        'status' => 'Pending'
    ];

    $inserted = $contributorGateway->insert($data);

    if (!$inserted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        exit;
    }

    // Send notification to the contributor
    $notificationGateway = $container->get(NotificationGateway::class);

    $studentName = Format::name('', $intervention['preferredName'], $intervention['surname'], 'Student', false, true);
    $notificationString = __('You have been added as a contributor to the intervention "{name}" for {student}.', [
        'name' => $intervention['name'],
        'student' => $studentName
    ]);
    
    // Add notification event
    $notificationGateway->addNotification([$gibbonPersonIDContributor], 'Individual Needs', $notificationString, 'interventions_manage_edit.php', [
        'gibbonINInterventionID' => $gibbonINInterventionID
    ], 'Alert');

    $URL .= '&return=success0';
    header("Location: {$URL}");
    exit;
}
